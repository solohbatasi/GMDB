<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type Category = { id: number; name: string };
type Book = { id: number; title: string; author: string; cover_url?: string | null; category?: Category | null };
type Item = { id: number; sku: string; quantity_on_hand: number; quantity_reserved: number; available_quantity: number; reorder_level: number; track_stock: boolean; stock_status: string; book: Book };
type Page<T> = { data: T[] };

const props = defineProps<{ items: Page<Item>; categories: Category[]; filters: Record<string, string> }>();
const filters = ref({ ...props.filters });
const selected = ref<Item | null>(null);
const restockDialog = ref<HTMLDialogElement | null>(null);
const adjustDialog = ref<HTMLDialogElement | null>(null);
const restockForm = useForm({ quantity: 1, reference: '', notes: '' });
const adjustForm = useForm({ quantity_change: -1, reason: '', notes: '' });

const filter = () => router.get('/inventory', filters.value, { preserveState: true, replace: true });
const openRestock = (item: Item) => { selected.value = item; restockForm.reset(); restockDialog.value?.showModal(); };
const openAdjust = (item: Item) => { selected.value = item; adjustForm.reset(); adjustDialog.value?.showModal(); };
const submitRestock = () => selected.value && restockForm.post(`/inventory/${selected.value.book.id}/restock`, { preserveScroll: true, onSuccess: () => restockDialog.value?.close() });
const submitAdjust = () => selected.value && adjustForm.post(`/inventory/${selected.value.book.id}/adjust`, { preserveScroll: true, onSuccess: () => adjustDialog.value?.close() });
</script>

<template>
    <Head title="Inventory" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <div>
            <h1 class="text-2xl font-semibold">Inventory</h1>
            <p class="text-muted-foreground text-sm">Track stock without overwriting movement history.</p>
        </div>

        <form class="grid gap-2 md:grid-cols-5" @submit.prevent="filter">
            <input v-model="filters.search" class="rounded-md border bg-transparent px-3 py-2 text-sm" placeholder="Search book or SKU">
            <select v-model="filters.category" class="rounded-md border bg-background px-3 py-2 text-sm"><option value="">All categories</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select>
            <select v-model="filters.condition" class="rounded-md border bg-background px-3 py-2 text-sm"><option value="">All stock</option><option value="low">Low stock</option><option value="out">Out of stock</option><option value="disabled">Tracking disabled</option></select>
            <button class="rounded-md border px-3 py-2 text-sm">Filter</button>
            <Link href="/inventory/movements" class="rounded-md border px-3 py-2 text-center text-sm">All Movements</Link>
        </form>

        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full min-w-[900px] text-sm">
                <thead class="bg-muted/40 text-left">
                    <tr><th class="p-3">Book</th><th class="p-3">SKU</th><th class="p-3">On hand</th><th class="p-3">Reserved</th><th class="p-3">Available</th><th class="p-3">Reorder</th><th class="p-3">Condition</th><th class="p-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="item in items.data" :key="item.id">
                        <td class="flex items-center gap-3 p-3">
                            <img v-if="item.book.cover_url" :src="item.book.cover_url" class="h-14 w-10 rounded object-cover" :alt="item.book.title">
                            <div v-else class="h-14 w-10 rounded bg-muted" />
                            <div><div class="font-medium">{{ item.book.title }}</div><div class="text-muted-foreground">{{ item.book.category?.name ?? 'Uncategorized' }}</div></div>
                        </td>
                        <td class="p-3">{{ item.sku }}</td>
                        <td class="p-3">{{ item.quantity_on_hand }}</td>
                        <td class="p-3">{{ item.quantity_reserved }}</td>
                        <td class="p-3">{{ item.available_quantity }}</td>
                        <td class="p-3">{{ item.reorder_level }}</td>
                        <td class="p-3">{{ item.stock_status }}</td>
                        <td class="space-x-2 p-3 text-right"><Link class="rounded border px-2 py-1" :href="`/inventory/${item.book.id}`">View</Link><button class="rounded border px-2 py-1" @click="openRestock(item)">Restock</button><button class="rounded border px-2 py-1" @click="openAdjust(item)">Adjust</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <dialog ref="restockDialog" class="w-full max-w-md rounded-lg border bg-background p-0 text-foreground shadow-xl backdrop:bg-black/40">
            <form class="space-y-4 p-5" @submit.prevent="submitRestock">
                <h2 class="text-lg font-semibold">Restock {{ selected?.book.title }}</h2>
                <input v-model.number="restockForm.quantity" type="number" min="1" class="w-full rounded-md border bg-transparent px-3 py-2" placeholder="Quantity">
                <input v-model="restockForm.reference" class="w-full rounded-md border bg-transparent px-3 py-2" placeholder="Reference">
                <textarea v-model="restockForm.notes" class="w-full rounded-md border bg-transparent px-3 py-2" placeholder="Notes" />
                <div v-if="restockForm.errors.quantity" class="text-sm text-red-600">{{ restockForm.errors.quantity }}</div>
                <div class="flex justify-end gap-2"><button type="button" class="rounded-md border px-3 py-2" @click="restockDialog?.close()">Cancel</button><button class="rounded-md bg-primary px-3 py-2 text-primary-foreground">Restock</button></div>
            </form>
        </dialog>

        <dialog ref="adjustDialog" class="w-full max-w-md rounded-lg border bg-background p-0 text-foreground shadow-xl backdrop:bg-black/40">
            <form class="space-y-4 p-5" @submit.prevent="submitAdjust">
                <h2 class="text-lg font-semibold">Adjust {{ selected?.book.title }}</h2>
                <input v-model.number="adjustForm.quantity_change" type="number" class="w-full rounded-md border bg-transparent px-3 py-2" placeholder="Signed quantity, e.g. -2 or 4">
                <input v-model="adjustForm.reason" class="w-full rounded-md border bg-transparent px-3 py-2" placeholder="Reason">
                <textarea v-model="adjustForm.notes" class="w-full rounded-md border bg-transparent px-3 py-2" placeholder="Notes" />
                <div v-if="adjustForm.errors.quantity_change" class="text-sm text-red-600">{{ adjustForm.errors.quantity_change }}</div>
                <div class="flex justify-end gap-2"><button type="button" class="rounded-md border px-3 py-2" @click="adjustDialog?.close()">Cancel</button><button class="rounded-md bg-primary px-3 py-2 text-primary-foreground">Adjust</button></div>
            </form>
        </dialog>
    </div>
</template>
