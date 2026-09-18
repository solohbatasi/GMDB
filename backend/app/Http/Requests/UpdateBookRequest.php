<?php

namespace App\Http\Requests;

use App\Models\Book;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $book = $this->route('book');
        $inventoryId = $book?->inventoryItem?->id;

        return [
            'book_category_id' => ['nullable', 'exists:book_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('books', 'slug')->ignore($book)],
            'isbn' => ['nullable', 'string', 'max:32'],
            'author' => ['required', 'string', 'max:255'],
            'publisher' => ['nullable', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:1000'],
            'description' => ['nullable', 'string'],
            'cover' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_price' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'featured' => ['boolean'],
            'is_digital' => ['boolean'],
            'status' => ['required', Rule::in(Book::STATUSES)],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:500'],
            'external_purchase_url' => ['nullable', 'url', 'max:255'],
            'sku' => ['nullable', 'string', 'max:64', Rule::unique('inventory_items', 'sku')->ignore($inventoryId)],
            'reorder_level' => ['nullable', 'integer', 'min:0'],
            'track_stock' => ['boolean'],
        ];
    }
}
