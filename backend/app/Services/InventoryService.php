<?php

namespace App\Services;

use App\Models\Book;
use App\Models\InventoryItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    public function open(
        Book $book,
        string $sku,
        int $openingQuantity = 0,
        int $reorderLevel = 5,
        bool $trackStock = true,
        ?User $user = null,
        ?string $reference = null,
        ?string $notes = null,
    ): InventoryItem {
        if ($openingQuantity < 0) {
            throw new InvalidArgumentException('Opening quantity cannot be negative.');
        }

        return DB::transaction(function () use ($book, $sku, $openingQuantity, $reorderLevel, $trackStock, $user, $reference, $notes) {
            $inventory = InventoryItem::create([
                'book_id' => $book->id,
                'sku' => $sku,
                'quantity_on_hand' => $openingQuantity,
                'quantity_reserved' => 0,
                'reorder_level' => $reorderLevel,
                'track_stock' => $trackStock,
            ]);

            StockMovement::create([
                'book_id' => $book->id,
                'user_id' => $user?->id,
                'type' => 'opening',
                'quantity_change' => $openingQuantity,
                'quantity_before' => 0,
                'quantity_after' => $openingQuantity,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            return $inventory;
        });
    }

    public function restock(Book $book, int $quantity, ?User $user = null, ?string $reference = null, ?string $notes = null): InventoryItem
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Restock quantity must be greater than zero.');
        }

        return $this->move($book, 'restock', $quantity, $user, $reference, $notes);
    }

    public function adjust(Book $book, int $quantityChange, ?User $user = null, ?string $reference = null, ?string $notes = null): InventoryItem
    {
        if ($quantityChange === 0) {
            throw new InvalidArgumentException('Adjustment quantity cannot be zero.');
        }

        return $this->move($book, 'adjustment', $quantityChange, $user, $reference, $notes);
    }

    public function availableStock(Book|InventoryItem $model): int
    {
        $inventory = $model instanceof Book ? $model->inventoryItem : $model;

        if (! $inventory) {
            return 0;
        }

        return max(0, $inventory->quantity_on_hand - $inventory->quantity_reserved);
    }

    public function isLowStock(Book|InventoryItem $model): bool
    {
        $inventory = $model instanceof Book ? $model->inventoryItem : $model;

        if (! $inventory || ! $inventory->track_stock) {
            return false;
        }

        $available = $this->availableStock($inventory);

        return $available > 0 && $available <= $inventory->reorder_level;
    }

    protected function move(Book $book, string $type, int $quantityChange, ?User $user, ?string $reference, ?string $notes): InventoryItem
    {
        return DB::transaction(function () use ($book, $type, $quantityChange, $user, $reference, $notes) {
            $inventory = InventoryItem::where('book_id', $book->id)->lockForUpdate()->firstOrFail();
            $before = $inventory->quantity_on_hand;
            $after = $before + $quantityChange;

            if ($after < 0) {
                throw new InvalidArgumentException('This stock change would make quantity on hand negative.');
            }

            $inventory->update(['quantity_on_hand' => $after]);

            StockMovement::create([
                'book_id' => $book->id,
                'user_id' => $user?->id,
                'type' => $type,
                'quantity_change' => $quantityChange,
                'quantity_before' => $before,
                'quantity_after' => $after,
                'reference' => $reference,
                'notes' => $notes,
            ]);

            return $inventory->refresh();
        });
    }
}
