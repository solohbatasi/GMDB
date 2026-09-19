<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { backendPath } from '@/lib/backendPath';

type Order = { id: number; order_number: string; customer_name: string; customer_phone: string; total: string; currency: string; payment_status: string; order_status: string; delivery_method: string; fulfillment_status: string; reservation_expires_at?: string | null; created_at: string; items_count: number };
type Page<T> = { data: T[] };

const props = defineProps<{ orders: Page<Order>; filters: Record<string, string> }>();
const filters = ref({ ...props.filters });
const filter = () => router.get(backendPath('/orders'), filters.value, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Orders" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <div><h1 class="text-2xl font-semibold">Orders</h1><p class="text-muted-foreground text-sm">Pending guest checkout orders and reservations.</p></div>
        <form class="grid gap-2 md:grid-cols-5" @submit.prevent="filter">
            <input v-model="filters.search" class="rounded-md border bg-transparent px-3 py-2 text-sm" placeholder="Order/customer/phone">
            <select v-model="filters.payment_status" class="rounded-md border bg-background px-3 py-2 text-sm"><option value="">Payment</option><option value="unpaid">Unpaid</option><option value="paid">Paid</option><option value="failed">Failed</option></select>
            <select v-model="filters.order_status" class="rounded-md border bg-background px-3 py-2 text-sm"><option value="">Order status</option><option value="pending">Pending</option><option value="cancelled">Cancelled</option><option value="completed">Completed</option></select>
            <select v-model="filters.delivery_method" class="rounded-md border bg-background px-3 py-2 text-sm"><option value="">Fulfillment</option><option value="delivery">Delivery</option><option value="pickup">Pickup</option></select>
            <button class="rounded-md border px-3 py-2 text-sm">Filter</button>
        </form>
        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full min-w-[1000px] text-sm">
                <thead class="bg-muted/40 text-left"><tr><th class="p-3">Order</th><th class="p-3">Customer</th><th class="p-3">Phone</th><th class="p-3">Total</th><th class="p-3">Payment</th><th class="p-3">Order</th><th class="p-3">Fulfillment</th><th class="p-3">Expires</th><th class="p-3 text-right">Actions</th></tr></thead>
                <tbody class="divide-y">
                    <tr v-for="order in orders.data" :key="order.id">
                        <td class="p-3 font-medium">{{ order.order_number }}</td><td class="p-3">{{ order.customer_name }}</td><td class="p-3">{{ order.customer_phone }}</td><td class="p-3">{{ order.currency }} {{ order.total }}</td><td class="p-3">{{ order.payment_status }}</td><td class="p-3">{{ order.order_status }}</td><td class="p-3">{{ order.delivery_method }} / {{ order.fulfillment_status }}</td><td class="p-3">{{ order.reservation_expires_at ?? '-' }}</td><td class="p-3 text-right"><Link class="rounded border px-2 py-1" :href="backendPath(`/orders/${order.id}`)">View</Link></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
