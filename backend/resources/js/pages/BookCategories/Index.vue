<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

type Category = { id: number; name: string; slug: string; description?: string | null; is_active: boolean; sort_order: number; books_count: number };
type Page<T> = { data: T[] };

const props = defineProps<{ categories: Page<Category>; filters: { search?: string } }>();

const dialog = ref<HTMLDialogElement | null>(null);
const editing = ref<Category | null>(null);
const search = ref(props.filters.search ?? '');
const form = useForm({ name: '', slug: '', description: '', is_active: true, sort_order: 0 });

const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    dialog.value?.showModal();
};

const openEdit = (category: Category) => {
    editing.value = category;
    form.clearErrors();
    form.name = category.name;
    form.slug = category.slug;
    form.description = category.description ?? '';
    form.is_active = category.is_active;
    form.sort_order = category.sort_order;
    dialog.value?.showModal();
};

const submit = () => {
    const options = { preserveScroll: true, onSuccess: () => dialog.value?.close() };
    editing.value ? form.put(`/book-categories/${editing.value.id}`, options) : form.post('/book-categories', options);
};

const deactivate = (category: Category) => {
    router.delete(`/book-categories/${category.id}`, { preserveScroll: true });
};

const filter = () => router.get('/book-categories', { search: search.value }, { preserveState: true, replace: true });
</script>

<template>
    <Head title="Book Categories" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Categories</h1>
                <p class="text-muted-foreground text-sm">Organize the bookstore catalogue.</p>
            </div>
            <button class="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground" @click="openCreate">Add Category</button>
        </div>

        <form class="flex gap-2" @submit.prevent="filter">
            <input v-model="search" class="h-9 rounded-md border bg-transparent px-3 text-sm" placeholder="Search categories">
            <button class="rounded-md border px-3 text-sm">Filter</button>
        </form>

        <div class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-muted/40 text-left">
                    <tr>
                        <th class="p-3">Name</th>
                        <th class="p-3">Slug</th>
                        <th class="p-3">Books</th>
                        <th class="p-3">Order</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="category in categories.data" :key="category.id">
                        <td class="p-3 font-medium">{{ category.name }}</td>
                        <td class="p-3">{{ category.slug }}</td>
                        <td class="p-3">{{ category.books_count }}</td>
                        <td class="p-3">{{ category.sort_order }}</td>
                        <td class="p-3">{{ category.is_active ? 'Active' : 'Inactive' }}</td>
                        <td class="space-x-2 p-3 text-right">
                            <button class="rounded border px-2 py-1" @click="openEdit(category)">Edit</button>
                            <button class="rounded border px-2 py-1" @click="deactivate(category)">Deactivate</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <dialog ref="dialog" class="w-full max-w-xl rounded-lg border bg-background p-0 text-foreground shadow-xl backdrop:bg-black/40">
            <form class="space-y-4 p-5" @submit.prevent="submit">
                <h2 class="text-lg font-semibold">{{ editing ? 'Edit Category' : 'Add Category' }}</h2>
                <div class="grid gap-3">
                    <input v-model="form.name" class="rounded-md border bg-transparent px-3 py-2" placeholder="Name">
                    <div v-if="form.errors.name" class="text-sm text-red-600">{{ form.errors.name }}</div>
                    <input v-model="form.slug" class="rounded-md border bg-transparent px-3 py-2" placeholder="Slug">
                    <div v-if="form.errors.slug" class="text-sm text-red-600">{{ form.errors.slug }}</div>
                    <textarea v-model="form.description" class="rounded-md border bg-transparent px-3 py-2" placeholder="Description" />
                    <input v-model.number="form.sort_order" type="number" min="0" class="rounded-md border bg-transparent px-3 py-2" placeholder="Sort order">
                    <label class="flex items-center gap-2 text-sm"><input v-model="form.is_active" type="checkbox"> Active</label>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" class="rounded-md border px-3 py-2" @click="dialog?.close()">Cancel</button>
                    <button class="rounded-md bg-primary px-3 py-2 text-primary-foreground" :disabled="form.processing">Save</button>
                </div>
            </form>
        </dialog>
    </div>
</template>
