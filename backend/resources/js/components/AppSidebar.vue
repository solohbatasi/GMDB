<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Archive, Banknote, BookOpen, LayoutGrid, LibraryBig, MapPin, ReceiptText, Tags } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import { backendPath } from '@/lib/backendPath';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

const dashboardNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: backendPath('/dashboard'),
        icon: LayoutGrid,
    },
];

const catalogNavItems: NavItem[] = [
    {
        title: 'Books',
        href: backendPath('/books'),
        icon: LibraryBig,
    },
    {
        title: 'Categories',
        href: backendPath('/book-categories'),
        icon: Tags,
    },
];

const inventoryNavItems: NavItem[] = [
    {
        title: 'Inventory',
        href: backendPath('/inventory'),
        icon: Archive,
    },
    {
        title: 'Stock Movements',
        href: backendPath('/inventory/movements'),
        icon: BookOpen,
    },
    {
        title: 'Pickup Locations',
        href: backendPath('/pickup-locations'),
        icon: MapPin,
    },
];

const salesNavItems: NavItem[] = [
    {
        title: 'Orders',
        href: backendPath('/orders'),
        icon: ReceiptText,
    },
    {
        title: 'Payments',
        href: backendPath('/payments'),
        icon: Banknote,
    },
];

</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="backendPath('/dashboard')">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="dashboardNavItems" label="Dashboard" />
            <NavMain :items="catalogNavItems" label="Catalog" />
            <NavMain :items="inventoryNavItems" label="Inventory" />
            <NavMain :items="salesNavItems" label="Sales" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
