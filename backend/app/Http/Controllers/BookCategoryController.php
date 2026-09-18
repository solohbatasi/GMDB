<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBookCategoryRequest;
use App\Http\Requests\UpdateBookCategoryRequest;
use App\Models\BookCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BookCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('BookCategories/Index', [
            'filters' => ['search' => $search],
            'categories' => BookCategory::query()
                ->withCount('books')
                ->when($search, fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orderBy('sort_order')
                ->orderBy('name')
                ->paginate(12)
                ->withQueryString(),
        ]);
    }

    public function store(StoreBookCategoryRequest $request): RedirectResponse
    {
        BookCategory::create([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->integer('sort_order', 0),
        ]);

        return back()->with('success', 'Category created.');
    }

    public function update(UpdateBookCategoryRequest $request, BookCategory $bookCategory): RedirectResponse
    {
        $bookCategory->update([
            ...$request->validated(),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => $request->integer('sort_order', 0),
        ]);

        return back()->with('success', 'Category updated.');
    }

    public function destroy(BookCategory $bookCategory): RedirectResponse
    {
        $bookCategory->update(['is_active' => false]);

        return back()->with('success', 'Category deactivated.');
    }
}
