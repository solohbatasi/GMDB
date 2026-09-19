<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { backendPath } from '@/lib/backendPath';

type Payment = {
    id: number;
    created_at: string;
    provider: string;
    method: string;
    source?: string | null;
    amount: string;
    currency: string;
    payer_phone?: string | null;
    status: string;
    channel_id?: string | null;
    payhero_reference?: string | null;
    provider_reference?: string | null;
    result_description?: string | null;
    order?: { id: number; order_number: string; customer_name: string; customer_email: string; customer_phone: string } | null;
};
type Page<T> = { data: T[] };

const props = defineProps<{
    payments: Page<Payment>;
    filters: Record<string, string | boolean>;
    payheroChannel: { channel_id?: string | null; provider?: string | null };
}>();

const filters = ref({ ...props.filters });
const filter = () => router.get(backendPath('/payments'), filters.value, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Payments" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Payments</h1>
                <p class="text-muted-foreground text-sm">PayHero attempts, confirmations, failures, and review items.</p>
            </div>
            <div class="rounded-md border px-3 py-2 text-right text-xs">
                <div class="font-medium">PayHero Channel</div>
                <div class="text-muted-foreground">{{ payheroChannel.provider ?? '-' }} / {{ payheroChannel.channel_id ?? 'not configured' }}</div>
            </div>
        </div>

        <form class="grid gap-2 md:grid-cols-5" @submit.prevent="filter">
            <input v-model="filters.search" class="rounded-md border bg-transparent px-3 py-2 text-sm" placeholder="Order/customer/reference">
            <select v-model="filters.status" class="rounded-md border bg-background px-3 py-2 text-sm">
                <option value="">Status</option>
                <option value="pending">Pending</option>
                <option value="paid">Paid</option>
                <option value="failed">Failed</option>
                <option value="cancelled">Cancelled</option>
                <option value="review_required">Review required</option>
            </select>
            <select v-model="filters.source" class="rounded-md border bg-background px-3 py-2 text-sm">
                <option value="">Source</option>
                <option value="stk">STK</option>
                <option value="paybill">PayBill</option>
                <option value="bank">Bank</option>
            </select>
            <label class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm"><input v-model="filters.review_required" type="checkbox"> Review required</label>
            <button class="rounded-md border px-3 py-2 text-sm">Filter</button>
        </form>

        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full min-w-[1200px] text-sm">
                <thead class="bg-muted/40 text-left">
                    <tr><th class="p-3">Date</th><th class="p-3">Order</th><th class="p-3">Customer</th><th class="p-3">Method</th><th class="p-3">Phone</th><th class="p-3">Amount</th><th class="p-3">Status</th><th class="p-3">Channel</th><th class="p-3">PayHero Ref</th><th class="p-3">Provider Ref</th><th class="p-3">Reason</th></tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="payment in payments.data" :key="payment.id" :class="payment.status === 'review_required' ? 'bg-amber-50 dark:bg-amber-950/20' : ''">
                        <td class="p-3">{{ payment.created_at }}</td>
                        <td class="p-3"><Link v-if="payment.order" class="underline" :href="backendPath(`/orders/${payment.order.id}`)">{{ payment.order.order_number }}</Link><span v-else>-</span></td>
                        <td class="p-3">{{ payment.order?.customer_name ?? '-' }}</td>
                        <td class="p-3">{{ payment.source ?? payment.method }}</td>
                        <td class="p-3">{{ payment.payer_phone ?? '-' }}</td>
                        <td class="p-3">{{ payment.currency }} {{ payment.amount }}</td>
                        <td class="p-3 font-medium">{{ payment.status === 'review_required' ? 'Payment Requires Review' : payment.status }}</td>
                        <td class="p-3">{{ payment.channel_id ?? '-' }}</td>
                        <td class="p-3">{{ payment.payhero_reference ?? '-' }}</td>
                        <td class="p-3">{{ payment.provider_reference ?? '-' }}</td>
                        <td class="p-3">{{ payment.result_description ?? '-' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
