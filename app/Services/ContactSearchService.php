<?php

namespace App\Services;

use App\Models\Contact;
use Illuminate\Database\Eloquent\Builder;

class ContactSearchService
{
    /**
     * 検索条件に応じて絞り込んだContactクエリビルダを返す
     *
     * @param  array{keyword?: string, gender?: int, category_id?: int, date?: string}  $validated
     */
    public function search(array $validated): Builder
    {
        return Contact::query()
            ->when(!empty($validated['keyword']), function (Builder $query) use ($validated) {
                $keyword = $validated['keyword'];
                $query->where(function (Builder $query) use ($keyword) {
                    $query->whereRaw("CONCAT(last_name, first_name) LIKE ?", ["%{$keyword}%"])
                        ->orWhereRaw("CONCAT(first_name, last_name) LIKE ?", ["%{$keyword}%"])
                        ->orWhere('email', 'like', "%{$keyword}%");
                });
            })
            ->when(!empty($validated['gender']) && $validated['gender'] != 0, function (Builder $query) use ($validated) {
                $query->where('gender', $validated['gender']);
            })
            ->when(!empty($validated['category_id']), function (Builder $query) use ($validated) {
                $query->where('category_id', $validated['category_id']);
            })
            ->when(!empty($validated['date']), function (Builder $query) use ($validated) {
                $query->whereDate('created_at', $validated['date']);
            })
            ->latest();
    }
}
