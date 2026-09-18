<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

type Book = { id: number; title: string; author: string; cover_url?: string | null; inventory_item?: { sku: string; quantity_on_hand: number; quantity_reserved: number; available_quantity: number; stock_status: string } | null };
type Movement = { id: number; created_at: string; type: string; quantity_change: number; quantity_before: number; quantity_after: number; reference?: string | null; notes?: string | null; user?: { name: string } | null };
type Page<T> = { data: T[] };

defineProps<{ book: Book; movements: Page<Movement> }>();
</script>

<template>
    <Head :title="`${book.title} Inventory`" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <Link href="/inventory" class="text-sm underline">Back to inventory</Link>
        <div class="flex items-center gap-4 rounded-lg border p-4">
            <img v-if="book.cover_url" :src="book.cover_url" class="h-24 w-16 rounded object-cover" :alt="book.title">
            <div>
                <h1 class="text-2xl font-semibold">{{ book.title }}</h1>
                <p class="text-muted-foreground">{{ book.author }}</p>
                <p v-if="book.inventory_item" class="text-sm">SKU {{ book.inventory_item.sku }} · {{ book.inventory_item.available_quantity }} available · {{ book.inventory_item.stock_status }}</p>
            </div>
        </div>
        <div class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-muted/40 text-left"><tr><th class="p-3">Date</th><th class="p-3">Type</th><th class="p-3">Change</th><th class="p-3">Before</th><th class="p-3">After</th><th class="p-3">Reference</th><th class="p-3">Admin</th></tr></thead>
                <tbody class="divide-y">
                    <tr v-for="movement in movements.data" :key="movement.id">
                        <td class="p-3">{{ movement.created_at }}</td><td class="p-3">{{ movement.type }}</td><td class="p-3">{{ movement.quantity_change }}</td><td class="p-3">{{ movement.quantity_before }}</td><td class="p-3">{{ movement.quantity_after }}</td><td class="p-3">{{ movement.reference ?? '-' }}</td><td class="p-3">{{ movement.user?.name ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
