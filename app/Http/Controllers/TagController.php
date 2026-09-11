<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTagRequest;
use App\Http\Requests\UpdateTagRequest;
use App\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TagController extends Controller
{
    /**
     * タグ追加
     */
    public function store(StoreTagRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        Tag::create([
            'name' => $validated['name'],
        ]);

        return redirect()->route('admin.index');
    }

    /**
     * タグ編集ページ表示
     */
    public function edit(Tag $tag): View
    {
        return view('admin.tags.edit', compact('tag'));
    }

    /**
     * タグ更新
     */
    public function update(UpdateTagRequest $request, Tag $tag): RedirectResponse
    {
        $validated = $request->validated();

        $tag->update([
            'name' => $validated['name'],
        ]);

        return redirect()->route('admin.index');
    }

    /**
     * タグ削除
     */
    public function destroy(Tag $tag): RedirectResponse
    {
        $tag->delete();

        return redirect()->route('admin.index');
    }
}
