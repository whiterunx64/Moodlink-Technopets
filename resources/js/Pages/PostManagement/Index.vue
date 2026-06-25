<script setup lang="ts">
import type { FilterTab } from '@/Components/Filters/FilterTabs.vue';
import FilterTabs from '@/Components/Filters/FilterTabs.vue';
import PostCard from '@/Components/Posts/PostCard.vue';
import PostDetailModal from '@/Components/Posts/PostDetailModal.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePaginatorNav } from '@/composables/usePaginatorNav';
import { usePollingReload } from '@/composables/usePolling';
import type { Paginated, Post, PostFilters } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, toRef } from 'vue';

const props = defineProps<{
    posts: Paginated<Post>;
    filters: PostFilters;
}>();

usePollingReload(['posts']);

const currentSort = computed(() => props.filters.sort ?? 'latest');
const currentSortLabel = computed(() =>
    currentSort.value === 'oldest' ? 'Oldest first' : 'Latest first',
);

const sortIcon = computed(() =>
    currentSort.value === 'oldest' ? 'fa-arrow-up-wide-short' : 'fa-arrow-down-wide-short',
);

function toggleSort() {
    const next = currentSort.value === 'latest' ? 'oldest' : 'latest';
    const sortParam = next === 'latest' ? undefined : next;

    router.get(
        route('posts.index'),
        filterParams({ sort: sortParam, page: undefined }),
        { preserveState: true, replace: true },
    );
}

const activeFilter = computed<string>(() => {
    if (props.filters.status) return props.filters.status;
    if (props.filters.program) return props.filters.program;
    if (props.filters.mood) return props.filters.mood;
    return 'all';
});

const tabs: FilterTab[] = [
    { label: 'All', value: 'all' },
    {
        label: 'Flagged',
        value: 'flagged',
        badgeInactiveClass: 'bg-status-flagged-bg text-status-flagged',
    },
    {
        label: 'Safe',
        value: 'safe',
        badgeInactiveClass: 'bg-status-safe-bg text-status-safe',
    },
    {
        label: 'Reported',
        value: 'Reported',
        badgeInactiveClass: 'bg-status-reported-bg text-status-reported',
    },
    {
        label: 'Archives',
        value: 'Archives',
        badgeInactiveClass: 'bg-status-archives-bg text-status-archives',
    },
];

type FilterParams = Record<string, string | number | null | undefined>;

function filterParams(extra: FilterParams = {}): FilterParams {
    const params: FilterParams = {};
    const keys: Array<'status' | 'program' | 'mood'> = ['status', 'program', 'mood'];

    for (const key of keys) {
        if (props.filters[key]) params[key] = props.filters[key];
    }

    if (props.filters.sort && props.filters.sort !== 'latest') {
        params.sort = props.filters.sort;
    }

    return { ...params, ...extra };
}

function setFilter(filter: string) {
    const params: Record<string, string> = {};

    if (filter !== 'all') {
        params.status = filter;
    }

    if (currentSort.value !== 'latest') {
        params.sort = currentSort.value;
    }

    router.get(route('posts.index'), params, {
        preserveState: true,
        replace: true,
    });
}

const { pageNumbers } = usePaginatorNav(toRef(props, 'posts'));

function goToPage(page: number) {
    router.get(route('posts.index'), filterParams({ page }), {
        preserveState: true,
        replace: true,
    });
}

const selectedPost = ref<Post | null>(null);

function updateSelectedPostStatus(post: Post, nextStatus: Post['status']) {
    if (selectedPost.value?.id !== post.id) {
        return;
    }
    selectedPost.value = { ...selectedPost.value, status: nextStatus };
}

function toggleFlag(post: Post) {
    let routeName: string;
    let nextStatus: Post['status'];

    if (post.status === 'flagged') {
        routeName = 'posts.flag';
        nextStatus = 'safe';
    } else {
        routeName = 'posts.unflag';
        nextStatus = 'flagged';
    }

    router.patch(route(routeName, post.id), {},
        {
            preserveScroll: true,
            only: ['posts', 'filters', 'flash'],
            onSuccess: () => updateSelectedPostStatus(post, nextStatus),
        },
    );
}

function openModal(post: Post) {
    selectedPost.value = post;
}

function closeModal() {
    selectedPost.value = null;
}

function toggleFlagFromModal() {
    if (selectedPost.value) toggleFlag(selectedPost.value);
}
</script>

<template>

    <Head title="Posts" />

    <AdminLayout title="Posts">
        <!-- Bottom padding keeps the fixed pagination bar from covering cards -->
        <div class="space-y-5 pb-20">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <FilterTabs :model-value="activeFilter" :tabs="tabs" @update:model-value="setFilter" />

                <div class="bg-bg-surface border-border-light flex items-center rounded-xl border p-1">
                    <button type="button"
                        class="text-filter-inactive-text hover:text-filter-inactive-hover-text hover:bg-filter-inactive-hover-bg flex items-center gap-2 rounded-lg px-5 py-2 text-sm font-medium transition-all duration-150 select-none"
                        @click="toggleSort">
                        <i class="fas text-[10px]" :class="sortIcon" />
                        {{ currentSortLabel }}
                    </button>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <PostCard v-for="post in posts.data" :key="post.id" :post="post" @toggle-flag="toggleFlag(post)"
                    @view="openModal(post)" />

                <div v-if="posts.data.length === 0"
                    class="text-text-muted col-span-full flex flex-col items-center gap-3 py-20">
                    <p class="text-sm">No posts in this category</p>
                </div>
            </div>
        </div>

        <Pagination :fixed="true" :current-page="posts.current_page" :total-pages="posts.last_page"
            :page-numbers="pageNumbers" :range-start="posts.from ?? 0" :range-end="posts.to ?? 0" :total="posts.total"
            @update:current-page="goToPage" @prev="goToPage(posts.current_page - 1)"
            @next="goToPage(posts.current_page + 1)" />

        <PostDetailModal :post="selectedPost" :show="selectedPost !== null" @close="closeModal"
            @toggle-flag="toggleFlagFromModal" />
    </AdminLayout>
</template>