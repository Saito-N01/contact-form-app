<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactRequest;
use Illuminate\Http\Request;
use App\Models\Contact;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
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
}

