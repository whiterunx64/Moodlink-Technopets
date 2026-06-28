<script setup lang="ts">
import AdminLayout from '@/Layouts/AdminLayout.vue';
import ReportDetailModal from '@/Components/Posts/ReportDetailModal.vue';
import { Head } from '@inertiajs/vue3';
import { FlagIcon } from '@heroicons/vue/24/outline';
import { computed, ref } from 'vue';
import type { ReportedPost, ReportStatus, ReportReason } from '@/types';

// ─── Mock data ────────────────────────────────────────────────────────────────

const allPosts = ref<ReportedPost[]>([
    {
        id: 1,
        anonymous_name: 'SilentWave42',
        post_preview: "I feel like nobody in this school actually cares about what I'm going through. Every day it's the same cycle of pretending to be okay...",
        full_content: "I feel like nobody in this school actually cares about what I'm going through. Every day it's the same cycle of pretending to be okay while everything feels like it's falling apart. I don't even know why I keep showing up. The people here are toxic and I'm tired of dealing with them.",
        date_posted: 'Jun 20, 2025',
        program: 'BS Psychology',
        mood: 'Drained',
        report_count: 7,
        top_reason: 'Harassment',
        latest_report_date: 'Jun 23, 2025',
        status: 'pending',
        reason_breakdown: { harassment: 4, offensive_language: 2, bullying: 0, false_information: 0, spam: 0, other: 1 },
        reporters: [
            { id: 1, name: 'Maria Santos',   student_number: '2021-00123', program: 'BS Psychology', date_reported: 'Jun 23, 2025', reason: 'Harassment',        comment: "This post is targeting a specific group of students." },
            { id: 2, name: 'Juan dela Cruz',  student_number: '2022-00456', program: 'BS IT',         date_reported: 'Jun 23, 2025', reason: 'Harassment' },
            { id: 3, name: 'Ana Reyes',       student_number: '2020-00789', program: 'BS Nursing',    date_reported: 'Jun 22, 2025', reason: 'Offensive Language', comment: "The language used is inappropriate and hurtful." },
            { id: 4, name: 'Carlo Mendoza',   student_number: '2021-00321', program: 'BS Psychology', date_reported: 'Jun 22, 2025', reason: 'Harassment' },
            { id: 5, name: 'Lea Garcia',      student_number: '2023-00654', program: 'BS Education',  date_reported: 'Jun 21, 2025', reason: 'Offensive Language' },
            { id: 6, name: 'Ryan Torres',     student_number: '2022-00987', program: 'BS IT',         date_reported: 'Jun 21, 2025', reason: 'Harassment' },
            { id: 7, name: 'Sophia Lim',      student_number: '2021-00147', program: 'BS Nursing',    date_reported: 'Jun 20, 2025', reason: 'Other',             comment: "Seems like indirect venting about classmates." },
        ],
    },
    {
        id: 2,
        anonymous_name: 'QuietStorm88',
        post_preview: "Some people in my block are spreading lies about me and I know exactly who they are. Don't think I won't find out...",
        full_content: "Some people in my block are spreading lies about me and I know exactly who they are. Don't think I won't find out. I have screenshots and I'm not afraid to expose them publicly. They deserve everything that's coming to them.",
        date_posted: 'Jun 18, 2025',
        program: 'BS IT',
        mood: 'Stressed',
        report_count: 5,
        top_reason: 'Bullying',
        latest_report_date: 'Jun 21, 2025',
        status: 'flagged',
        reason_breakdown: { harassment: 1, offensive_language: 1, bullying: 3, false_information: 0, spam: 0, other: 0 },
        reporters: [
            { id: 1, name: 'Paolo Cruz',       student_number: '2021-00258', program: 'BS IT',        date_reported: 'Jun 21, 2025', reason: 'Bullying',   comment: "This is clearly directed at real students and feels threatening." },
            { id: 2, name: 'Kim Bautista',     student_number: '2022-00369', program: 'BS IT',        date_reported: 'Jun 20, 2025', reason: 'Bullying' },
            { id: 3, name: 'Trisha Villanueva', student_number: '2021-00741', program: 'BS Education', date_reported: 'Jun 20, 2025', reason: 'Harassment',  comment: "This reads like a threat to another student." },
            { id: 4, name: 'Kevin Tan',        student_number: '2023-00852', program: 'BS Nursing',   date_reported: 'Jun 19, 2025', reason: 'Bullying' },
            { id: 5, name: 'Diana Flores',     student_number: '2020-00963', program: 'BS IT',        date_reported: 'Jun 18, 2025', reason: 'Offensive Language' },
        ],
    },
    {
        id: 3,
        anonymous_name: 'MoonlitPath',
        post_preview: "People keep saying mental health awareness week is for everyone but only the popular students get noticed. The rest of us...",
        full_content: "People keep saying mental health awareness week is for everyone but only the popular students get noticed. The rest of us just sit here and suffer in silence. This school's MHW is a complete joke. All these events are just for show. Nobody actually cares.",
        date_posted: 'Jun 15, 2025',
        program: 'BS Education',
        mood: 'Drained',
        report_count: 3,
        top_reason: 'False Information',
        latest_report_date: 'Jun 17, 2025',
        status: 'pending',
        reason_breakdown: { harassment: 0, offensive_language: 1, bullying: 0, false_information: 2, spam: 0, other: 0 },
        reporters: [
            { id: 1, name: 'Grace Aquino',  student_number: '2022-00135', program: 'BS Psychology', date_reported: 'Jun 17, 2025', reason: 'False Information', comment: "Contains misleading claims about our school programs." },
            { id: 2, name: 'Samuel Ong',    student_number: '2021-00246', program: 'BS Education',  date_reported: 'Jun 16, 2025', reason: 'False Information' },
            { id: 3, name: 'Ria Castillo',  student_number: '2023-00357', program: 'BS Nursing',    date_reported: 'Jun 15, 2025', reason: 'Offensive Language' },
        ],
    },
    {
        id: 4,
        anonymous_name: 'EchoVault',
        post_preview: "Check out this online shop offering school supplies! Limited stocks only, message me for details. We accept GCash...",
        full_content: "Check out this online shop offering school supplies! Limited stocks only, message me for details. We accept GCash. Legit and trusted seller. This is not an ad, I just genuinely want to help fellow students get affordable supplies. Follow my account for updates!",
        date_posted: 'Jun 12, 2025',
        program: 'BS Nursing',
        mood: 'Excited',
        report_count: 9,
        top_reason: 'Spam',
        latest_report_date: 'Jun 14, 2025',
        status: 'resolved',
        reason_breakdown: { harassment: 0, offensive_language: 0, bullying: 0, false_information: 2, spam: 7, other: 0 },
        reporters: [
            { id: 1, name: 'Mark Lopez',  student_number: '2021-00468', program: 'BS IT',        date_reported: 'Jun 14, 2025', reason: 'Spam',             comment: "Clearly an advertising post — not appropriate here." },
            { id: 2, name: 'Jen Reyes',   student_number: '2022-00579', program: 'BS Nursing',   date_reported: 'Jun 14, 2025', reason: 'Spam' },
            { id: 3, name: 'Jolo Santos', student_number: '2020-00680', program: 'BS Psychology', date_reported: 'Jun 13, 2025', reason: 'Spam' },
            { id: 4, name: 'Bea Cruz',    student_number: '2023-00791', program: 'BS Education',  date_reported: 'Jun 13, 2025', reason: 'False Information', comment: "This might be a scam targeting students." },
            { id: 5, name: 'Rico Tan',    student_number: '2021-00802', program: 'BS IT',        date_reported: 'Jun 12, 2025', reason: 'Spam' },
            { id: 6, name: 'Mia Gomez',   student_number: '2022-00913', program: 'BS Nursing',   date_reported: 'Jun 12, 2025', reason: 'Spam' },
            { id: 7, name: 'Noel Garcia', student_number: '2021-01024', program: 'BS IT',        date_reported: 'Jun 12, 2025', reason: 'Spam' },
            { id: 8, name: 'Angel Ramos', student_number: '2023-01135', program: 'BS Psychology', date_reported: 'Jun 12, 2025', reason: 'False Information' },
            { id: 9, name: 'Vince Lim',   student_number: '2020-01246', program: 'BS Education',  date_reported: 'Jun 12, 2025', reason: 'Spam' },
        ],
    },
    {
        id: 5,
        anonymous_name: 'DrifterKite',
        post_preview: "I can't believe how our professor talks to us in class. Calling students out in front of everyone and making us feel stupid...",
        full_content: "I can't believe how our professor talks to us in class. Calling students out in front of everyone and making us feel stupid is not okay. This kind of teaching method is toxic and demeaning. We should all report this to the admin together.",
        date_posted: 'Jun 10, 2025',
        program: 'BS Psychology',
        mood: 'Stressed',
        report_count: 4,
        top_reason: 'Harassment',
        latest_report_date: 'Jun 12, 2025',
        status: 'pending',
        reason_breakdown: { harassment: 3, offensive_language: 1, bullying: 0, false_information: 0, spam: 0, other: 0 },
        reporters: [
            { id: 1, name: 'Carla Santos',   student_number: '2022-00135', program: 'BS Psychology', date_reported: 'Jun 12, 2025', reason: 'Harassment',        comment: "Could damage a faculty member's reputation without evidence." },
            { id: 2, name: 'Nico Dela Rosa', student_number: '2021-00246', program: 'BS Psychology', date_reported: 'Jun 11, 2025', reason: 'Harassment' },
            { id: 3, name: 'Trina Abad',     student_number: '2023-00357', program: 'BS IT',         date_reported: 'Jun 11, 2025', reason: 'Offensive Language' },
            { id: 4, name: 'Marc Reyes',     student_number: '2020-00468', program: 'BS Nursing',    date_reported: 'Jun 10, 2025', reason: 'Harassment' },
        ],
    },
    {
        id: 6,
        anonymous_name: 'TidePulse',
        post_preview: "Someone in our org keeps stealing credit for group work. If you're reading this, you know who you are. Karma is real...",
        full_content: "Someone in our org keeps stealing credit for group work. If you're reading this, you know who you are. Karma is real and everyone around you can see what you're doing. Stop riding on others' hard work and pretending it's yours.",
        date_posted: 'Jun 8, 2025',
        program: 'BS IT',
        mood: 'Stressed',
        report_count: 2,
        top_reason: 'Bullying',
        latest_report_date: 'Jun 9, 2025',
        status: 'resolved',
        reason_breakdown: { harassment: 0, offensive_language: 0, bullying: 2, false_information: 0, spam: 0, other: 0 },
        reporters: [
            { id: 1, name: 'Ava Villanueva', student_number: '2021-00579', program: 'BS IT', date_reported: 'Jun 9, 2025', reason: 'Bullying', comment: "Feels like indirectly calling out a specific org member." },
            { id: 2, name: 'Gio Torres',     student_number: '2022-00680', program: 'BS IT', date_reported: 'Jun 8, 2025', reason: 'Bullying' },
        ],
    },
    {
        id: 7,
        anonymous_name: 'CrimsonNote',
        post_preview: "There are people in this platform who are clearly faking their emotions just to get attention. Stop being so dramatic...",
        full_content: "There are people in this platform who are clearly faking their emotions just to get attention and sympathy. Stop being so dramatic. Real problems exist and you're here crying over nothing. Grow up.",
        date_posted: 'Jun 5, 2025',
        program: 'BS Nursing',
        mood: 'Content',
        report_count: 6,
        top_reason: 'Offensive Language',
        latest_report_date: 'Jun 8, 2025',
        status: 'flagged',
        reason_breakdown: { harassment: 2, offensive_language: 3, bullying: 1, false_information: 0, spam: 0, other: 0 },
        reporters: [
            { id: 1, name: 'Bianca Sta. Cruz', student_number: '2021-00693', program: 'BS Psychology', date_reported: 'Jun 8, 2025',  reason: 'Offensive Language', comment: "This invalidates the struggles of other students." },
            { id: 2, name: 'Renz Navarro',     student_number: '2022-00704', program: 'BS Nursing',    date_reported: 'Jun 7, 2025',  reason: 'Offensive Language' },
            { id: 3, name: 'Hope Castillo',    student_number: '2020-00815', program: 'BS IT',         date_reported: 'Jun 7, 2025',  reason: 'Harassment',         comment: "This mocks people who are genuinely struggling." },
            { id: 4, name: 'Felix Santos',     student_number: '2023-00926', program: 'BS Education',  date_reported: 'Jun 6, 2025',  reason: 'Bullying' },
            { id: 5, name: 'Lyra Delos Reyes', student_number: '2021-01037', program: 'BS Psychology', date_reported: 'Jun 6, 2025',  reason: 'Offensive Language' },
            { id: 6, name: 'Josh Morales',     student_number: '2022-01148', program: 'BS Nursing',    date_reported: 'Jun 5, 2025',  reason: 'Harassment' },
        ],
    },
]);

// ─── Filter state ─────────────────────────────────────────────────────────────

const search       = ref('');
const filterReason = ref('');
const filterDate   = ref('');

// ─── Computed ─────────────────────────────────────────────────────────────────

const stats = computed(() => ({
    total: allPosts.value.length,
}));

const filteredPosts = computed(() =>
    allPosts.value.filter((p) => {
        const q = search.value.trim().toLowerCase();
        if (q && !p.anonymous_name.toLowerCase().includes(q)) return false;
        if (filterReason.value && p.top_reason !== filterReason.value) return false;
        return true;
    }),
);

// ─── Badge helpers ────────────────────────────────────────────────────────────

const REASON_BADGE: Record<string, string> = {
    'Harassment':        'bg-red-50 text-red-700',
    'Offensive Language': 'bg-orange-50 text-orange-700',
    'Bullying':          'bg-purple-50 text-purple-700',
    'False Information': 'bg-blue-50 text-blue-700',
    'Spam':              'bg-gray-100 text-gray-600',
    'Other':             'bg-gray-100 text-gray-600',
};

const REPORT_REASONS: ReportReason[] = [
    'Harassment',
    'Offensive Language',
    'Bullying',
    'False Information',
    'Spam',
    'Other',
];

// ─── Modal state & actions ────────────────────────────────────────────────────

const selectedReport = ref<ReportedPost | null>(null);

function openModal(post: ReportedPost) {
    selectedReport.value = { ...post };
}

function closeModal() {
    selectedReport.value = null;
}

function updatePostStatus(id: number, status: ReportStatus) {
    const idx = allPosts.value.findIndex(p => p.id === id);
    if (idx !== -1) allPosts.value[idx] = { ...allPosts.value[idx], status };
    if (selectedReport.value?.id === id) {
        selectedReport.value = { ...selectedReport.value, status };
    }
    closeModal();
}

function onMarkSafe() {
    if (selectedReport.value) updatePostStatus(selectedReport.value.id, 'resolved');
}

function onFlag() {
    if (selectedReport.value) updatePostStatus(selectedReport.value.id, 'flagged');
}

function clearFilters() {
    search.value = '';
    filterReason.value = '';
    filterDate.value = '';
}
</script>

<template>

    <Head title="Reported Posts" />

    <AdminLayout title="Reported Posts">
        <div class="space-y-6 pb-6">

            <!-- ── Page intro ───────────────────────────────────────────── -->
            <div>
                <p class="text-text-muted mt-1 text-sm">
                    Review community reports submitted by students before taking moderation action.
                </p>
            </div>

            <!-- ── Stats strip ───────────────────────────────────────────── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">

                <div class="group flex items-center gap-4 rounded-2xl border border-gray-100 bg-white px-5 py-4 shadow-sm transition-shadow hover:shadow-md">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-blue-50 transition-colors group-hover:bg-blue-100">
                        <FlagIcon class="h-5 w-5 text-blue-500" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-2xl font-extrabold tracking-tight text-gray-900">{{ stats.total }}</p>
                        <p class="mt-0.5 truncate text-xs font-medium text-gray-400">Total Reported</p>
                    </div>
                </div>

            </div>

            <!-- ── Filter bar ────────────────────────────────────────────── -->
            <div class="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm">
                <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-center">

                    <!-- Search -->
                    <div class="relative min-w-0 flex-1 sm:max-w-xs">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8" /><line x1="21" y1="21" x2="16.65" y2="16.65" />
                            </svg>
                        </span>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Search by anonymous name..."
                            class="w-full rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-9 pr-4 text-sm text-gray-900 placeholder-gray-400 transition-colors focus:border-sidebar focus:bg-white focus:ring-2 focus:ring-sidebar/20 focus:outline-none"
                        />
                    </div>

                    <!-- Report Reason -->
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                            <i class="fas fa-tag text-xs" />
                        </span>
                        <select
                            v-model="filterReason"
                            class="cursor-pointer appearance-none rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-9 pr-8 text-sm text-gray-700 transition-colors focus:border-sidebar focus:bg-white focus:ring-2 focus:ring-sidebar/20 focus:outline-none"
                        >
                            <option value="">All Reasons</option>
                            <option v-for="r in REPORT_REASONS" :key="r" :value="r">{{ r }}</option>
                        </select>
                        <span class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="6 9 12 15 18 9" />
                            </svg>
                        </span>
                    </div>

                    <!-- Date Reported -->
                    <div class="relative">
                        <span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-gray-400">
                            <i class="far fa-calendar text-xs" />
                        </span>
                        <input
                            v-model="filterDate"
                            type="date"
                            class="cursor-pointer rounded-xl border border-gray-200 bg-gray-50 py-2.5 pl-9 pr-3 text-sm text-gray-700 transition-colors focus:border-sidebar focus:bg-white focus:ring-2 focus:ring-sidebar/20 focus:outline-none"
                        />
                    </div>

                    <!-- Clear filters -->
                    <button
                        v-if="search || filterReason || filterDate"
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
            <div class="overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm">

                <!-- Table header label -->
                <div class="border-b border-gray-100 px-5 py-3.5">
                    <div class="flex items-center justify-between">
                        <p class="text-text-primary text-sm font-semibold">
                            Reported Posts
                            <span class="text-text-muted ml-1.5 text-xs font-normal">({{ filteredPosts.length }} total)</span>
                        </p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <colgroup>
                            <col class="w-[170px]" />
                            <col class="w-auto" />
                            <col class="w-[100px]" />
                            <col class="w-[160px]" />
                            <col class="w-[130px]" />
                            <col class="w-[90px]" />
                        </colgroup>

                        <thead class="bg-table-header border-b border-table-grid">
                            <tr>
                                <th class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase">
                                    Anonymous Name
                                </th>
                                <th class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase">
                                    Post Preview
                                </th>
                                <th class="border-table-grid border-r px-4 py-3 text-center text-[11px] font-semibold tracking-wide text-gray-500 uppercase">
                                    Reports
                                </th>
                                <th class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase">
                                    Top Reason
                                </th>
                                <th class="border-table-grid border-r px-4 py-3 text-left text-[11px] font-semibold tracking-wide text-gray-500 uppercase">
                                    Latest Report
                                </th>
                                <th class="px-4 py-3 text-center text-[11px] font-semibold tracking-wide text-gray-500 uppercase">
                                    Action
                                </th>
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-table-grid">

                            <!-- Empty state -->
                            <tr v-if="filteredPosts.length === 0">
                                <td colspan="6" class="py-16 text-center">
                                    <div class="flex flex-col items-center gap-3 text-gray-400">
                                        <i class="fas fa-flag text-3xl opacity-30" />
                                        <p class="text-sm">No reported posts match the current filters.</p>
                                        <button
                                            type="button"
                                            class="text-sidebar hover:text-sidebar/80 text-xs font-medium underline underline-offset-2"
                                            @click="clearFilters"
                                        >
                                            Clear filters
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Data rows -->
                            <tr
                                v-for="post in filteredPosts"
                                :key="post.id"
                                class="bg-table-row transition-colors hover:bg-table-row-hover"
                            >
                                <!-- Anonymous Name -->
                                <td class="border-table-grid border-r px-4 py-3">
                                    <div class="flex items-center gap-2.5">
                                        <div class="bg-sidebar flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold text-white">
                                            {{ post.anonymous_name.charAt(0).toUpperCase() }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="text-text-primary truncate text-sm font-semibold">
                                                {{ post.anonymous_name }}
                                            </p>
                                            <p class="text-text-muted truncate text-xs">{{ post.program }}</p>
                                        </div>
                                    </div>
                                </td>

                                <!-- Post Preview -->
                                <td class="border-table-grid border-r px-4 py-3">
                                    <p class="text-text-secondary line-clamp-2 max-w-xs text-sm leading-relaxed">
                                        {{ post.post_preview }}
                                    </p>
                                </td>

                                <!-- Report Count -->
                                <td class="border-table-grid border-r px-4 py-3 text-center">
                                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-red-50 text-sm font-bold text-red-600">
                                        {{ post.report_count }}
                                    </span>
                                </td>

                                <!-- Top Reason -->
                                <td class="border-table-grid border-r px-4 py-3">
                                    <span :class="[
                                        'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium',
                                        REASON_BADGE[post.top_reason] ?? 'bg-gray-100 text-gray-600',
                                    ]">
                                        <i class="fas fa-flag text-[8px]" />
                                        {{ post.top_reason }}
                                    </span>
                                </td>

                                <!-- Latest Report Date -->
                                <td class="border-table-grid px-4 py-3">
                                    <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                        <i class="far fa-calendar text-[10px]" />
                                        {{ post.latest_report_date }}
                                    </div>
                                </td>

                                <!-- View action -->
                                <td class="px-4 py-3 text-center">
                                    <button
                                        type="button"
                                        class="bg-sidebar/10 text-sidebar hover:bg-sidebar hover:text-white inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-semibold transition-all duration-150"
                                        @click="openModal(post)"
                                    >
                                        <i class="fas fa-eye text-[10px]" />
                                        View
                                    </button>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </AdminLayout>

    <!-- Report Detail Modal -->
    <ReportDetailModal
        :report="selectedReport"
        :show="selectedReport !== null"
        @close="closeModal"
        @mark-safe="onMarkSafe"
        @flag="onFlag"
    />

</template>
