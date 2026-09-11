<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\ExportContactRequest;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Models\Category;
use App\Models\Tag;
use App\Services\ContactSearchService;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactController extends Controller
{
    public function __construct(
        private ContactSearchService $contactSearchService,
    ) {
    }

    /**
     * お問い合わせフォーム入力ページ表示
     */
    public function index(): View
    {
        $categories = Category::orderBy('id')->get();
        $tags = Tag::orderBy('id')->get();
        return view('contact.index', compact('categories', 'tags'));
    }

    /**
     * 入力内容のバリデーション＋確認ページ表示
     */
    public function confirm(StoreContactRequest $request): View
    {
        $validated = $request->validated();
        $tel1 = $request->input('tel1');
        $tel2 = $request->input('tel2');
        $tel3 = $request->input('tel3');

        $category = Category::findOrFail($validated['category_id']);
        $tags = Tag::whereIn('id', $validated['tag_ids'] ?? [])->get();

        return view('contact.confirm', compact('validated', 'category', 'tags', 'tel1', 'tel2', 'tel3'));

    }

    /**
     * 確認画面から入力画面へ戻る
     */
    public function back(Request $request): RedirectResponse
    {
        return redirect()->route('contact.index')->withInput($request->all());
    }

    /**
     * お問い合わせ送信（確認ページからの再送信データを再検証して保存）
     */
    public function store(StoreContactRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $contact = Contact::create([
            'category_id' => $validated['category_id'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'gender' => $validated['gender'],
            'email' => $validated['email'],
            'tel' => $validated['tel'],
            'address' => $validated['address'],
            'building' => $validated['building'] ?? null,
            'detail' => $validated['detail'],
        ]);

        if (!empty($validated['tag_ids'])) {
            $contact->tags()->attach($validated['tag_ids']);
        }

        return redirect()->route('contact.thanks');
    }

    /**
     * サンクスページ表示
     */
    public function thanks(): View
    {
        return view('contact.thanks');
    }

    /**
     * お問い合わせ一覧をCSVエクスポート
     */
    public function export(ExportContactRequest $request): StreamedResponse
    {
        $genderLabels = [1 => '男性', 2 => '女性', 3 => 'その他'];

        $contacts = $this->contactSearchService
            ->search($request->validated())
            ->with(['category'])
            ->get();

        $fileName = 'contacts_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ];

        return response()->streamDownload(function () use ($contacts, $genderLabels) {
            echo "\xEF\xBB\xBF";

            $stream = fopen('php://output', 'w');

            fputcsv($stream, [
                'ID',
                '氏名',
                '性別',
                'メール',
                '電話',
                '住所',
                '建物',
                'カテゴリ',
                '内容',
                '作成日時',
            ]);

            foreach ($contacts as $contact) {
                fputcsv($stream, [
                    $contact->id,
                    $contact->last_name . ' ' . $contact->first_name,
                    $genderLabels[$contact->gender] ?? '',
                    $contact->email,
                    $contact->tel,
                    $contact->address,
                    $contact->building,
                    $contact->category->content ?? '',
                    $contact->detail,
                    $contact->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($stream);
        }, $fileName, $headers);
    }
}

