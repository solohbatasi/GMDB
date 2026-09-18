<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type Location = { id: number; name: string; address: string; city?: string | null; county?: string | null; instructions?: string | null; is_active: boolean; sort_order: number };
type Page<T> = { data: T[] };

defineProps<{ locations: Page<Location> }>();
const dialog = ref<HTMLDialogElement | null>(null);
const editing = ref<Location | null>(null);
const form = useForm({ name: '', address: '', city: '', county: '', instructions: '', is_active: true, sort_order: 0 });

const openCreate = () => { editing.value = null; form.reset(); form.is_active = true; dialog.value?.showModal(); };
const openEdit = (location: Location) => {
    editing.value = location;
    form.name = location.name; form.address = location.address; form.city = location.city ?? ''; form.county = location.county ?? ''; form.instructions = location.instructions ?? ''; form.is_active = location.is_active; form.sort_order = location.sort_order;
    dialog.value?.showModal();
};
const submit = () => editing.value ? form.put(`/pickup-locations/${editing.value.id}`, { preserveScroll: true, onSuccess: () => dialog.value?.close() }) : form.post('/pickup-locations', { preserveScroll: true, onSuccess: () => dialog.value?.close() });
const deactivate = (location: Location) => router.delete(`/pickup-locations/${location.id}`, { preserveScroll: true });
</script>

<template>
    <Head title="Pickup Locations" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between"><div><h1 class="text-2xl font-semibold">Pickup Locations</h1><p class="text-muted-foreground text-sm">Locations available during guest checkout.</p></div><button class="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground" @click="openCreate">Add Location</button></div>
        <div class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm"><thead class="bg-muted/40 text-left"><tr><th class="p-3">Name</th><th class="p-3">Address</th><th class="p-3">Order</th><th class="p-3">Status</th><th class="p-3 text-right">Actions</th></tr></thead><tbody class="divide-y"><tr v-for="location in locations.data" :key="location.id"><td class="p-3 font-medium">{{ location.name }}</td><td class="p-3">{{ location.address }}</td><td class="p-3">{{ location.sort_order }}</td><td class="p-3">{{ location.is_active ? 'Active' : 'Inactive' }}</td><td class="space-x-2 p-3 text-right"><button class="rounded border px-2 py-1" @click="openEdit(location)">Edit</button><button class="rounded border px-2 py-1" @click="deactivate(location)">Deactivate</button></td></tr></tbody></table>
        </div>
        <dialog ref="dialog" class="w-full max-w-xl rounded-lg border bg-background p-0 text-foreground shadow-xl backdrop:bg-black/40">
            <form class="space-y-4 p-5" @submit.prevent="submit">
                <h2 class="text-lg font-semibold">{{ editing ? 'Edit Pickup Location' : 'Add Pickup Location' }}</h2>
                <input v-model="form.name" class="w-full rounded-md border bg-transparent px-3 py-2" placeholder="Name">
                <textarea v-model="form.address" class="w-full rounded-md border bg-transparent px-3 py-2" placeholder="Address" />
                <div class="grid gap-3 md:grid-cols-2"><input v-model="form.city" class="rounded-md border bg-transparent px-3 py-2" placeholder="City"><input v-model="form.county" class="rounded-md border bg-transparent px-3 py-2" placeholder="County"></div>
                <textarea v-model="form.instructions" class="w-full rounded-md border bg-transparent px-3 py-2" placeholder="Instructions" />
                <input v-model.number="form.sort_order" type="number" min="0" class="w-full rounded-md border bg-transparent px-3 py-2" placeholder="Sort order">
                <label class="flex items-center gap-2 text-sm"><input v-model="form.is_active" type="checkbox"> Active</label>
                <div class="flex justify-end gap-2"><button type="button" class="rounded-md border px-3 py-2" @click="dialog?.close()">Cancel</button><button class="rounded-md bg-primary px-3 py-2 text-primary-foreground">Save</button></div>
            </form>
        </dialog>
    </div>
</template>
