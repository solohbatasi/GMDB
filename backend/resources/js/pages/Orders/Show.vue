<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{ order: any }>();
</script>

<template>
    <Head :title="order.order_number" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <Link href="/orders" class="text-sm underline">Back to orders</Link>
        <div class="rounded-lg border p-4">
            <h1 class="text-2xl font-semibold">{{ order.order_number }}</h1>
            <p class="text-muted-foreground text-sm">Payment {{ order.payment_status }} · Order {{ order.order_status }} · Fulfillment {{ order.fulfillment_status }}</p>
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-lg border p-4"><h2 class="mb-3 font-medium">Customer</h2><p>{{ order.customer_name }}</p><p>{{ order.customer_email }}</p><p>{{ order.customer_phone }}</p></section>
            <section class="rounded-lg border p-4"><h2 class="mb-3 font-medium">Fulfillment</h2><p>{{ order.delivery_method }}</p><p v-if="order.delivery_method === 'delivery'">{{ order.shipping_address }}, {{ order.shipping_city }}, {{ order.shipping_county }}</p><p v-else>{{ order.pickup_location?.name }} · {{ order.pickup_location?.address }}</p><p>Reservation expires: {{ order.reservation_expires_at ?? '-' }}</p></section>
        </div>
        <section class="rounded-lg border">
            <div class="border-b p-4 font-medium">Items</div>
            <table class="w-full text-sm"><tbody class="divide-y"><tr v-for="item in order.items" :key="item.id"><td class="p-3">{{ item.title_snapshot }}</td><td class="p-3">{{ item.sku_snapshot ?? '-' }}</td><td class="p-3">{{ item.quantity }} × {{ item.price_snapshot }}</td><td class="p-3 text-right">{{ item.line_total }}</td></tr></tbody></table>
            <div class="border-t p-4 text-right font-medium">Total {{ order.currency }} {{ order.total }}</div>
        </section>
        <section class="rounded-lg border">
            <div class="border-b p-4 font-medium">Status History</div>
            <div v-for="history in order.histories" :key="history.id" class="border-b p-4 text-sm">{{ history.created_at }} · {{ history.type }} · {{ history.from_status ?? '-' }} → {{ history.to_status }}<div class="text-muted-foreground">{{ history.notes }}</div></div>
        </section>
    </div>
</template>
