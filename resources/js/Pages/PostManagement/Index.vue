<script setup lang="ts">
import type { FilterTab } from '@/Components/Filters/FilterTabs.vue';
import FilterTabs from '@/Components/Filters/FilterTabs.vue';
import PostDetailModal from '@/Components/Posts/PostDetailModal.vue';
import ReportDetailModal from '@/Components/Posts/ReportDetailModal.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { usePaginatorNav } from '@/composables/usePaginatorNav';
import { usePollingReload } from '@/composables/usePolling';
import type {
    Paginated,
    PendingPost,
    Post,
    PostFilters,
    PostStatusCounts,
    ReportedPost,
    ReportReason,
} from '@/types';
import { buildPageButtons } from '@/utils/pagination';
import {
    CheckCircleIcon,
    ClockIcon,
    DocumentTextIcon,
    ExclamationTriangleIcon,
    FlagIcon,
} from '@heroicons/vue/24/outline';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, toRef, watch } from 'vue';

// ─── Server props ─────────────────────────────────────────────────────────────

const props = defineProps<{
    posts: Paginated<Post>;
    filters: PostFilters;
    counts: PostStatusCounts;
    reportedPosts: ReportedPost[];
    pendingPosts: PendingPost[];
}>();

usePollingReload(['posts', 'counts', 'reportedPosts', 'pendingPosts']);

// ─── Reported posts (seeded from server, mutable for local optimistic updates) ─

const reportedPosts = ref<ReportedPost[]>(props.reportedPosts ?? []);

watch(
    () => props.reportedPosts,
    (val) => {
        reportedPosts.value = val ?? [];
    },
);

// ─── Pending posts ────────────────────────────────────────────────────────────

const pendingPosts = ref<PendingPost[]>(props.pendingPosts ?? []);

watch(
    () => props.pendingPosts,
    (val) => {
        pendingPosts.value = val ?? [];
    },
);

// ─── Tabs ─────────────────────────────────────────────────────────────────────

const tabs: FilterTab[] = [
    { label: 'All', value: 'all' },
    {
        label: 'Safe',
        value: 'safe',
        badgeInactiveClass: 'bg-status-safe-bg text-status-safe',
    },
    {
        label: 'Flagged',
        value: 'flagged',
        badgeInactiveClass: 'bg-status-flagged-bg text-status-flagged',
    },
    { label: 'Archives', value: 'archived' },
    { label: 'Reported', value: 'reported' },
    { label: 'Pending', value: 'pending' },
];

const activeFilter = computed<string>(() => {
    if (props.filters.tab === 'reported') return 'reported';
    if (props.filters.tab === 'pending') return 'pending';
    if (props.filters.status) return props.filters.status;
    if (props.filters.program) return props.filters.program;
    if (props.filters.mood) return props.filters.mood;
    return 'all';
});

const isReportedTab = computed(() => activeFilter.value === 'reported');
const isPendingTab = computed(() => activeFilter.value === 'pending');
const isArchivesTab = computed(() => activeFilter.value === 'archived');

// ─── Filter state (search + reported-specific filters) ────────────────────────

const search = ref('');
const filterReason = ref('');

const REPORT_REASONS: ReportReason[] = [
    'Harassment',
    'Offensive Language',
    'Bullying',
    'False Information',
    'Spam',
    'Other',
];

// ─── Reported tab: local sort + pagination ────────────────────────────────────

const reportedSort = ref<'latest' | 'oldest'>('latest');
const reportedCurrentPage = ref(1);
const REPORTED_PER_PAGE = 10;

// ─── Pending tab: local pagination ───────────────────────────────────────────

const pendingCurrentPage = ref(1);
const PENDING_PER_PAGE = 10;

// ─── Sort (regular tabs only) ─────────────────────────────────────────────────

const currentSort = computed(() => props.filters.sort ?? 'latest');

function toggleSort() {
    const next = currentSort.value === 'latest' ? 'oldest' : 'latest';
    router.get(
        route('posts.index'),
        filterParams({
            sort: next === 'latest' ? undefined : next,
            page: undefined,
        }),
        { preserveState: true, replace: true },
    );
}

// ─── Routing helpers ──────────────────────────────────────────────────────────

type FilterParams = Record<string, string | number | null | undefined>;

function filterParams(extra: FilterParams = {}): FilterParams {
    const params: FilterParams = {};
    const keys: Array<'status' | 'program' | 'mood'> = [
        'status',
        'program',
        'mood',
    ];
    for (const key of keys) {
        if (props.filters[key]) params[key] = props.filters[key];
    }
    if (props.filters.sort && props.filters.sort !== 'latest') {
        params.sort = props.filters.sort;
    }
    // never carry `tab` into regular sort/page navigations
    return { ...params, ...extra };
}

function setFilter(filter: string) {
    if (filter === 'reported' || filter === 'pending') {
        router.get(
            route('posts.index'),
            { tab: filter },
            { preserveState: true, replace: true },
        );
        return;
    }

    const params: Record<string, string> = {};
    if (filter !== 'all') params.status = filter;
    if (currentSort.value !== 'latest') params.sort = currentSort.value;

    router.get(route('posts.index'), params, {
        preserveState: true,
        replace: true,
    });
}

// ─── Pagination (regular tabs) ────────────────────────────────────────────────

const { pageNumbers } = usePaginatorNav(toRef(props, 'posts'));

function goToPage(page: number) {
    router.get(route('posts.index'), filterParams({ page }), {
        preserveState: true,
        replace: true,
    });
}

// ─── Stats (change per tab) ───────────────────────────────────────────────────

interface StatItem {
    label: string;
    value: number;
    iconColor: string;
    bgColor: string;
    hoverBg: string;
    icon: 'document' | 'flag' | 'check' | 'clock' | 'warning';
    description?: string;
}

const statItems = computed<StatItem[]>(() => {
    if (isReportedTab.value) {
        return [
            {
                label: 'Total Reported',
                value: reportedPosts.value.length,
                icon: 'flag',
                iconColor: 'text-blue-500',
                bgColor: 'bg-blue-50',
                hoverBg: 'group-hover:bg-blue-100',
                description:
                    'Posts Reported by the community and pending moderation review.',
            },
        ];
    }
    if (isPendingTab.value) {
        return [
            {
                label: 'Total Pending',
                value: pendingPosts.value.length,
                icon: 'clock',
                iconColor: 'text-yellow-500',
                bgColor: 'bg-yellow-50',
                hoverBg: 'group-hover:bg-yellow-100',
                description:
                    'Posts submitted by students awaiting admin review.',
            },
        ];
    }
    return [
        {
            label: 'Total Posts',
            value: props.counts.total,
            icon: 'document',
            iconColor: 'text-blue-500',
            bgColor: 'bg-blue-50',
            hoverBg: 'group-hover:bg-blue-100',
        },
        {
            label: 'Flagged Posts',
            value: props.counts.flagged,
            icon: 'warning',
            iconColor: 'text-red-500',
            bgColor: 'bg-red-50',
            hoverBg: 'group-hover:bg-red-100',
        },
        {
            label: 'Safe Posts',
            value: props.counts.safe,
            icon: 'check',
            iconColor: 'text-green-500',
            bgColor: 'bg-green-50',
            hoverBg: 'group-hover:bg-green-100',
        },
        {
            label: 'Archived Posts',
            value: props.counts.archived,
            icon: 'clock',
            iconColor: 'text-yellow-500',
            bgColor: 'bg-yellow-50',
            hoverBg: 'group-hover:bg-yellow-100',
        },
    ];
});

// ─── Filtered data ────────────────────────────────────────────────────────────

const filteredReportedPosts = computed(() => {
    const q = search.value.trim().toLowerCase();
    const posts = reportedPosts.value.filter((p) => {
        if (q && !p.anonymous_name.toLowerCase().includes(q)) return false;
        if (filterReason.value && p.top_reason !== filterReason.value)
            return false;
        return true;
    });
    return [...posts].sort((a, b) => {
        const diff =
            new Date(a.latest_report_date).getTime() -
            new Date(b.latest_report_date).getTime();
        return reportedSort.value === 'latest' ? -diff : diff;
    });
});

watch(filteredReportedPosts, () => {
    reportedCurrentPage.value = 1;
});

const reportedTotalPages = computed(() =>
    Math.max(
        1,
        Math.ceil(filteredReportedPosts.value.length / REPORTED_PER_PAGE),
    ),
);

const reportedPageNumbers = computed(() =>
    buildPageButtons(reportedCurrentPage.value, reportedTotalPages.value),
);

const paginatedReportedPosts = computed(() => {
    const start = (reportedCurrentPage.value - 1) * REPORTED_PER_PAGE;
    return filteredReportedPosts.value.slice(start, start + REPORTED_PER_PAGE);
});

const reportedRangeStart = computed(() =>
    filteredReportedPosts.value.length === 0
        ? 0
        : (reportedCurrentPage.value - 1) * REPORTED_PER_PAGE + 1,
);

const reportedRangeEnd = computed(() =>
    Math.min(
        reportedCurrentPage.value * REPORTED_PER_PAGE,
        filteredReportedPosts.value.length,
    ),
);

const filteredPendingPosts = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return pendingPosts.value;
    return pendingPosts.value.filter(
        (p) =>
            (p.anonymous_name ?? '').toLowerCase().includes(q) ||
            (p.content ?? '').toLowerCase().includes(q),
    );
});

watch(filteredPendingPosts, () => {
    pendingCurrentPage.value = 1;
});

const pendingTotalPages = computed(() =>
    Math.max(
        1,
        Math.ceil(filteredPendingPosts.value.length / PENDING_PER_PAGE),
    ),
);

const pendingPageNumbers = computed(() =>
    buildPageButtons(pendingCurrentPage.value, pendingTotalPages.value),
);

const paginatedPendingPosts = computed(() => {
    const start = (pendingCurrentPage.value - 1) * PENDING_PER_PAGE;
    return filteredPendingPosts.value.slice(start, start + PENDING_PER_PAGE);
});

const pendingRangeStart = computed(() =>
    filteredPendingPosts.value.length === 0
        ? 0
        : (pendingCurrentPage.value - 1) * PENDING_PER_PAGE + 1,
);

const pendingRangeEnd = computed(() =>
    Math.min(
        pendingCurrentPage.value * PENDING_PER_PAGE,
        filteredPendingPosts.value.length,
    ),
);

const filteredPosts = computed(() => {
    const q = search.value.trim().toLowerCase();
    if (!q) return props.posts.data;
    return props.posts.data.filter(
        (p) =>
            (p.anonymous_name ?? '').toLowerCase().includes(q) ||
            (p.content ?? '').toLowerCase().includes(q),
    );
});

const tableRowCount = computed(() =>
    isReportedTab.value
        ? filteredReportedPosts.value.length
        : isPendingTab.value
          ? filteredPendingPosts.value.length
          : props.posts.total,
);

// ─── Mood badge styles ────────────────────────────────────────────────────────

const MOOD_BADGE: Record<string, { pill: string; dot: string }> = {
    Excited: {
        pill: 'bg-amber-50 text-amber-600 border border-amber-200',
        dot: 'bg-amber-400',
    },
    Content: {
        pill: 'bg-green-50 text-green-700 border border-green-200',
        dot: 'bg-green-500',
    },
    Stressed: {
        pill: 'bg-orange-50 text-orange-600 border border-orange-200',
        dot: 'bg-orange-400',
    },
    Drained: {
        pill: 'bg-purple-50 text-purple-600 border border-purple-200',
        dot: 'bg-purple-400',
    },
};

function moodStyle(mood: string) {
    return (
        MOOD_BADGE[mood] ?? {
            pill: 'bg-gray-100 text-gray-600 border border-gray-200',
            dot: 'bg-gray-400',
        }
    );
}

// ─── Report badge helpers ─────────────────────────────────────────────────────

const REASON_BADGE: Record<string, string> = {
    Harassment: 'bg-red-50 text-red-700',
    'Offensive Language': 'bg-orange-50 text-orange-700',
    Bullying: 'bg-purple-50 text-purple-700',
    'False Information': 'bg-blue-50 text-blue-700',
    Spam: 'bg-gray-100 text-gray-600',
    Other: 'bg-gray-100 text-gray-600',
};

// ─── Modals ───────────────────────────────────────────────────────────────────

const selectedPost = ref<Post | null>(null);
const selectedReport = ref<ReportedPost | null>(null);

function updateSelectedPostStatus(post: Post, nextStatus: Post['status']) {
    if (selectedPost.value?.id !== post.id) return;
    selectedPost.value = { ...selectedPost.value, status: nextStatus };
}

function toggleFlag(post: Post) {
    const isFlagged = post.status === 'flagged';
    const routeName = isFlagged ? 'posts.unflag' : 'posts.flag';
    const nextStatus: Post['status'] = isFlagged ? 'safe' : 'flagged';
    router.patch(
        route(routeName, post.id),
        {},
        {
            preserveScroll: true,
            only: ['posts', 'filters', 'flash', 'counts'],
            onSuccess: () => updateSelectedPostStatus(post, nextStatus),
        },
    );
}

function toggleReport(post: Post, chosenStatus: String) {
    const routeName = chosenStatus == 'safe' ? 'posts.unflag' : 'posts.flag';
    console.log(post, chosenStatus);
    const nextStatus: Post['status'] =
        chosenStatus == 'safe' ? 'safe' : 'flagged';
    router.patch(
        route('posts.unreport', { post: post.id, status: chosenStatus }),
        {},
        {
            preserveScroll: true,
            only: ['posts', 'filters', 'flash', 'counts', 'reportedPosts'],
            onSuccess: () => {
                reportedPosts.value = reportedPosts.value.filter(
                    (p) => p.id !== post.id,
                );
            },
        },
    );
}

function openPostModal(post: Post) {
    selectedPost.value = post;
}
function closePostModal() {
    selectedPost.value = null;
}
function toggleFlagFromModal() {
    if (selectedPost.value) toggleFlag(selectedPost.value);
}

function openReportModal(r: ReportedPost) {
    selectedReport.value = { ...r };
}
function closeReportModal() {
    selectedReport.value = null;
}

function onMarkSafe() {
    if (!selectedReport.value) return;
    router.patch(
        route('reported-posts.mark-safe', selectedReport.value.id),
        {},
        { onSuccess: () => closeReportModal() },
    );
}
function onFlag() {
    if (!selectedReport.value) return;
    router.patch(
        route('reported-posts.mark-flagged', selectedReport.value.id),
        {},
        { onSuccess: () => closeReportModal() },
    );
}

function approvePendingAsSafe(post: PendingPost) {
    router.patch(route('pending-posts.approve-safe', post.id), {});
}

function approvePendingAsFlagged(post: PendingPost) {
    router.patch(route('pending-posts.approve-flagged', post.id), {});
}

function clearFilters() {
    search.value = '';
    filterReason.value = '';
}
</script>

<template>
    <Head title="Posts" />

    <AdminLayout title="Post Management">
        <div class="space-y-3 pb-20">
            <!-- ── Stats strip ───────────────────────────────────────────── -->
            <div
                :class="[
                    'grid gap-4',
                    isReportedTab || isPendingTab
                        ? 'lg:grid-cols-1'
                        : 'grid-cols-2 lg:grid-cols-4',
                ]"
            >
                <div
                    v-for="stat in statItems"
                    :key="stat.label"
                    class="group flex w-full items-center gap-4 border border-gray-100 bg-white p-3 shadow-sm transition-shadow hover:shadow-md md:flex-1"
                >
                    <div
                        :class="[
                            'flex h-11 w-11 shrink-0 items-center justify-center transition-colors',
                            stat.bgColor,
                            stat.hoverBg,
                        ]"
                    >
                        <DocumentTextIcon
                            v-if="stat.icon === 'document'"
                            :class="['h-5 w-5', stat.iconColor]"
                        />
                        <FlagIcon
                            v-else-if="stat.icon === 'flag'"
                            :class="['h-5 w-5', stat.iconColor]"
                        />
                        <ClockIcon
                            v-else-if="stat.icon === 'clock'"
                            :class="['h-5 w-5', stat.iconColor]"
                        />
                        <ExclamationTriangleIcon
                            v-else-if="stat.icon === 'warning'"
                            :class="['h-5 w-5', stat.iconColor]"
                        />
                        <CheckCircleIcon
                            v-else-if="stat.icon === 'check'"
                            :class="['h-5 w-5', stat.iconColor]"
                        />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p
                            class="text-xl font-extrabold tracking-tight text-gray-900"
                        >
                            {{ stat.value }}
                        </p>
                        <p
                            class="mt-0.5 truncate text-xs font-medium text-gray-400"
                        >
                            {{ stat.label }}
                        </p>
                    </div>
                    <p
                        v-if="stat.description"
                        class="hidden max-w-sm text-right text-sm leading-relaxed text-gray-400 sm:block"
                    >
                        {{ stat.description }}
                    </p>
                </div>
            </div>

            <!-- ── Tabs + Sort ───────────────────────────────────────────── -->
            <div
                :class="[
                    'flex flex-col items-center gap-3',
                    isReportedTab ? 'xl:flex-row' : 'md:flex-row',
                ]"
            >
                <div class="flex w-full flex-1 flex-row items-center gap-3">
                    <!-- Search -->
                    <div class="relative w-full flex-1">
                        <span
                            class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                class="h-4 w-4"
                                viewBox="0 0 24 24"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            >
                                <circle cx="11" cy="11" r="8" />
                                <line x1="21" y1="21" x2="16.65" y2="16.65" />
                            </svg>
                        </span>
                        <input
                            v-model="search"
                            type="text"
                            :placeholder="
                                isReportedTab
                                    ? 'Search by anonymous name...'
                                    : 'Search posts...'
                            "
                            class="focus:border-sidebar focus:ring-sidebar/20 w-full border border-gray-200 bg-gray-50 py-2.5 pr-4 pl-9 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:bg-white focus:ring-2 focus:outline-none"
                        />
                    </div>
                    <div
                        class="flex flex-row gap-3 sm:flex-wrap sm:items-center"
                    >
                        <!-- Reported-only filters -->
                        <template v-if="isReportedTab">
                            <!-- Report Reason -->
                            <div class="relative">
                                <span
                                    class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400"
                                >
                                    <i class="fas fa-tag text-xs" />
                                </span>
                                <select
                                    v-model="filterReason"
                                    class="focus:border-sidebar focus:ring-sidebar/20 cursor-pointer appearance-none border border-gray-200 bg-gray-50 py-2.5 pr-8 pl-9 text-sm text-gray-700 transition-colors focus:bg-white focus:ring-2 focus:outline-none"
                                >
                                    <option value="">All Reasons</option>
                                    <option
                                        v-for="r in REPORT_REASONS"
                                        :key="r"
                                        :value="r"
                                    >
                                        {{ r }}
                                    </option>
                                </select>
                                <span
                                    class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <polyline points="6 9 12 15 18 9" />
                                    </svg>
                                </span>
                            </div>
                        </template>

                        <!-- Clear (shown when any filter is active) -->
                        <button
                            v-if="search || filterReason"
                            type="button"
                            class="text-text-muted hover:text-text-primary flex items-center gap-1.5 border border-gray-200 bg-white px-3 py-1 text-xs font-medium transition-colors hover:border-gray-300"
                            @click="clearFilters"
                        >
                            <i class="fas fa-times text-[10px]" />
                            Clear
                        </button>
                        <button
                            v-if="!isArchivesTab && !isPendingTab"
                            type="button"
                            class="text-filter-inactive-text hover:text-filter-inactive-hover-text hover:bg-filter-inactive-hover-bg bg-bg-surface border-border-light ml-auto flex w-min cursor-pointer items-center gap-2 border px-2 py-1 text-xs font-medium transition-all duration-150 select-none"
                            @click="
                                isReportedTab
                                    ? (reportedSort =
                                          reportedSort === 'latest'
                                              ? 'oldest'
                                              : 'latest')
                                    : toggleSort()
                            "
                        >
                            <i
                                class="fas text-xl font-bold"
                                :class="
                                    (isReportedTab
                                        ? reportedSort
                                        : currentSort) === 'oldest'
                                        ? 'fa-arrow-up-wide-short'
                                        : 'fa-arrow-down-wide-short'
                                "
                                >{{
                                    (isReportedTab
                                        ? reportedSort
                                        : currentSort) === 'oldest'
                                        ? '↑'
                                        : '↓'
                                }}</i
                            >
                            {{
                                (isReportedTab ? reportedSort : currentSort) ===
                                'oldest'
                                    ? 'Oldest First'
                                    : 'Latest First'
                            }}
                        </button>
                    </div>
                </div>

                <FilterTabs
                    :model-value="activeFilter"
                    :tabs="tabs"
                    @update:model-value="setFilter"
                />
            </div>
            <!-- ── Table ─────────────────────────────────────────────────── -->
            <div
                class="overflow-hidden border border-gray-100 bg-white shadow-sm"
            >
                <!-- Table label row -->
                <div class="border-b border-gray-100 px-5 py-3.5">
                    <p class="text-text-primary text-sm font-semibold">
                        {{
                            isReportedTab
                                ? 'Reported Posts'
                                : isPendingTab
                                  ? 'Pending Posts'
                                  : isArchivesTab
                                    ? 'Archived Posts'
                                    : 'MoodSpace Posts'
                        }}
                        <span class="text-text-muted ml-1.5 text-xs font-normal"
                            >({{ tableRowCount }} total)</span
                        >
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <!-- ── Reported tab columns ── -->
                        <template v-if="isReportedTab">
                            <colgroup>
                                <col class="w-45" />
                                <col class="w-auto" />
                                <col class="w-24" />
                                <col class="w-40" />
                                <col class="w-32" />
                            </colgroup>
                            <thead
                                class="bg-table-header border-table-grid border-b"
                            >
                                <tr>
                                    <th
                                        class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Anonymous Name
                                    </th>
                                    <th
                                        class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Post Preview
                                    </th>
                                    <th
                                        class="border-table-grid border-r px-4 py-3 text-center text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Reports
                                    </th>
                                    <th
                                        class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Top Reason
                                    </th>
                                    <th
                                        class="px-4 py-3 text-center text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Action
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-table-grid divide-y">
                                <tr v-if="filteredReportedPosts.length === 0">
                                    <td colspan="6" class="py-16 text-center">
                                        <div
                                            class="flex flex-col items-center gap-3 text-gray-400"
                                        >
                                            <i
                                                class="fas fa-flag text-3xl opacity-30"
                                            />
                                            <p class="text-sm">
                                                No reported posts match the
                                                current filters.
                                            </p>
                                            <button
                                                v-if="search || filterReason"
                                                type="button"
                                                class="text-sidebar text-xs font-medium underline underline-offset-2"
                                                @click="clearFilters"
                                            >
                                                Clear filters
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    v-for="rp in paginatedReportedPosts"
                                    :key="rp.id"
                                    class="bg-table-row hover:bg-table-row-hover transition-colors"
                                >
                                    <td
                                        class="border-table-grid border-r px-4 py-3"
                                    >
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="bg-sidebar flex h-8 w-8 shrink-0 items-center justify-center text-xs font-bold text-white"
                                            >
                                                {{
                                                    rp.anonymous_name
                                                        .charAt(0)
                                                        .toUpperCase()
                                                }}
                                            </div>
                                            <div class="min-w-0">
                                                <p
                                                    class="text-text-primary truncate text-sm font-semibold"
                                                >
                                                    {{ rp.anonymous_name }}
                                                </p>
                                                <p
                                                    class="text-text-muted truncate text-xs"
                                                >
                                                    {{ rp.program }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td
                                        class="border-table-grid border-r px-4 py-3"
                                    >
                                        <p
                                            class="text-text-secondary line-clamp-2 max-w-sm text-sm leading-relaxed"
                                        >
                                            {{ rp.post_preview }}
                                        </p>
                                    </td>
                                    <td
                                        class="border-table-grid border-r px-4 py-3 text-center"
                                    >
                                        <span
                                            class="inline-flex h-7 w-7 items-center justify-center bg-red-50 text-sm font-bold text-red-600"
                                        >
                                            {{ rp.report_count }}
                                        </span>
                                    </td>
                                    <td
                                        class="border-table-grid border-r px-4 py-3"
                                    >
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium',
                                                REASON_BADGE[rp.top_reason] ??
                                                    'bg-gray-100 text-gray-600',
                                            ]"
                                        >
                                            <i class="fas fa-flag text-[8px]" />
                                            {{ rp.top_reason }}
                                        </span>
                                    </td>
                                    <td
                                        class="flex flex-row gap-2 px-4 py-3 text-center"
                                    >
                                        <button
                                            type="button"
                                            class="bg-sidebar/10 text-sidebar hover:bg-sidebar inline-flex cursor-pointer items-center gap-1.5 px-3 py-1.5 text-xs font-semibold transition-all duration-150 hover:text-white"
                                            @click="toggleReport(rp, 'safe')"
                                        >
                                            {{ console.log(rp) }}
                                            <i class="fas fa-eye text-[10px]" />
                                            Safe
                                        </button>
                                        <button
                                            type="button"
                                            class="bg-status-flagged/40 text-sidebar hover:bg-status-flagged inline-flex cursor-pointer items-center gap-1.5 px-3 py-1.5 text-xs font-semibold transition-all duration-150 hover:text-white"
                                            @click="toggleReport(rp, 'flag')"
                                        >
                                            <i class="fas fa-eye text-[10px]" />
                                            Flag
                                        </button>
                                        <button
                                            type="button"
                                            class="bg-sidebar/10 text-sidebar hover:bg-sidebar inline-flex cursor-pointer items-center gap-1.5 px-3 py-1.5 text-xs font-semibold transition-all duration-150 hover:text-white"
                                            @click="openReportModal(rp)"
                                        >
                                            <i class="fas fa-eye text-[10px]" />
                                            View
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </template>

                        <!-- ── Pending tab ── -->
                        <template v-else-if="isPendingTab">
                            <colgroup>
                                <col class="w-45" />
                                <col class="w-auto" />
                                <col class="w-32" />
                                <col class="w-38" />
                                <col class="w-40" />
                            </colgroup>
                            <thead
                                class="bg-table-header border-table-grid border-b"
                            >
                                <tr>
                                    <th
                                        class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Student
                                    </th>
                                    <th
                                        class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Post Content
                                    </th>
                                    <th
                                        class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Mood
                                    </th>
                                    <th
                                        class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Date Posted
                                    </th>
                                    <th
                                        class="px-4 py-3 text-center text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-table-grid divide-y">
                                <tr v-if="paginatedPendingPosts.length === 0">
                                    <td colspan="5" class="py-16 text-center">
                                        <div
                                            class="flex flex-col items-center gap-3 text-gray-400"
                                        >
                                            <i
                                                class="fas fa-clock text-3xl opacity-30"
                                            />
                                            <p class="text-sm">
                                                No pending posts.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    v-for="post in paginatedPendingPosts"
                                    :key="post.id"
                                    class="bg-table-row hover:bg-table-row-hover transition-colors"
                                >
                                    <!-- Student -->
                                    <td
                                        class="border-table-grid border-r px-4 py-3"
                                    >
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="bg-sidebar flex h-8 w-8 shrink-0 items-center justify-center text-xs font-bold text-white"
                                            >
                                                {{
                                                    (post.anonymous_name ?? 'U')
                                                        .charAt(0)
                                                        .toUpperCase()
                                                }}
                                            </div>
                                            <div class="min-w-0">
                                                <p
                                                    class="text-text-primary truncate text-sm font-semibold capitalize"
                                                >
                                                    {{
                                                        post.anonymous_name ??
                                                        'Anonymous'
                                                    }}
                                                </p>
                                                <p
                                                    class="text-text-muted truncate text-xs"
                                                >
                                                    {{ post.program }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Content -->
                                    <td
                                        class="border-table-grid border-r px-4 py-3"
                                    >
                                        <p
                                            class="text-text-secondary line-clamp-2 max-w-sm text-sm leading-relaxed"
                                        >
                                            {{ post.content ?? '—' }}
                                        </p>
                                    </td>
                                    <!-- Mood -->
                                    <td
                                        class="border-table-grid border-r px-4 py-3"
                                    >
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium',
                                                moodStyle(post.mood).pill,
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'h-1.5 w-1.5',
                                                    moodStyle(post.mood).dot,
                                                ]"
                                            />
                                            {{ post.mood }}
                                        </span>
                                    </td>
                                    <!-- Date -->
                                    <td
                                        class="border-table-grid border-r px-4 py-3"
                                    >
                                        <div class="text-xs text-gray-500">
                                            <p class="font-medium">
                                                {{ post.date }}
                                            </p>
                                            <p class="text-gray-400">
                                                {{ post.time }}
                                            </p>
                                        </div>
                                    </td>
                                    <!-- Actions -->
                                    <td class="px-4 py-3">
                                        <div
                                            class="flex items-center justify-center gap-1.5"
                                        >
                                            <button
                                                type="button"
                                                class="border-status-safe text-status-safe hover:bg-status-safe inline-flex cursor-pointer items-center gap-1 border px-2.5 py-1.5 text-xs font-semibold transition-all hover:text-white"
                                                @click="
                                                    approvePendingAsSafe(post)
                                                "
                                            >
                                                <i
                                                    class="fas fa-check text-[9px]"
                                                />
                                                Safe
                                            </button>
                                            <button
                                                type="button"
                                                class="border-status-flagged text-status-flagged hover:bg-status-flagged inline-flex cursor-pointer items-center gap-1 border px-2.5 py-1.5 text-xs font-semibold transition-all hover:text-white"
                                                @click="
                                                    approvePendingAsFlagged(
                                                        post,
                                                    )
                                                "
                                            >
                                                <i
                                                    class="fas fa-flag text-[9px]"
                                                />
                                                Flag
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </template>

                        <!-- ── All / Flagged / Safe / Archives tabs ── -->
                        <template v-else>
                            <colgroup>
                                <col class="w-45" />
                                <col class="w-auto" />
                                <col class="w-32" />
                                <col class="w-38" />
                                <col class="w-30" />
                                <col class="w-32" />
                            </colgroup>
                            <thead
                                class="bg-table-header border-table-grid border-b"
                            >
                                <tr>
                                    <th
                                        class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Student
                                    </th>
                                    <th
                                        class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Post Content
                                    </th>
                                    <th
                                        class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Mood
                                    </th>
                                    <th
                                        class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Date Posted
                                    </th>
                                    <th
                                        class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Status
                                    </th>
                                    <th
                                        class="px-4 py-3 text-center text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-table-grid divide-y">
                                <tr v-if="filteredPosts.length === 0">
                                    <td colspan="6" class="py-16 text-center">
                                        <div
                                            class="flex flex-col items-center gap-3 text-gray-400"
                                        >
                                            <i
                                                class="fas fa-file-alt text-3xl opacity-30"
                                            />
                                            <p class="text-sm">
                                                No posts in this category.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                                <tr
                                    v-for="post in filteredPosts"
                                    :key="post.id"
                                    class="bg-table-row hover:bg-table-row-hover transition-colors"
                                >
                                    <!-- Student -->
                                    <td
                                        class="border-table-grid border-r px-4 py-3"
                                    >
                                        <div class="flex items-center gap-2.5">
                                            <div
                                                class="bg-sidebar flex h-8 w-8 shrink-0 items-center justify-center text-xs font-bold text-white"
                                            >
                                                {{
                                                    (post.anonymous_name ?? 'U')
                                                        .charAt(0)
                                                        .toUpperCase()
                                                }}
                                            </div>
                                            <div class="min-w-0">
                                                <p
                                                    class="text-text-primary truncate text-sm font-semibold capitalize"
                                                >
                                                    {{
                                                        post.anonymous_name ??
                                                        'Anonymous'
                                                    }}
                                                </p>
                                                <p
                                                    class="text-text-muted truncate text-xs"
                                                >
                                                    {{ post.program }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <!-- Content -->
                                    <td
                                        class="border-table-grid border-r px-4 py-3"
                                    >
                                        <p
                                            class="text-text-secondary line-clamp-2 max-w-sm text-sm leading-relaxed"
                                        >
                                            {{ post.content ?? '—' }}
                                        </p>
                                    </td>
                                    <!-- Mood -->
                                    <td
                                        class="border-table-grid border-r px-4 py-3"
                                    >
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium',
                                                moodStyle(post.mood).pill,
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'h-1.5 w-1.5',
                                                    moodStyle(post.mood).dot,
                                                ]"
                                            />
                                            {{ post.mood }}
                                        </span>
                                    </td>
                                    <!-- Date -->
                                    <td
                                        class="border-table-grid border-r px-4 py-3"
                                    >
                                        <div class="text-xs text-gray-500">
                                            <p class="font-medium">
                                                {{ post.date }}
                                            </p>
                                            <p class="text-gray-400">
                                                {{ post.time }}
                                            </p>
                                        </div>
                                    </td>
                                    <!-- Status -->
                                    <td
                                        class="border-table-grid border-r px-4 py-3"
                                    >
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 px-2.5 py-1 text-xs font-medium',
                                                post.status === 'flagged'
                                                    ? 'bg-status-flagged-bg text-status-flagged border border-red-100'
                                                    : 'bg-status-safe-bg text-status-safe border border-green-200',
                                                post.status === 'archived'
                                                    ? 'hidden'
                                                    : '',
                                            ]"
                                        >
                                            <i
                                                :class="[
                                                    'fas text-[8px]',
                                                    post.status === 'flagged'
                                                        ? 'fa-flag'
                                                        : 'fa-check',
                                                ]"
                                            />
                                            {{
                                                post.status
                                                    .charAt(0)
                                                    .toUpperCase() +
                                                post.status.slice(1)
                                            }}
                                        </span>
                                    </td>
                                    <!-- Actions -->
                                    <td class="px-4 py-3">
                                        <div
                                            class="flex items-center justify-center gap-1.5"
                                        >
                                            <button
                                                type="button"
                                                :class="[
                                                    'cursor-pointer items-center gap-1 border px-2.5 py-1.5 text-xs font-semibold transition-all',
                                                    post.status === 'flagged'
                                                        ? 'border-status-safe text-status-safe hover:bg-status-safe hover:text-white'
                                                        : 'border-status-flagged text-status-flagged hover:bg-status-flagged hover:text-white',
                                                    post.status === 'archived'
                                                        ? 'hidden'
                                                        : '',
                                                ]"
                                                @click="toggleFlag(post)"
                                            >
                                                <i
                                                    :class="[
                                                        'fas text-[9px]',
                                                        post.status ===
                                                        'flagged'
                                                            ? 'fa-check'
                                                            : 'fa-flag',
                                                    ]"
                                                />
                                                {{
                                                    post.status === 'flagged'
                                                        ? 'Clear'
                                                        : 'Flag'
                                                }}
                                            </button>
                                            <button
                                                type="button"
                                                class="bg-sidebar/10 text-sidebar hover:bg-sidebar inline-flex cursor-pointer items-center gap-1 px-2.5 py-1.5 text-xs font-semibold transition-all hover:text-white"
                                                @click="openPostModal(post)"
                                            >
                                                <i
                                                    class="fas fa-eye text-[9px]"
                                                />
                                                View
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </template>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination (pending tab) -->
        <Pagination
            v-if="isPendingTab"
            :fixed="true"
            :current-page="pendingCurrentPage"
            :total-pages="pendingTotalPages"
            :page-numbers="pendingPageNumbers"
            :range-start="pendingRangeStart"
            :range-end="pendingRangeEnd"
            :total="filteredPendingPosts.length"
            @update:current-page="pendingCurrentPage = $event"
            @prev="pendingCurrentPage > 1 && pendingCurrentPage--"
            @next="
                pendingCurrentPage < pendingTotalPages && pendingCurrentPage++
            "
        />

        <!-- Pagination (regular tabs) -->
        <Pagination
            v-if="!isReportedTab && !isPendingTab"
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

        <!-- Pagination (reported tab) -->
        <Pagination
            v-if="isReportedTab"
            :fixed="true"
            :current-page="reportedCurrentPage"
            :total-pages="reportedTotalPages"
            :page-numbers="reportedPageNumbers"
            :range-start="reportedRangeStart"
            :range-end="reportedRangeEnd"
            :total="filteredReportedPosts.length"
            @update:current-page="reportedCurrentPage = $event"
            @prev="reportedCurrentPage > 1 && reportedCurrentPage--"
            @next="
                reportedCurrentPage < reportedTotalPages &&
                reportedCurrentPage++
            "
        />

        <!-- Post detail modal (All / Flagged / Safe) -->
        <PostDetailModal
            :post="selectedPost"
            :show="selectedPost !== null"
            @close="closePostModal"
            @toggle-flag="toggleFlagFromModal"
        />

        <!-- Report detail modal (Reported tab) -->
        <ReportDetailModal
            :report="selectedReport"
            :show="selectedReport !== null"
            @close="closeReportModal"
            @unreport="
                (status) => {
                    if (selectedReport) {
                        toggleReport(selectedReport, status);
                        closeReportModal();
                    }
                }
            "
        />
    </AdminLayout>
</template>
