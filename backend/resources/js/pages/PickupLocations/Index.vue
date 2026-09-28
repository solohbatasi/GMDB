<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { backendPath } from '@/lib/backendPath';

type Location = {
    id: number;
    name: string;
    address: string;
    city?: string | null;
    county?: string | null;
    instructions?: string | null;
    map_url?: string | null;
    latitude?: number | null;
    longitude?: number | null;
    is_active: boolean;
    sort_order: number;
};
type Page<T> = { data: T[] };

defineProps<{ locations: Page<Location> }>();
const dialog = ref<HTMLDialogElement | null>(null);
const editing = ref<Location | null>(null);
const locating = ref(false);
const locationMessage = ref('');
const locationError = ref('');
const form = useForm({
    name: '',
    address: '',
    city: '',
    county: '',
    instructions: '',
    map_url: '',
    latitude: '',
    longitude: '',
    is_active: true,
    sort_order: 0,
});

const resetLocationStatus = () => {
    locationMessage.value = '';
    locationError.value = '';
};
const openCreate = () => {
    editing.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    resetLocationStatus();
    dialog.value?.showModal();
};
const openEdit = (location: Location) => {
    editing.value = location;
    form.clearErrors();
    form.name = location.name;
    form.address = location.address;
    form.city = location.city ?? '';
    form.county = location.county ?? '';
    form.instructions = location.instructions ?? '';
    form.map_url = location.map_url ?? '';
    form.latitude = location.latitude?.toString() ?? '';
    form.longitude = location.longitude?.toString() ?? '';
    form.is_active = location.is_active;
    form.sort_order = location.sort_order;
    resetLocationStatus();
    dialog.value?.showModal();
};
const submit = () =>
    editing.value
        ? form.put(backendPath(`/pickup-locations/${editing.value.id}`), {
              preserveScroll: true,
              onSuccess: () => dialog.value?.close(),
          })
        : form.post(backendPath('/pickup-locations'), {
              preserveScroll: true,
              onSuccess: () => dialog.value?.close(),
          });
const deactivate = (location: Location) =>
    router.delete(backendPath(`/pickup-locations/${location.id}`), {
        preserveScroll: true,
    });

const useCurrentLocation = () => {
    resetLocationStatus();

    if (!navigator.geolocation) {
        locationError.value =
            'Location access is not supported by this browser.';
        return;
    }

    locating.value = true;
    navigator.geolocation.getCurrentPosition(
        ({ coords }) => {
            const latitude = coords.latitude.toFixed(7);
            const longitude = coords.longitude.toFixed(7);
            form.latitude = latitude;
            form.longitude = longitude;
            form.map_url = `https://www.google.com/maps/dir/?api=1&destination=${latitude},${longitude}`;
            locationMessage.value = `Current location captured (accuracy approximately ${Math.round(coords.accuracy)} metres).`;
            locating.value = false;
        },
        (error) => {
            const messages: Record<number, string> = {
                1: 'Location permission was denied. Allow location access or enter the coordinates manually.',
                2: 'Your current location could not be determined. Enter the coordinates manually.',
                3: 'Location detection timed out. Please try again or enter the coordinates manually.',
            };
            locationError.value =
                messages[error.code] ??
                'Your current location could not be determined.';
            locating.value = false;
        },
        { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
    );
};
</script>

<template>
    <Head title="Pickup Locations" />
    <div class="flex flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold">Pickup Locations</h1>
                <p class="text-muted-foreground text-sm">
                    Locations available during guest checkout and in the website
                    directions link.
                </p>
            </div>
            <button
                class="bg-primary text-primary-foreground rounded-md px-3 py-2 text-sm"
                @click="openCreate"
            >
                Add Location
            </button>
        </div>
        <div class="overflow-hidden rounded-lg border">
            <table class="w-full text-sm">
                <thead class="bg-muted/40 text-left">
                    <tr>
                        <th class="p-3">Name</th>
                        <th class="p-3">Address</th>
                        <th class="p-3">Order</th>
                        <th class="p-3">Status</th>
                        <th class="p-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="location in locations.data" :key="location.id">
                        <td class="p-3 font-medium">{{ location.name }}</td>
                        <td class="p-3">{{ location.address }}</td>
                        <td class="p-3">{{ location.sort_order }}</td>
                        <td class="p-3">
                            {{ location.is_active ? 'Active' : 'Inactive' }}
                        </td>
                        <td class="space-x-2 p-3 text-right">
                            <button
                                class="rounded border px-2 py-1"
                                @click="openEdit(location)"
                            >
                                Edit</button
                            ><button
                                class="rounded border px-2 py-1"
                                @click="deactivate(location)"
                            >
                                Deactivate
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <dialog
            ref="dialog"
            class="bg-background text-foreground w-full max-w-xl rounded-lg border p-0 shadow-xl backdrop:bg-black/40"
        >
            <form class="space-y-4 p-5" @submit.prevent="submit">
                <h2 class="text-lg font-semibold">
                    {{
                        editing ? 'Edit Pickup Location' : 'Add Pickup Location'
                    }}
                </h2>
                <input
                    v-model="form.name"
                    class="w-full rounded-md border bg-transparent px-3 py-2"
                    placeholder="Name"
                />
                <textarea
                    v-model="form.address"
                    class="w-full rounded-md border bg-transparent px-3 py-2"
                    placeholder="Address"
                />
                <div class="grid gap-3 md:grid-cols-2">
                    <input
                        v-model="form.city"
                        class="rounded-md border bg-transparent px-3 py-2"
                        placeholder="City"
                    /><input
                        v-model="form.county"
                        class="rounded-md border bg-transparent px-3 py-2"
                        placeholder="County"
                    />
                </div>
                <textarea
                    v-model="form.instructions"
                    class="w-full rounded-md border bg-transparent px-3 py-2"
                    placeholder="Instructions"
                />
                <div class="space-y-2 rounded-md border p-4">
                    <div>
                        <label for="pickup-map-url" class="text-sm font-medium"
                            >Map URL</label
                        >
                        <input
                            id="pickup-map-url"
                            v-model="form.map_url"
                            type="url"
                            class="mt-1 w-full rounded-md border bg-transparent px-3 py-2"
                            placeholder="https://maps.app.goo.gl/..."
                        />
                        <p class="text-muted-foreground mt-1 text-xs">
                            Paste a Google Maps share link. When omitted, saved
                            coordinates are used.
                        </p>
                        <p
                            v-if="form.errors.map_url"
                            class="text-destructive mt-1 text-xs"
                        >
                            {{ form.errors.map_url }}
                        </p>
                    </div>
                    <div class="grid gap-3 md:grid-cols-2">
                        <div>
                            <label
                                for="pickup-latitude"
                                class="text-sm font-medium"
                                >Latitude</label
                            >
                            <input
                                id="pickup-latitude"
                                v-model="form.latitude"
                                type="number"
                                step="any"
                                min="-90"
                                max="90"
                                class="mt-1 w-full rounded-md border bg-transparent px-3 py-2"
                                placeholder="-1.286389"
                            />
                            <p
                                v-if="form.errors.latitude"
                                class="text-destructive mt-1 text-xs"
                            >
                                {{ form.errors.latitude }}
                            </p>
                        </div>
                        <div>
                            <label
                                for="pickup-longitude"
                                class="text-sm font-medium"
                                >Longitude</label
                            >
                            <input
                                id="pickup-longitude"
                                v-model="form.longitude"
                                type="number"
                                step="any"
                                min="-180"
                                max="180"
                                class="mt-1 w-full rounded-md border bg-transparent px-3 py-2"
                                placeholder="36.817223"
                            />
                            <p
                                v-if="form.errors.longitude"
                                class="text-destructive mt-1 text-xs"
                            >
                                {{ form.errors.longitude }}
                            </p>
                        </div>
                    </div>
                    <button
                        type="button"
                        class="rounded-md border px-3 py-2 text-sm font-medium disabled:cursor-wait disabled:opacity-60"
                        :disabled="locating"
                        @click="useCurrentLocation"
                    >
                        {{
                            locating
                                ? 'Getting current location…'
                                : 'Use current location'
                        }}
                    </button>
                    <p v-if="locationMessage" class="text-xs text-emerald-600">
                        {{ locationMessage }}
                    </p>
                    <p v-if="locationError" class="text-destructive text-xs">
                        {{ locationError }}
                    </p>
                    <p class="text-muted-foreground text-xs">
                        Browser location access requires HTTPS or localhost and
                        your permission.
                    </p>
                </div>
                <input
                    v-model.number="form.sort_order"
                    type="number"
                    min="0"
                    class="w-full rounded-md border bg-transparent px-3 py-2"
                    placeholder="Sort order"
                />
                <label class="flex items-center gap-2 text-sm"
                    ><input v-model="form.is_active" type="checkbox" />
                    Active</label
                >
                <div class="flex justify-end gap-2">
                    <button
                        type="button"
                        class="rounded-md border px-3 py-2"
                        @click="dialog?.close()"
                    >
                        Cancel</button
                    ><button
                        class="bg-primary text-primary-foreground rounded-md px-3 py-2"
                    >
                        Save
                    </button>
                </div>
            </form>
        </dialog>
    </div>
</template>
