<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { backendPath } from '@/lib/backendPath';

type Movement = { id: number; created_at: string; type: string; quantity_change: number; quantity_before: number; quantity_after: number; reference?: string | null; notes?: string | null; book: { title: string }; user?: { name: string } | null };
type Page<T> = { data: T[] };

defineProps<{ movements: Page<Movement> }>();
</script>

<template>
    <Head title="Stock Movements" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <div><h1 class="text-2xl font-semibold">Stock Movements</h1><p class="text-muted-foreground text-sm">Newest movement history first.</p></div>
            <Link :href="backendPath('/inventory')" class="rounded-md border px-3 py-2 text-sm">Inventory</Link>
        </div>
        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full min-w-[900px] text-sm">
                <thead class="bg-muted/40 text-left"><tr><th class="p-3">Date</th><th class="p-3">Book</th><th class="p-3">Type</th><th class="p-3">Change</th><th class="p-3">Before</th><th class="p-3">After</th><th class="p-3">Reference</th><th class="p-3">Admin</th><th class="p-3">Notes</th></tr></thead>
                <tbody class="divide-y">
                    <tr v-for="movement in movements.data" :key="movement.id">
                        <td class="p-3">{{ movement.created_at }}</td><td class="p-3">{{ movement.book.title }}</td><td class="p-3">{{ movement.type }}</td><td class="p-3">{{ movement.quantity_change }}</td><td class="p-3">{{ movement.quantity_before }}</td><td class="p-3">{{ movement.quantity_after }}</td><td class="p-3">{{ movement.reference ?? '-' }}</td><td class="p-3">{{ movement.user?.name ?? '-' }}</td><td class="p-3">{{ movement.notes ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
