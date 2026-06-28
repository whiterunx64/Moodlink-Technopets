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
    Post,
    PostFilters,
    ReportedPost,
    ReportReason,
    ReportStatus,
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
    counts: { total: number; flagged: number; safe: number };
}>();

usePollingReload(['posts', 'counts']);

// ─── Mock reported posts data ─────────────────────────────────────────────────

const reportedPosts = ref<ReportedPost[]>([
    {
        id: 1,
        anonymous_name: 'SilentWave42',
        post_preview:
            "I feel like nobody in this school actually cares about what I'm going through. Every day it's the same cycle of pretending to be okay...",
        full_content:
            "I feel like nobody in this school actually cares about what I'm going through. Every day it's the same cycle of pretending to be okay while everything feels like it's falling apart. I don't even know why I keep showing up. The people here are toxic and I'm tired of dealing with them.",
        date_posted: 'Jun 20, 2025',
        program: 'BS Psychology',
        mood: 'Drained',
        report_count: 7,
        top_reason: 'Harassment',
        latest_report_date: 'Jun 23, 2025',
        status: 'pending',
        reason_breakdown: {
            harassment: 4,
            offensive_language: 2,
            bullying: 0,
            false_information: 0,
            spam: 0,
            other: 1,
        },
        reporters: [
            {
                id: 1,
                name: 'Maria Santos',
                student_number: '2021-00123',
                program: 'BS Psychology',
                date_reported: 'Jun 23, 2025',
                reason: 'Harassment',
                comment: 'This post is targeting a specific group of students.',
            },
            {
                id: 2,
                name: 'Juan dela Cruz',
                student_number: '2022-00456',
                program: 'BS IT',
                date_reported: 'Jun 23, 2025',
                reason: 'Harassment',
            },
            {
                id: 3,
                name: 'Ana Reyes',
                student_number: '2020-00789',
                program: 'BS Nursing',
                date_reported: 'Jun 22, 2025',
                reason: 'Offensive Language',
                comment: 'The language used is inappropriate.',
            },
            {
                id: 4,
                name: 'Carlo Mendoza',
                student_number: '2021-00321',
                program: 'BS Psychology',
                date_reported: 'Jun 22, 2025',
                reason: 'Harassment',
            },
            {
                id: 5,
                name: 'Lea Garcia',
                student_number: '2023-00654',
                program: 'BS Education',
                date_reported: 'Jun 21, 2025',
                reason: 'Offensive Language',
            },
            {
                id: 6,
                name: 'Ryan Torres',
                student_number: '2022-00987',
                program: 'BS IT',
                date_reported: 'Jun 21, 2025',
                reason: 'Harassment',
            },
            {
                id: 7,
                name: 'Sophia Lim',
                student_number: '2021-00147',
                program: 'BS Nursing',
                date_reported: 'Jun 20, 2025',
                reason: 'Other',
                comment: 'Seems like indirect venting about classmates.',
            },
        ],
    },
    {
        id: 2,
        anonymous_name: 'QuietStorm88',
        post_preview:
            "Some people in my block are spreading lies about me and I know exactly who they are. Don't think I won't find out...",
        full_content:
            "Some people in my block are spreading lies about me and I know exactly who they are. Don't think I won't find out. I have screenshots and I'm not afraid to expose them publicly. They deserve everything that's coming to them.",
        date_posted: 'Jun 18, 2025',
        program: 'BS IT',
        mood: 'Stressed',
        report_count: 5,
        top_reason: 'Bullying',
        latest_report_date: 'Jun 21, 2025',
        status: 'flagged',
        reason_breakdown: {
            harassment: 1,
            offensive_language: 1,
            bullying: 3,
            false_information: 0,
            spam: 0,
            other: 0,
        },
        reporters: [
            {
                id: 1,
                name: 'Paolo Cruz',
                student_number: '2021-00258',
                program: 'BS IT',
                date_reported: 'Jun 21, 2025',
                reason: 'Bullying',
                comment:
                    'This is clearly directed at real students and feels threatening.',
            },
            {
                id: 2,
                name: 'Kim Bautista',
                student_number: '2022-00369',
                program: 'BS IT',
                date_reported: 'Jun 20, 2025',
                reason: 'Bullying',
            },
            {
                id: 3,
                name: 'Trisha Villanueva',
                student_number: '2021-00741',
                program: 'BS Education',
                date_reported: 'Jun 20, 2025',
                reason: 'Harassment',
                comment: 'This reads like a threat to another student.',
            },
            {
                id: 4,
                name: 'Kevin Tan',
                student_number: '2023-00852',
                program: 'BS Nursing',
                date_reported: 'Jun 19, 2025',
                reason: 'Bullying',
            },
            {
                id: 5,
                name: 'Diana Flores',
                student_number: '2020-00963',
                program: 'BS IT',
                date_reported: 'Jun 18, 2025',
                reason: 'Offensive Language',
            },
        ],
    },
    {
        id: 3,
        anonymous_name: 'MoonlitPath',
        post_preview:
            'People keep saying mental health awareness week is for everyone but only the popular students get noticed...',
        full_content:
            "People keep saying mental health awareness week is for everyone but only the popular students get noticed. The rest of us just sit here and suffer in silence. This school's MHW is a complete joke. All these events are just for show. Nobody actually cares.",
        date_posted: 'Jun 15, 2025',
        program: 'BS Education',
        mood: 'Drained',
        report_count: 3,
        top_reason: 'False Information',
        latest_report_date: 'Jun 17, 2025',
        status: 'pending',
        reason_breakdown: {
            harassment: 0,
            offensive_language: 1,
            bullying: 0,
            false_information: 2,
            spam: 0,
            other: 0,
        },
        reporters: [
            {
                id: 1,
                name: 'Grace Aquino',
                student_number: '2022-00135',
                program: 'BS Psychology',
                date_reported: 'Jun 17, 2025',
                reason: 'False Information',
                comment:
                    'Contains misleading claims about our school programs.',
            },
            {
                id: 2,
                name: 'Samuel Ong',
                student_number: '2021-00246',
                program: 'BS Education',
                date_reported: 'Jun 16, 2025',
                reason: 'False Information',
            },
            {
                id: 3,
                name: 'Ria Castillo',
                student_number: '2023-00357',
                program: 'BS Nursing',
                date_reported: 'Jun 15, 2025',
                reason: 'Offensive Language',
            },
        ],
    },
    {
        id: 4,
        anonymous_name: 'EchoVault',
        post_preview:
            'Check out this online shop offering school supplies! Limited stocks only, message me for details. We accept GCash...',
        full_content:
            'Check out this online shop offering school supplies! Limited stocks only, message me for details. We accept GCash. Legit and trusted seller. This is not an ad, I just genuinely want to help fellow students get affordable supplies. Follow my account for updates!',
        date_posted: 'Jun 12, 2025',
        program: 'BS Nursing',
        mood: 'Excited',
        report_count: 9,
        top_reason: 'Spam',
        latest_report_date: 'Jun 14, 2025',
        status: 'resolved',
        reason_breakdown: {
            harassment: 0,
            offensive_language: 0,
            bullying: 0,
            false_information: 2,
            spam: 7,
            other: 0,
        },
        reporters: [
            {
                id: 1,
                name: 'Mark Lopez',
                student_number: '2021-00468',
                program: 'BS IT',
                date_reported: 'Jun 14, 2025',
                reason: 'Spam',
                comment: 'Clearly an advertising post — not appropriate here.',
            },
            {
                id: 2,
                name: 'Jen Reyes',
                student_number: '2022-00579',
                program: 'BS Nursing',
                date_reported: 'Jun 14, 2025',
                reason: 'Spam',
            },
            {
                id: 3,
                name: 'Jolo Santos',
                student_number: '2020-00680',
                program: 'BS Psychology',
                date_reported: 'Jun 13, 2025',
                reason: 'Spam',
            },
            {
                id: 4,
                name: 'Bea Cruz',
                student_number: '2023-00791',
                program: 'BS Education',
                date_reported: 'Jun 13, 2025',
                reason: 'False Information',
                comment: 'This might be a scam targeting students.',
            },
            {
                id: 5,
                name: 'Rico Tan',
                student_number: '2021-00802',
                program: 'BS IT',
                date_reported: 'Jun 12, 2025',
                reason: 'Spam',
            },
            {
                id: 6,
                name: 'Mia Gomez',
                student_number: '2022-00913',
                program: 'BS Nursing',
                date_reported: 'Jun 12, 2025',
                reason: 'Spam',
            },
            {
                id: 7,
                name: 'Noel Garcia',
                student_number: '2021-01024',
                program: 'BS IT',
                date_reported: 'Jun 12, 2025',
                reason: 'Spam',
            },
            {
                id: 8,
                name: 'Angel Ramos',
                student_number: '2023-01135',
                program: 'BS Psychology',
                date_reported: 'Jun 12, 2025',
                reason: 'False Information',
            },
            {
                id: 9,
                name: 'Vince Lim',
                student_number: '2020-01246',
                program: 'BS Education',
                date_reported: 'Jun 12, 2025',
                reason: 'Spam',
            },
        ],
    },
    {
        id: 5,
        anonymous_name: 'DrifterKite',
        post_preview:
            "I can't believe how our professor talks to us in class. Calling students out in front of everyone and making us feel stupid...",
        full_content:
            "I can't believe how our professor talks to us in class. Calling students out in front of everyone and making us feel stupid is not okay. This kind of teaching method is toxic and demeaning. We should all report this to the admin together.",
        date_posted: 'Jun 10, 2025',
        program: 'BS Psychology',
        mood: 'Stressed',
        report_count: 4,
        top_reason: 'Harassment',
        latest_report_date: 'Jun 12, 2025',
        status: 'pending',
        reason_breakdown: {
            harassment: 3,
            offensive_language: 1,
            bullying: 0,
            false_information: 0,
            spam: 0,
            other: 0,
        },
        reporters: [
            {
                id: 1,
                name: 'Carla Santos',
                student_number: '2022-00135',
                program: 'BS Psychology',
                date_reported: 'Jun 12, 2025',
                reason: 'Harassment',
                comment:
                    "Could damage a faculty member's reputation without evidence.",
            },
            {
                id: 2,
                name: 'Nico Dela Rosa',
                student_number: '2021-00246',
                program: 'BS Psychology',
                date_reported: 'Jun 11, 2025',
                reason: 'Harassment',
            },
            {
                id: 3,
                name: 'Trina Abad',
                student_number: '2023-00357',
                program: 'BS IT',
                date_reported: 'Jun 11, 2025',
                reason: 'Offensive Language',
            },
            {
                id: 4,
                name: 'Marc Reyes',
                student_number: '2020-00468',
                program: 'BS Nursing',
                date_reported: 'Jun 10, 2025',
                reason: 'Harassment',
            },
        ],
    },
    {
        id: 6,
        anonymous_name: 'TidePulse',
        post_preview:
            "Someone in our org keeps stealing credit for group work. If you're reading this, you know who you are. Karma is real...",
        full_content:
            "Someone in our org keeps stealing credit for group work. If you're reading this, you know who you are. Karma is real and everyone around you can see what you're doing. Stop riding on others' hard work and pretending it's yours.",
        date_posted: 'Jun 8, 2025',
        program: 'BS IT',
        mood: 'Stressed',
        report_count: 2,
        top_reason: 'Bullying',
        latest_report_date: 'Jun 9, 2025',
        status: 'resolved',
        reason_breakdown: {
            harassment: 0,
            offensive_language: 0,
            bullying: 2,
            false_information: 0,
            spam: 0,
            other: 0,
        },
        reporters: [
            {
                id: 1,
                name: 'Ava Villanueva',
                student_number: '2021-00579',
                program: 'BS IT',
                date_reported: 'Jun 9, 2025',
                reason: 'Bullying',
                comment:
                    'Feels like indirectly calling out a specific org member.',
            },
            {
                id: 2,
                name: 'Gio Torres',
                student_number: '2022-00680',
                program: 'BS IT',
                date_reported: 'Jun 8, 2025',
                reason: 'Bullying',
            },
        ],
    },
    {
        id: 7,
        anonymous_name: 'CrimsonNote',
        post_preview:
            'There are people in this platform who are clearly faking their emotions just to get attention. Stop being so dramatic...',
        full_content:
            "There are people in this platform who are clearly faking their emotions just to get attention and sympathy. Stop being so dramatic. Real problems exist and you're here crying over nothing. Grow up.",
        date_posted: 'Jun 5, 2025',
        program: 'BS Nursing',
        mood: 'Content',
        report_count: 6,
        top_reason: 'Offensive Language',
        latest_report_date: 'Jun 8, 2025',
        status: 'flagged',
        reason_breakdown: {
            harassment: 2,
            offensive_language: 3,
            bullying: 1,
            false_information: 0,
            spam: 0,
            other: 0,
        },
        reporters: [
            {
                id: 1,
                name: 'Bianca Sta. Cruz',
                student_number: '2021-00693',
                program: 'BS Psychology',
                date_reported: 'Jun 8, 2025',
                reason: 'Offensive Language',
                comment: 'This invalidates the struggles of other students.',
            },
            {
                id: 2,
                name: 'Renz Navarro',
                student_number: '2022-00704',
                program: 'BS Nursing',
                date_reported: 'Jun 7, 2025',
                reason: 'Offensive Language',
            },
            {
                id: 3,
                name: 'Hope Castillo',
                student_number: '2020-00815',
                program: 'BS IT',
                date_reported: 'Jun 7, 2025',
                reason: 'Harassment',
                comment: 'This mocks people who are genuinely struggling.',
            },
            {
                id: 4,
                name: 'Felix Santos',
                student_number: '2023-00926',
                program: 'BS Education',
                date_reported: 'Jun 6, 2025',
                reason: 'Bullying',
            },
            {
                id: 5,
                name: 'Lyra Delos Reyes',
                student_number: '2021-01037',
                program: 'BS Psychology',
                date_reported: 'Jun 6, 2025',
                reason: 'Offensive Language',
            },
            {
                id: 6,
                name: 'Josh Morales',
                student_number: '2022-01148',
                program: 'BS Nursing',
                date_reported: 'Jun 5, 2025',
                reason: 'Harassment',
            },
        ],
    },
]);

// ─── Tabs ─────────────────────────────────────────────────────────────────────

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
    { label: 'Archives', value: 'archives' },
];

const activeFilter = computed<string>(() => {
    if (props.filters.tab === 'reported') return 'reported';
    if (props.filters.tab === 'archives') return 'archives';
    if (props.filters.status) return props.filters.status;
    if (props.filters.program) return props.filters.program;
    if (props.filters.mood) return props.filters.mood;
    return 'all';
});

const isReportedTab = computed(() => activeFilter.value === 'reported');
const isArchivesTab = computed(() => activeFilter.value === 'archives');

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
    if (filter === 'reported' || filter === 'archives') {
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
        : isArchivesTab.value
          ? 0
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

function updateReportStatus(id: number, status: ReportStatus) {
    const idx = reportedPosts.value.findIndex((p) => p.id === id);
    if (idx !== -1)
        reportedPosts.value[idx] = { ...reportedPosts.value[idx], status };
    if (selectedReport.value?.id === id) {
        selectedReport.value = { ...selectedReport.value, status };
    }
    closeReportModal();
}

function onMarkSafe() {
    if (selectedReport.value)
        updateReportStatus(selectedReport.value.id, 'resolved');
}
function onFlag() {
    if (selectedReport.value)
        updateReportStatus(selectedReport.value.id, 'flagged');
}

function clearFilters() {
    search.value = '';
    filterReason.value = '';
}
</script>

<template>
    <Head title="Posts" />

    <AdminLayout title="Post Management">
        <div class="space-y-5 pb-20">
            <!-- ── Tabs + Sort ───────────────────────────────────────────── -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <FilterTabs
                    :model-value="activeFilter"
                    :tabs="tabs"
                    @update:model-value="setFilter"
                />

                <div class="flex items-center gap-2">
                    <!-- Reported Posts button -->
                    <button
                        type="button"
                        :class="[
                            'flex items-center gap-2 rounded-xl border px-4 py-2.5 text-sm font-medium transition-all duration-150 select-none',
                            isReportedTab
                                ? 'border-red-200 bg-red-50 text-red-600'
                                : 'text-filter-inactive-text hover:text-filter-inactive-hover-text hover:bg-filter-inactive-hover-bg bg-bg-surface border-border-light',
                        ]"
                        @click="setFilter('reported')"
                    >
                        <i class="fas fa-flag text-[10px]" />
                        Reported Posts
                    </button>

                    <!-- Sort button -->
                    <button
                        v-if="!isArchivesTab"
                        type="button"
                        class="text-filter-inactive-text hover:text-filter-inactive-hover-text hover:bg-filter-inactive-hover-bg bg-bg-surface border-border-light flex items-center gap-2 rounded-xl border p-2.5 px-5 text-sm font-medium transition-all duration-150 select-none"
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
                            class="fas text-[10px]"
                            :class="
                                (isReportedTab ? reportedSort : currentSort) ===
                                'oldest'
                                    ? 'fa-arrow-up-wide-short'
                                    : 'fa-arrow-down-wide-short'
                            "
                        />
                        {{
                            (isReportedTab ? reportedSort : currentSort) ===
                            'oldest'
                                ? 'Oldest first'
                                : 'Latest first'
                        }}
                    </button>
                </div>
            </div>

            <!-- ── Stats strip ───────────────────────────────────────────── -->
            <div
                :class="[
                    'grid gap-3',
                    isReportedTab
                        ? 'grid-cols-1'
                        : 'grid-cols-1 sm:grid-cols-3',
                ]"
            >
                <div
                    v-for="stat in statItems"
                    :key="stat.label"
                    class="group flex items-center gap-4 rounded-2xl border border-gray-100 bg-white px-5 py-4 shadow-sm transition-shadow hover:shadow-md"
                >
                    <div
                        :class="[
                            'flex h-11 w-11 shrink-0 items-center justify-center rounded-xl transition-colors',
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
                            class="text-2xl font-extrabold tracking-tight text-gray-900"
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

            <!-- ── Filter bar ────────────────────────────────────────────── -->
            <div
                class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm"
            >
                <div
                    class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center"
                >
                    <!-- Search -->
                    <div class="relative min-w-0 flex-1 sm:max-w-xs">
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
                            class="focus:border-sidebar focus:ring-sidebar/20 w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pr-4 pl-9 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:bg-white focus:ring-2 focus:outline-none"
                        />
                    </div>

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
                                class="focus:border-sidebar focus:ring-sidebar/20 cursor-pointer appearance-none rounded-xl border border-gray-200 bg-gray-50 py-2.5 pr-8 pl-9 text-sm text-gray-700 transition-colors focus:bg-white focus:ring-2 focus:outline-none"
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
                        class="text-text-muted hover:text-text-primary flex items-center gap-1.5 rounded-xl border border-gray-200 bg-white px-3 py-2.5 text-xs font-medium transition-colors hover:border-gray-300"
                        @click="clearFilters"
                    >
                        <i class="fas fa-times text-[10px]" />
                        Clear
                    </button>
                </div>
            </div>

            <!-- ── Table ─────────────────────────────────────────────────── -->
            <div
                class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm"
            >
                <!-- Table label row -->
                <div class="border-b border-gray-100 px-5 py-3.5">
                    <p class="text-text-primary text-sm font-semibold">
                        {{
                            isReportedTab
                                ? 'Reported Posts'
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
                                <col class="w-22" />
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
                                        class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase"
                                    >
                                        Latest Report
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
                                                class="bg-sidebar flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white"
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
                                            class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-red-50 text-sm font-bold text-red-600"
                                        >
                                            {{ rp.report_count }}
                                        </span>
                                    </td>
                                    <td
                                        class="border-table-grid border-r px-4 py-3"
                                    >
                                        <span
                                            :class="[
                                                'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium',
                                                REASON_BADGE[rp.top_reason] ??
                                                    'bg-gray-100 text-gray-600',
                                            ]"
                                        >
                                            <i class="fas fa-flag text-[8px]" />
                                            {{ rp.top_reason }}
                                        </span>
                                    </td>
                                    <td
                                        class="border-table-grid border-r px-4 py-3"
                                    >
                                        <div
                                            class="flex items-center gap-1.5 text-xs text-gray-500"
                                        >
                                            <i
                                                class="far fa-calendar text-[10px]"
                                            />
                                            {{ rp.latest_report_date }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button
                                            type="button"
                                            class="bg-sidebar/10 text-sidebar hover:bg-sidebar inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all duration-150 hover:text-white"
                                            @click="openReportModal(rp)"
                                        >
                                            <i class="fas fa-eye text-[10px]" />
                                            View
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </template>

                        <!-- ── Archives tab ── -->
                        <template v-else-if="isArchivesTab">
                            <tbody>
                                <tr>
                                    <td class="py-16 text-center">
                                        <div
                                            class="flex flex-col items-center gap-3 text-gray-400"
                                        >
                                            <i
                                                class="fas fa-archive text-3xl opacity-30"
                                            />
                                            <p class="text-sm">
                                                No archived posts yet.
                                            </p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </template>

                        <!-- ── All / Flagged / Safe tabs ── -->
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
                                                class="bg-sidebar flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white"
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
                                                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium',
                                                moodStyle(post.mood).pill,
                                            ]"
                                        >
                                            <span
                                                :class="[
                                                    'h-1.5 w-1.5 rounded-full',
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
                                                'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium',
                                                post.status === 'flagged'
                                                    ? 'bg-status-flagged-bg text-status-flagged border border-red-100'
                                                    : 'bg-status-safe-bg text-status-safe border border-green-200',
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
                                                post.status === 'flagged'
                                                    ? 'Flagged'
                                                    : 'Safe'
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
                                                    'inline-flex items-center gap-1 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition-all',
                                                    post.status === 'flagged'
                                                        ? 'border-status-safe text-status-safe hover:bg-status-safe hover:text-white'
                                                        : 'border-status-flagged text-status-flagged hover:bg-status-flagged hover:text-white',
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
                                                class="bg-sidebar/10 text-sidebar hover:bg-sidebar inline-flex items-center gap-1 rounded-lg px-2.5 py-1.5 text-xs font-semibold transition-all hover:text-white"
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

        <!-- Pagination (regular tabs) -->
        <Pagination
            v-if="!isReportedTab && !isArchivesTab"
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
            @mark-safe="onMarkSafe"
            @flag="onFlag"
        />
    </AdminLayout>
</template>
