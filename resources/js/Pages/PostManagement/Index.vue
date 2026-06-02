<script setup lang="ts">
import { computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FilterTabs from '@/Components/Filters/FilterTabs.vue';
import PostCard from '@/Components/Posts/PostCard.vue';
import type { Post, PostFilters } from '@/types';
import type { FilterTab } from '@/Components/Filters/FilterTabs.vue';

const props = defineProps<{
    posts: Post[];
    filters: PostFilters;
}>();

/** Server-side filtering triggered via Inertia request */
const activeFilter = computed<string>(() => {
    const cap = (s: string) => s.charAt(0).toUpperCase() + s.slice(1);
    if (props.filters.status) return cap(props.filters.status);
    if (props.filters.section) return props.filters.section;
    if (props.filters.mood) return cap(props.filters.mood);
    return 'All';
});

const tabs: FilterTab[] = [
    { label: 'All', value: 'All' },
    { label: 'Flagged', value: 'Flagged', badgeInactiveClass: 'bg-status-flagged-bg text-status-flagged' },
    { label: 'Safe', value: 'Safe', badgeInactiveClass: 'bg-status-safe-bg text-status-safe' },
];

function setFilter(filter: string) {
    router.get(
        route('post-management.index'),
        filter === 'All' ? {} : { status: filter.toLowerCase() },
        { preserveState: true, replace: true },
    );
}

/** Inertia PATCH request to toggle post flag state */
function toggleFlag(post: Post) {
    router.patch(route('post-management.toggle-status', post.id), {}, {
        preserveScroll: true,
        only: ['posts', 'filters'],
    });
}
</script>

<template>

    <Head title="Posts" />

    <!-- Admin layout wrapper container -->
    <AdminLayout title="Posts">
        <div class="space-y-5">

            <!-- Server-driven filter tabs UI -->
            <FilterTabs :model-value="activeFilter" :tabs="tabs" @update:model-value="setFilter" />

            <!-- Responsive grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">

                <!-- Render post card items -->
                <PostCard v-for="post in posts" :key="post.id" :post="post" @toggle-flag="toggleFlag(post)" />

                <!-- Empty state when no posts -->
                <div v-if="posts.length === 0"
                    class="col-span-full py-20 flex flex-col items-center gap-3 text-text-muted">
                    <i class="fas fa-file-alt text-4xl opacity-20"></i>
                    <p class="text-sm">No posts in this category</p>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
