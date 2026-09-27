<?php

namespace App\Services;

use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class CartQuoteService
{
    public function __construct(private MoneyService $money) {}

    public function quote(array $items): array
    {
        $normalized = $this->normalizeItems($items);
        $books = $this->publicBooksBySlug(array_column($normalized, 'slug'));
        $requestedBySlug = [];

        foreach ($normalized as $item) {
            $requestedBySlug[$item['slug']] = ($requestedBySlug[$item['slug']] ?? 0) + $item['quantity'];
        }
        $quotedItems = [];
        $subtotalCents = 0;
        $valid = true;
        $currency = 'KES';

        foreach ($normalized as $normalizedItem) {
            $slug = $normalizedItem['slug'];
            $quantity = $normalizedItem['quantity'];
            $priceOption = $normalizedItem['price_option'];
            $book = $books->get($slug);

            if (! $book) {
                $valid = false;
                $quotedItems[] = [
                    'slug' => $slug,
                    'price_option' => $priceOption,
                    'quantity' => $quantity,
                    'valid' => false,
                    'message' => 'This book is no longer available.',
                ];

                continue;
            }

            $inventory = $book->inventoryItem;
            $availability = $this->availability($inventory);
            $availableQuantity = $inventory?->track_stock ? $inventory->available_quantity : null;
            $lineValid = ! ($inventory?->track_stock && $availableQuantity < $requestedBySlug[$slug]);
            $valid = $valid && $lineValid;
            $usesSecondaryPrice = $priceOption === 'secondary'
                && $book->compare_price !== null
                && (float) $book->compare_price > 0;
            $resolvedPriceOption = $usesSecondaryPrice ? 'secondary' : 'primary';
            $unitCents = $this->money->decimalToCents($usesSecondaryPrice ? $book->compare_price : $book->price);
            $lineCents = $unitCents * $quantity;
            $subtotalCents += $lineValid ? $lineCents : 0;
            $currency = $book->currency;

            $quotedItems[] = [
                'book_id' => $book->id,
                'slug' => $book->slug,
                'price_option' => $resolvedPriceOption,
                'price_option_label' => $resolvedPriceOption === 'secondary' ? 'Paperback' : 'Hardcover',
                'title' => $book->title,
                'author' => $book->author,
                'cover_url' => $book->cover_url,
                'sku' => $inventory?->sku,
                'unit_price' => $this->money->centsToDecimal($unitCents),
                'quantity' => $quantity,
                'line_total' => $this->money->centsToDecimal($lineCents),
                'availability' => $availability,
                'available' => ! $inventory?->track_stock || $availableQuantity > 0,
                'available_quantity' => $availableQuantity,
                'valid' => $lineValid,
                'message' => $lineValid ? null : 'Only '.$availableQuantity.' copies of "'.$book->title.'" remain.',
            ];
        }

        return [
            'items' => $quotedItems,
            'subtotal' => $this->money->centsToDecimal($subtotalCents),
            'shipping_total' => '0.00',
            'discount_total' => '0.00',
            'tax_total' => '0.00',
            'total' => $this->money->centsToDecimal($subtotalCents),
            'currency' => $currency,
            'valid' => $valid,
        ];
    }

    public function normalizeItems(array $items): array
    {
        if ($items === [] || count($items) > 50) {
            throw new InvalidArgumentException('Please add at least one book to your cart.');
        }

        $normalized = [];

        foreach ($items as $item) {
            $slug = trim((string) ($item['slug'] ?? ''));
            $quantity = (int) ($item['quantity'] ?? 0);
            $priceOption = (string) ($item['price_option'] ?? 'primary');

            if (! preg_match('/\A[A-Za-z0-9_-]+\z/', $slug)
                || $quantity < 1
                || $quantity > 99
                || ! in_array($priceOption, ['primary', 'secondary'], true)) {
                throw new InvalidArgumentException('Your cart contains an invalid item.');
            }

            $key = $slug.'|'.$priceOption;
            $normalized[$key] ??= [
                'slug' => $slug,
                'price_option' => $priceOption,
                'quantity' => 0,
            ];
            $normalized[$key]['quantity'] += $quantity;

            if ($normalized[$key]['quantity'] > 99) {
                $normalized[$key]['quantity'] = 99;
            }
        }

        return array_values($normalized);
    }

    public function publicBooksBySlug(array $slugs): Collection
    {
        return Book::query()
            ->with('inventoryItem', 'category')
            ->whereIn('slug', $slugs)
            ->where('status', 'active')
            ->where(fn (Builder $query) => $query->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->get()
            ->keyBy('slug');
    }

    protected function availability(mixed $inventory): string
    {
        if (! $inventory || ! $inventory->track_stock) {
            return 'untracked';
        }

        if ($inventory->available_quantity === 0) {
            return 'out_of_stock';
        }

        return $inventory->available_quantity <= $inventory->reorder_level ? 'low_stock' : 'in_stock';
    }
}
