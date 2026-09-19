<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { backendPath } from '@/lib/backendPath';

defineProps<{ order: any }>();
</script>

<template>
    <Head :title="order.order_number" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <Link :href="backendPath('/orders')" class="text-sm underline">Back to orders</Link>
        <div class="rounded-lg border p-4">
            <h1 class="text-2xl font-semibold">{{ order.order_number }}</h1>
            <p class="text-muted-foreground text-sm">Payment {{ order.payment_status }} / Order {{ order.order_status }} / Fulfillment {{ order.fulfillment_status }}</p>
        </div>
        <div class="grid gap-4 lg:grid-cols-2">
            <section class="rounded-lg border p-4"><h2 class="mb-3 font-medium">Customer</h2><p>{{ order.customer_name }}</p><p>{{ order.customer_email }}</p><p>{{ order.customer_phone }}</p></section>
            <section class="rounded-lg border p-4"><h2 class="mb-3 font-medium">Fulfillment</h2><p>{{ order.delivery_method }}</p><p v-if="order.delivery_method === 'delivery'">{{ order.shipping_address }}, {{ order.shipping_city }}, {{ order.shipping_county }}</p><p v-else>{{ order.pickup_location?.name }} / {{ order.pickup_location?.address }}</p><p>Reservation expires: {{ order.reservation_expires_at ?? '-' }}</p></section>
        </div>
        <section class="rounded-lg border">
            <div class="border-b p-4 font-medium">Items</div>
            <table class="w-full text-sm"><tbody class="divide-y"><tr v-for="item in order.items" :key="item.id"><td class="p-3">{{ item.title_snapshot }}</td><td class="p-3">{{ item.sku_snapshot ?? '-' }}</td><td class="p-3">{{ item.quantity }} x {{ item.price_snapshot }}</td><td class="p-3 text-right">{{ item.line_total }}</td></tr></tbody></table>
            <div class="border-t p-4 text-right font-medium">Total {{ order.currency }} {{ order.total }}</div>
        </section>
        <section class="rounded-lg border">
            <div class="border-b p-4 font-medium">Payments</div>
            <div v-for="(payment, index) in order.payments" :key="payment.id" class="border-b p-4 text-sm">
                <div class="font-medium">Attempt #{{ index + 1 }} / {{ payment.method }} / {{ payment.status === 'review_required' ? 'Payment Requires Review' : payment.status }}</div>
                <div class="text-muted-foreground">{{ payment.created_at }} / {{ payment.currency }} {{ payment.amount }} / {{ payment.provider }} / {{ payment.channel ?? '-' }}</div>
                <div class="text-muted-foreground">Reference: {{ payment.provider_reference ?? payment.external_reference }}</div>
                <div v-if="payment.result_description" class="mt-1">{{ payment.result_description }}</div>
            </div>
            <div v-if="!order.payments?.length" class="p-4 text-sm text-muted-foreground">No payment attempts yet.</div>
        </section>
        <section class="rounded-lg border">
            <div class="border-b p-4 font-medium">Status History</div>
            <div v-for="history in order.histories" :key="history.id" class="border-b p-4 text-sm">{{ history.created_at }} / {{ history.type }} / {{ history.from_status ?? '-' }} -> {{ history.to_status }}<div class="text-muted-foreground">{{ history.notes }}</div></div>
        </section>
    </div>
</template>
