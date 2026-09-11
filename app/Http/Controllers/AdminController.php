<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexContactRequest;
use App\Models\Category;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminController extends Controller
{
    /**
     * 管理画面一覧表示（検索・ページネーション）
     */
    public function index(IndexContactRequest $request): View
    {
        $validated = $request->validated();
        $query = Contact::with(['category', 'tags']);

        if (!empty($validated['keyword'])) {
            $keyword = $validated['keyword'];
            $query->where(function ($query) use ($keyword) {
                $query->whereRaw("CONCAT(last_name, first_name) LIKE ?", ["%{$keyword}%"])
                    ->orWhereRaw("CONCAT(first_name, last_name) LIKE ?", ["%{$keyword}%"])
                    ->orWhere('email', 'like', "%{$keyword}%");
            });
        }

        if (!empty($validated['gender']) && $validated['gender'] != 0) {
            $query->where('gender', $validated['gender']);
        }

        if (!empty($validated['category_id'])) {
            $query->where('category_id', $validated['category_id']);
        }

        if (!empty($validated['date'])) {
            $query->whereDate('created_at', $validated['date']);
        }

        $contacts = $query->latest()->paginate(7);

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
