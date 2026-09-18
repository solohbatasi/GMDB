<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

type Stats = {
    total_books: number;
    active_books: number;
    total_units_in_stock: number;
    low_stock_books: number;
    out_of_stock_books: number;
    pending_orders: number;
    awaiting_payment: number;
    confirmed_revenue: string | number;
    paid_orders: number;
    pending_payments: number;
    failed_payments: number;
    payments_requiring_review: number;
    active_reservations: number;
    pending_order_value: string | number;
};

type Book = {
    id: number;
    title: string;
    author: string;
    status: string;
    cover_url?: string | null;
    category?: { name: string } | null;
    inventory_item?: { available_quantity: number; stock_status: string } | null;
};

defineProps<{
    stats: Stats;
    recentBooks: Book[];
    lowStockBooks: Array<{ id: number; sku: string; available_quantity: number; reorder_level: number; book: Book }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dashboard', href: dashboard() }],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-1 flex-col gap-6 p-4">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Bookstore Dashboard</h1>
            <p class="text-muted-foreground text-sm">Current catalogue and stock position.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-5">
            <div v-for="(value, label) in stats" :key="label" class="rounded-lg border p-4">
                <div class="text-muted-foreground text-xs uppercase">{{ String(label).replaceAll('_', ' ') }}</div>
                <div class="mt-2 text-2xl font-semibold">{{ value }}</div>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-lg border">
                <div class="flex items-center justify-between border-b p-4">
                    <h2 class="font-medium">Recently Added Books</h2>
                    <Link href="/books" class="text-sm underline">Manage</Link>
                </div>
                <div class="divide-y">
                    <div v-for="book in recentBooks" :key="book.id" class="flex items-center gap-3 p-4">
                        <img v-if="book.cover_url" :src="book.cover_url" class="h-14 w-10 rounded object-cover" :alt="book.title">
                        <div v-else class="h-14 w-10 rounded bg-muted" />
                        <div class="min-w-0">
                            <div class="truncate font-medium">{{ book.title }}</div>
                            <div class="text-muted-foreground truncate text-sm">{{ book.author }} · {{ book.status }}</div>
                        </div>
                    </div>
                    <div v-if="recentBooks.length === 0" class="p-4 text-sm text-muted-foreground">No books yet.</div>
                </div>
            </section>

            <section class="rounded-lg border">
                <div class="flex items-center justify-between border-b p-4">
                    <h2 class="font-medium">Low Stock Books</h2>
                    <Link href="/inventory?condition=low" class="text-sm underline">Review</Link>
                </div>
                <div class="divide-y">
                    <div v-for="item in lowStockBooks" :key="item.id" class="flex items-center justify-between gap-3 p-4">
                        <div class="min-w-0">
                            <div class="truncate font-medium">{{ item.book.title }}</div>
                            <div class="text-muted-foreground text-sm">SKU {{ item.sku }}</div>
                        </div>
                        <div class="text-right text-sm">
                            <div>{{ item.available_quantity }} available</div>
                            <div class="text-muted-foreground">Reorder at {{ item.reorder_level }}</div>
                        </div>
                    </div>
                    <div v-if="lowStockBooks.length === 0" class="p-4 text-sm text-muted-foreground">No low stock books.</div>
                </div>
            </section>
        </div>
    </div>
</template>
