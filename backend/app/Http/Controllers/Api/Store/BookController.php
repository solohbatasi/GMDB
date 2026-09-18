<?php

namespace App\Http\Controllers\Api\Store;

use App\Http\Controllers\Controller;
use App\Http\Resources\Store\BookResource;
use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'category' => ['nullable', 'string', 'max:255'],
            'featured' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:100'],
            'sort' => ['nullable', 'in:newest,oldest,title'],
            'limit' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $limit = min((int) ($validated['limit'] ?? 12), 50);

        $books = $this->storefrontQuery()
            ->when($validated['category'] ?? null, fn (Builder $query, string $category) => $query->whereHas(
                'category',
                fn (Builder $categoryQuery) => $categoryQuery->where('slug', $category)->orWhere('id', is_numeric($category) ? (int) $category : 0)
            ))
            ->when($request->has('featured'), fn (Builder $query) => $query->where('featured', $request->boolean('featured')))
            ->when($validated['search'] ?? null, fn (Builder $query, string $search) => $query->where(function (Builder $searchQuery) use ($search) {
                $searchQuery
                    ->where('title', 'like', "%{$search}%")
                    ->orWhere('author', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            }));

        match ($validated['sort'] ?? 'newest') {
            'oldest' => $books->orderBy('published_at')->orderBy('created_at'),
            'title' => $books->orderBy('title'),
            default => $books->latest('published_at')->latest(),
        };

        return BookResource::collection($books->paginate($limit)->withQueryString());
    }

    public function show(string $slug): BookResource|JsonResponse
    {
        $book = $this->storefrontQuery()
            ->where('slug', $slug)
            ->first();

        if (! $book) {
            return response()->json(['message' => 'Book not found.'], 404);
        }

        return new BookResource($book);
    }

    protected function storefrontQuery(): Builder
    {
        return Book::query()
            ->with('category', 'inventoryItem')
            ->where('status', 'active')
            ->where(fn (Builder $query) => $query
                ->whereNull('published_at')
                ->orWhere('published_at', '<=', now())
            );
    }
}
