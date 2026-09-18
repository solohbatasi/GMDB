<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Archive, BookOpen, FolderGit2, LayoutGrid, LibraryBig, MapPin, ReceiptText, Tags } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const dashboardNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
];

const catalogNavItems: NavItem[] = [
    {
        title: 'Books',
        href: '/books',
        icon: LibraryBig,
    },
    {
        title: 'Categories',
        href: '/book-categories',
        icon: Tags,
    },
];

const inventoryNavItems: NavItem[] = [
    {
        title: 'Inventory',
        href: '/inventory',
        icon: Archive,
    },
    {
        title: 'Stock Movements',
        href: '/inventory/movements',
        icon: BookOpen,
    },
    {
        title: 'Pickup Locations',
        href: '/pickup-locations',
        icon: MapPin,
    },
];

const salesNavItems: NavItem[] = [
    {
        title: 'Orders',
        href: '/orders',
        icon: ReceiptText,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
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
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
