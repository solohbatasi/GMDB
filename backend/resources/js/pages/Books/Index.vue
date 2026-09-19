<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { backendPath } from '@/lib/backendPath';

type Category = { id: number; name: string };
type Inventory = { sku: string; available_quantity: number; stock_status: string; reorder_level: number; track_stock: boolean };
type Book = {
    id: number; title: string; slug: string; author: string; publisher?: string | null; isbn?: string | null;
    short_description?: string | null; description?: string | null; cover_url?: string | null;
    price: string | number; compare_price?: string | number | null; currency: string; featured: boolean; is_digital: boolean;
    status: string; published_at?: string | null; seo_title?: string | null; seo_description?: string | null;
    external_purchase_url?: string | null; book_category_id?: number | null; category?: Category | null; inventory_item?: Inventory | null;
};
type Page<T> = { data: T[] };

const props = defineProps<{ books: Page<Book>; categories: Category[]; statuses: string[]; filters: Record<string, string> }>();
const dialog = ref<HTMLDialogElement | null>(null);
const editing = ref<Book | null>(null);
const coverPreview = ref<string | null>(null);
const filters = ref({ ...props.filters });

const form = useForm({
    title: '', slug: '', book_category_id: '', author: '', publisher: '', isbn: '', short_description: '', description: '',
    cover: null as File | null, price: 0, compare_price: '', currency: 'KES', status: 'draft', featured: false, is_digital: false,
    published_at: '', seo_title: '', seo_description: '', external_purchase_url: '', sku: '', opening_quantity: 0, reorder_level: 5, track_stock: true,
});

const modalTitle = computed(() => editing.value ? 'Edit Book' : 'Add Book');

const openCreate = () => {
    editing.value = null;
    coverPreview.value = null;
    form.reset();
    form.clearErrors();
    form.currency = 'KES';
    form.status = 'draft';
    form.reorder_level = 5;
    form.track_stock = true;
    dialog.value?.showModal();
};

const openEdit = (book: Book) => {
    editing.value = book;
    coverPreview.value = book.cover_url ?? null;
    form.clearErrors();
    Object.assign(form, {
        title: book.title, slug: book.slug, book_category_id: book.book_category_id ?? '', author: book.author,
        publisher: book.publisher ?? '', isbn: book.isbn ?? '', short_description: book.short_description ?? '', description: book.description ?? '',
        cover: null, price: book.price, compare_price: book.compare_price ?? '', currency: book.currency, status: book.status,
        featured: book.featured, is_digital: book.is_digital, published_at: book.published_at ? book.published_at.slice(0, 10) : '',
        seo_title: book.seo_title ?? '', seo_description: book.seo_description ?? '', external_purchase_url: book.external_purchase_url ?? '',
        sku: book.inventory_item?.sku ?? '', reorder_level: book.inventory_item?.reorder_level ?? 5, track_stock: book.inventory_item?.track_stock ?? true,
    });
    dialog.value?.showModal();
};

const chooseCover = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    form.cover = file;
    coverPreview.value = file ? URL.createObjectURL(file) : editing.value?.cover_url ?? null;
};

const submit = () => {
    const options = { preserveScroll: true, forceFormData: true, onSuccess: () => dialog.value?.close() };
    editing.value ? form.post(backendPath(`/books/${editing.value.id}?_method=PUT`), options) : form.post(backendPath('/books'), options);
};

const filter = () => router.get(backendPath('/books'), filters.value, { preserveState: true, replace: true });
const hideBook = (book: Book) => router.delete(backendPath(`/books/${book.id}`), { preserveScroll: true });
</script>

<template>
    <Head title="Books" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold">Books</h1>
                <p class="text-muted-foreground text-sm">Manage catalogue records and opening inventory.</p>
            </div>
            <button class="rounded-md bg-primary px-3 py-2 text-sm text-primary-foreground" @click="openCreate">Add Book</button>
        </div>

        <form class="grid gap-2 md:grid-cols-5" @submit.prevent="filter">
            <input v-model="filters.search" class="rounded-md border bg-transparent px-3 py-2 text-sm" placeholder="Title">
            <input v-model="filters.author" class="rounded-md border bg-transparent px-3 py-2 text-sm" placeholder="Author">
            <select v-model="filters.category" class="rounded-md border bg-background px-3 py-2 text-sm">
                <option value="">All categories</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
            <select v-model="filters.status" class="rounded-md border bg-background px-3 py-2 text-sm">
                <option value="">All statuses</option>
                <option v-for="status in statuses" :key="status" :value="status">{{ status }}</option>
            </select>
            <button class="rounded-md border px-3 py-2 text-sm">Filter</button>
        </form>

        <div class="overflow-x-auto rounded-lg border">
            <table class="w-full min-w-[980px] text-sm">
                <thead class="bg-muted/40 text-left">
                    <tr><th class="p-3">Book</th><th class="p-3">Category</th><th class="p-3">SKU</th><th class="p-3">Price</th><th class="p-3">Stock</th><th class="p-3">Featured</th><th class="p-3">Status</th><th class="p-3 text-right">Actions</th></tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="book in books.data" :key="book.id">
                        <td class="flex items-center gap-3 p-3">
                            <img v-if="book.cover_url" :src="book.cover_url" class="h-14 w-10 rounded object-cover" :alt="book.title">
                            <div v-else class="h-14 w-10 rounded bg-muted" />
                            <div><div class="font-medium">{{ book.title }}</div><div class="text-muted-foreground">{{ book.author }}</div></div>
                        </td>
                        <td class="p-3">{{ book.category?.name ?? 'Uncategorized' }}</td>
                        <td class="p-3">{{ book.inventory_item?.sku ?? '-' }}</td>
                        <td class="p-3">{{ book.currency }} {{ book.price }}</td>
                        <td class="p-3">{{ book.inventory_item ? `${book.inventory_item.available_quantity} · ${book.inventory_item.stock_status}` : 'Digital/none' }}</td>
                        <td class="p-3">{{ book.featured ? 'Yes' : 'No' }}</td>
                        <td class="p-3">{{ book.status }}</td>
                        <td class="space-x-2 p-3 text-right"><button class="rounded border px-2 py-1" @click="openEdit(book)">Edit</button><button class="rounded border px-2 py-1" @click="hideBook(book)">Hide</button></td>
                    </tr>
                </tbody>
            </table>
        </div>

        <dialog ref="dialog" class="w-full max-w-4xl rounded-lg border bg-background p-0 text-foreground shadow-xl backdrop:bg-black/40">
            <form class="space-y-4 p-5" @submit.prevent="submit">
                <h2 class="text-lg font-semibold">{{ modalTitle }}</h2>
                <div class="grid gap-4 md:grid-cols-[160px_1fr]">
                    <div>
                        <img v-if="coverPreview" :src="coverPreview" class="mb-2 aspect-[2/3] w-full rounded object-cover" alt="">
                        <input type="file" accept="image/png,image/jpeg,image/webp" @change="chooseCover">
                        <div v-if="form.errors.cover" class="text-sm text-red-600">{{ form.errors.cover }}</div>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <input v-model="form.title" class="rounded-md border bg-transparent px-3 py-2" placeholder="Title">
                        <input v-model="form.slug" class="rounded-md border bg-transparent px-3 py-2" placeholder="Slug">
                        <select v-model="form.book_category_id" class="rounded-md border bg-background px-3 py-2"><option value="">Category</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option></select>
                        <input v-model="form.author" class="rounded-md border bg-transparent px-3 py-2" placeholder="Author">
                        <input v-model="form.publisher" class="rounded-md border bg-transparent px-3 py-2" placeholder="Publisher">
                        <input v-model="form.isbn" class="rounded-md border bg-transparent px-3 py-2" placeholder="ISBN">
                        <input v-model.number="form.price" type="number" min="0" step="0.01" class="rounded-md border bg-transparent px-3 py-2" placeholder="Price">
                        <input v-model="form.compare_price" type="number" min="0" step="0.01" class="rounded-md border bg-transparent px-3 py-2" placeholder="Compare price">
                        <input v-model="form.currency" maxlength="3" class="rounded-md border bg-transparent px-3 py-2" placeholder="Currency">
                        <select v-model="form.status" class="rounded-md border bg-background px-3 py-2"><option v-for="status in statuses" :key="status" :value="status">{{ status }}</option></select>
                        <input v-model="form.published_at" type="date" class="rounded-md border bg-transparent px-3 py-2">
                        <input v-model="form.external_purchase_url" class="rounded-md border bg-transparent px-3 py-2" placeholder="External purchase URL">
                        <textarea v-model="form.short_description" class="md:col-span-2 rounded-md border bg-transparent px-3 py-2" placeholder="Short description" />
                        <textarea v-model="form.description" class="md:col-span-2 rounded-md border bg-transparent px-3 py-2" placeholder="Full description" />
                        <input v-model="form.seo_title" class="rounded-md border bg-transparent px-3 py-2" placeholder="SEO title">
                        <input v-model="form.seo_description" class="rounded-md border bg-transparent px-3 py-2" placeholder="SEO description">
                        <label class="flex items-center gap-2 text-sm"><input v-model="form.featured" type="checkbox"> Featured</label>
                        <label class="flex items-center gap-2 text-sm"><input v-model="form.is_digital" type="checkbox"> Digital</label>
                        <template v-if="!form.is_digital">
                            <input v-model="form.sku" class="rounded-md border bg-transparent px-3 py-2" placeholder="SKU">
                            <input v-if="!editing" v-model.number="form.opening_quantity" type="number" min="0" class="rounded-md border bg-transparent px-3 py-2" placeholder="Opening quantity">
                            <input v-model.number="form.reorder_level" type="number" min="0" class="rounded-md border bg-transparent px-3 py-2" placeholder="Reorder level">
                            <label class="flex items-center gap-2 text-sm"><input v-model="form.track_stock" type="checkbox"> Track stock</label>
                        </template>
                    </div>
                </div>
                <div v-if="Object.keys(form.errors).length" class="text-sm text-red-600">Please correct the highlighted fields.</div>
                <div class="flex justify-end gap-2"><button type="button" class="rounded-md border px-3 py-2" @click="dialog?.close()">Cancel</button><button class="rounded-md bg-primary px-3 py-2 text-primary-foreground" :disabled="form.processing">Save</button></div>
            </form>
        </dialog>
    </div>
</template>
