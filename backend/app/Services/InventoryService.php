<?php

namespace App\Services;

use App\Models\Book;
use App\Models\InventoryItem;
use App\Models\InventoryReservation;
use App\Models\Order;
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

    public function reserve(Order $order, Book $book, int $quantity, \DateTimeInterface $expiresAt): InventoryReservation
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('Reservation quantity must be greater than zero.');
        }

        return DB::transaction(function () use ($order, $book, $quantity, $expiresAt) {
            $inventory = InventoryItem::where('book_id', $book->id)->lockForUpdate()->first();

            if (! $inventory) {
                throw new InvalidArgumentException('This book does not have inventory configured.');
            }

            if ($inventory->track_stock && $inventory->available_quantity < $quantity) {
                throw new InvalidArgumentException('Insufficient stock is available.');
            }

            if ($inventory->track_stock) {
                $inventory->increment('quantity_reserved', $quantity);
            }

            return InventoryReservation::create([
                'order_id' => $order->id,
                'book_id' => $book->id,
                'quantity' => $quantity,
                'status' => 'active',
                'expires_at' => $expiresAt,
            ]);
        });
    }

    public function releaseReservation(InventoryReservation $reservation): InventoryReservation
    {
        return DB::transaction(function () use ($reservation) {
            $reservation = InventoryReservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if ($reservation->status !== 'active') {
                return $reservation;
            }

            $inventory = InventoryItem::where('book_id', $reservation->book_id)->lockForUpdate()->first();

            if ($inventory && $inventory->track_stock) {
                $inventory->update([
                    'quantity_reserved' => max(0, $inventory->quantity_reserved - $reservation->quantity),
                ]);
            }

            $reservation->update([
                'status' => 'released',
                'released_at' => now(),
            ]);

            return $reservation->refresh();
        });
    }

    public function commitReservation(InventoryReservation $reservation, ?Order $order = null, ?string $reference = null): InventoryReservation
    {
        return DB::transaction(function () use ($reservation, $order, $reference) {
            $reservation = InventoryReservation::whereKey($reservation->id)->lockForUpdate()->firstOrFail();

            if ($reservation->status !== 'active') {
                return $reservation;
            }

            $inventory = InventoryItem::where('book_id', $reservation->book_id)->lockForUpdate()->firstOrFail();

            if ($inventory->track_stock) {
                if ($inventory->quantity_on_hand < $reservation->quantity || $inventory->quantity_reserved < $reservation->quantity) {
                    throw new InvalidArgumentException('Reserved inventory cannot be committed.');
                }

                $before = $inventory->quantity_on_hand;
                $after = $before - $reservation->quantity;

                $inventory->update([
                    'quantity_on_hand' => $after,
                    'quantity_reserved' => $inventory->quantity_reserved - $reservation->quantity,
                ]);

                StockMovement::create([
                    'book_id' => $reservation->book_id,
                    'order_id' => $order?->id ?? $reservation->order_id,
                    'type' => 'sale',
                    'quantity_change' => -$reservation->quantity,
                    'quantity_before' => $before,
                    'quantity_after' => $after,
                    'reference' => $reference ?? $order?->order_number,
                    'notes' => 'Sale committed from paid order reservation.',
                    'metadata' => ['reservation_id' => $reservation->id],
                ]);
            }

            $reservation->update([
                'status' => 'committed',
                'committed_at' => now(),
            ]);

            return $reservation->refresh();
        });
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
