<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FilterTabs from '@/Components/Filters/FilterTabs.vue';
import PostCard from '@/Components/Posts/PostCard.vue';
import PostDetailModal from '@/Components/Posts/PostDetailModal.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import type { Paginated, Post, PostFilters } from '@/types';
import type { FilterTab } from '@/Components/Filters/FilterTabs.vue';

const props = defineProps<{
    posts: Paginated<Post>;
    filters: PostFilters;
}>();

// ── Sort toggle ───────────────────────────────────────────────────────────────
const currentSort = computed(() => props.filters.sort ?? 'latest');
const currentSortLabel = computed(() => currentSort.value === 'oldest' ? 'Oldest first' : 'Latest first');

function toggleSort() {
    const next = currentSort.value === 'latest' ? 'oldest' : 'latest';
    router.get(
        route('post-management.index'),
        filterParams({ sort: next === 'latest' ? undefined : next, page: undefined }),
        { preserveState: true, replace: true },
    );
}

// ── Filter tabs ───────────────────────────────────────────────────────────────
const activeFilter = computed<string>(() => {
    const cap = (s: string) => s.charAt(0).toUpperCase() + s.slice(1);
    if (props.filters.status)  return cap(props.filters.status);
    if (props.filters.section) return props.filters.section;
    if (props.filters.mood)    return cap(props.filters.mood);
    return 'All';
});

const tabs: FilterTab[] = [
    { label: 'All',     value: 'All' },
    { label: 'Flagged', value: 'Flagged', badgeInactiveClass: 'bg-status-flagged-bg text-status-flagged' },
    { label: 'Safe',    value: 'Safe',    badgeInactiveClass: 'bg-status-safe-bg text-status-safe' },
];

function filterParams(extra: Record<string, string | number | null | undefined> = {}): Record<string, string | number | null | undefined> {
    const p: Record<string, string | number | null | undefined> = {};
    if (props.filters.status)  p.status  = props.filters.status;
    if (props.filters.section) p.section = props.filters.section;
    if (props.filters.mood)    p.mood    = props.filters.mood;
    if (props.filters.sort && props.filters.sort !== 'latest') p.sort = props.filters.sort;
    return { ...p, ...extra };
}

function setFilter(filter: string) {
    const base = filter === 'All' ? {} : { status: filter.toLowerCase() };
    const sort = currentSort.value !== 'latest' ? { sort: currentSort.value } : {};
    router.get(
        route('post-management.index'),
        { ...base, ...sort },
        { preserveState: true, replace: true },
    );
}

// ── Pagination ────────────────────────────────────────────────────────────────
const pageNumbers = computed<(number | '…')[]>(() => {
    const total = props.posts.last_page;
    const cur   = props.posts.current_page;
    if (total <= 7) return Array.from({ length: total }, (_, i) => i + 1);
    const show   = new Set([1, total, cur, cur - 1, cur + 1].filter(p => p >= 1 && p <= total));
    const sorted = [...show].sort((a, b) => a - b);
    const result: (number | '…')[] = [];
    for (let i = 0; i < sorted.length; i++) {
        if (i > 0 && sorted[i] - sorted[i - 1] > 1) result.push('…');
        result.push(sorted[i]);
    }
    return result;
});

function goToPage(page: number) {
    router.get(route('post-management.index'), filterParams({ page }), {
        preserveState: true,
        replace: true,
    });
}

// ── Toggle flag ───────────────────────────────────────────────────────────────
function toggleFlag(post: Post) {
    router.patch(route('post-management.toggle-status', post.id), {}, {
        preserveScroll: true,
        only: ['posts', 'filters'],
        onSuccess: () => {
            if (selectedPost.value && selectedPost.value.id === post.id) {
                selectedPost.value = {
                    ...selectedPost.value,
                    status: selectedPost.value.status === 'flagged' ? 'safe' : 'flagged',
                };
            }
        },
    });
}

// ── Post detail modal ─────────────────────────────────────────────────────────
const selectedPost = ref<Post | null>(null);

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
        <!-- Extra bottom padding so the fixed pagination bar never overlaps cards -->
        <div class="space-y-5 pb-20">

            <!-- Filter tabs + Latest first label -->
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <FilterTabs :model-value="activeFilter" :tabs="tabs" @update:model-value="setFilter" />

                <!-- Sort toggle -->
                <div class="flex items-center bg-bg-surface border border-border-light rounded-xl p-1">
                    <button
                        type="button"
                        class="flex items-center gap-2 px-5 py-2 text-sm font-medium rounded-lg transition-all duration-150 text-filter-inactive-text hover:text-filter-inactive-hover-text hover:bg-filter-inactive-hover-bg select-none"
                        @click="toggleSort"
                    >
                        <i :class="['fas text-[10px]', currentSort === 'oldest' ? 'fa-arrow-up-wide-short' : 'fa-arrow-down-wide-short']" />
                        {{ currentSortLabel }}
                    </button>
                </div>
            </div>

            <!-- Post grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <PostCard
                    v-for="post in posts.data"
                    :key="post.id"
                    :post="post"
                    @toggle-flag="toggleFlag(post)"
                    @view="openModal(post)"
                />

                <div v-if="posts.data.length === 0"
                    class="col-span-full py-20 flex flex-col items-center gap-3 text-text-muted">
                    <p class="text-sm">No posts in this category</p>
                </div>
            </div>
        </div>

        <!-- Fixed pagination bar -->
        <Pagination
            :fixed="true"
            :current-page="posts.current_page"
            :total-pages="posts.last_page"
            :page-numbers="pageNumbers"
            :range-start="posts.from ?? 0"
            :range-end="posts.to ?? 0"
            :total="posts.total"
            @update:current-page="goToPage"
            @prev="goToPage(posts.current_page - 1)"
            @next="goToPage(posts.current_page + 1)"
        />

        <!-- Post detail modal -->
        <PostDetailModal
            :post="selectedPost"
            :show="selectedPost !== null"
            @close="closeModal"
            @toggle-flag="toggleFlagFromModal"
        />
    </AdminLayout>
</template>
