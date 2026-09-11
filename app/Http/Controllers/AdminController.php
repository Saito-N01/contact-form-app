<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use App\Services\ContactSearchService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function __construct(
        private ContactSearchService $contactSearchService,
    ) {
    }

    /**
     * 管理画面一覧（検索・ページネーション）
     */
    public function index(IndexContactRequest $request): View
    {
        $contacts = $this->contactSearchService
            ->search($request->validated())
            ->with(['category', 'tags'])
            ->paginate(7);

        $categories = Category::orderBy('id')->get();
        $tags = Tag::orderBy('id')->get();

        return view('admin.index', compact('contacts', 'categories', 'tags'));
    }

    /**
     * お問い合わせ詳細ページ表示
     */
    public function show(Contact $contact): View
    {
        $contact->load(['category', 'tags']);

        return view('admin.show', compact('contact'));
    }

    /**
     *お問い合わせ削除
     */
    public function destroy(Contact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()->route('admin.index');
    }
}
