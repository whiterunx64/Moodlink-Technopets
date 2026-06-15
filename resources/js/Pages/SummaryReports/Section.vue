<script setup lang="ts">
import { computed, ref, type Component } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowDownIcon,
    ArrowLeftIcon,
    ArrowUpIcon,
    MagnifyingGlassIcon,
    MinusIcon,
} from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PeriodFilter from '@/Components/SummaryReports/PeriodFilter.vue';
import type { SectionDetail, SummaryPeriod } from '@/types';

const props = defineProps<{
    detail: SectionDetail;
    filters: { period: SummaryPeriod; search: string | null };
}>();

// ── Mood overview items ───────────────────────────────────────────────────────
const OVERVIEW_ITEMS = computed(() => [
    { label: 'Total',    value: props.detail.total,    color: 'text-text-primary', bg: 'bg-gray-50' },
    { label: 'Excited',  value: props.detail.excited,  color: 'text-green-600',   bg: 'bg-green-50' },
    { label: 'Content',  value: props.detail.content,  color: 'text-blue-600',    bg: 'bg-blue-50' },
    { label: 'Stressed', value: props.detail.stressed, color: 'text-orange-500',  bg: 'bg-orange-50' },
    { label: 'Drained',  value: props.detail.drained,  color: 'text-red-500',     bg: 'bg-red-50' },
    { label: 'At-Risk',  value: props.detail.atRisk,   color: 'text-red-600',     bg: 'bg-red-50' },
]);

// ── Client-side student search ────────────────────────────────────────────────
const search = ref('');

const filteredStudents = computed(() => {
    const q = search.value.toLowerCase().trim();
    if (!q) return props.detail.students;
    return props.detail.students.filter(
        (s) => s.name.toLowerCase().includes(q) || s.studentNumber.toLowerCase().includes(q),
    );
});

// ── Trend badge styles ────────────────────────────────────────────────────────
const TREND_STYLE: Record<string, { icon: Component; cls: string }> = {
    Declining: { icon: ArrowDownIcon, cls: 'bg-red-50 text-red-500 border border-red-100' },
    Stable:    { icon: MinusIcon,     cls: 'bg-gray-50 text-text-muted border border-border-light' },
    Improving: { icon: ArrowUpIcon,   cls: 'bg-green-50 text-green-600 border border-green-100' },
};

// ── Distribution bar width helper ─────────────────────────────────────────────
function barPct(count: number): string {
    return props.detail.total > 0 ? `${Math.round((count / props.detail.total) * 100)}%` : '0%';
}

// ── Navigation ────────────────────────────────────────────────────────────────
function onPeriodChange(p: SummaryPeriod) {
    router.get(route('summary-reports.section', props.detail.section), { period: p }, {
        preserveState: true,
        replace: true,
    });
}

function goBack() {
    router.get(route('summary-reports.index'), { period: props.filters.period, tab: 'sections' });
}

function openStudent(studentId: number) {
    router.get(route('summary-reports.student', studentId), { period: props.filters.period });
}
</script>

<template>
    <Head :title="`Section ${detail.section}`" />

    <AdminLayout :title="`Section ${detail.section}`">
        <div class="space-y-5">

            <!-- Period filter + Export PDF -->
            <PeriodFilter :model-value="filters.period" @update:model-value="onPeriodChange" />

            <!-- Back + Title -->
            <div class="flex items-center gap-3">
                <button type="button"
                    class="w-8 h-8 rounded-full border border-border-light bg-white flex items-center justify-center hover:bg-gray-50 transition-colors"
                    @click="goBack">
                    <ArrowLeftIcon class="w-4 h-4 text-text-secondary" />
                </button>
                <div>
                    <h2 class="text-lg font-bold text-text-primary">Section {{ detail.section }}</h2>
                    <p class="text-xs text-text-muted">Mood summary and student list</p>
                </div>
            </div>

            <!-- Mood Overview -->
            <div class="bg-white rounded-2xl border border-border-light shadow-sm p-6">
                <h3 class="text-sm font-semibold text-text-primary mb-4">Mood Overview</h3>

                <div class="grid grid-cols-3 sm:grid-cols-6 gap-3 mb-5">
                    <div v-for="item in OVERVIEW_ITEMS" :key="item.label"
                        :class="['rounded-xl p-4 text-center', item.bg]">
                        <p :class="['text-2xl font-extrabold', item.color]">{{ item.value }}</p>
                        <p class="text-xs text-text-muted mt-1">{{ item.label }}</p>
                    </div>
                </div>

                <!-- Distribution bar -->
                <div class="flex h-3 rounded-full overflow-hidden">
                    <div class="bg-green-500 h-full" :style="{ width: barPct(detail.excited) }" />
                    <div class="bg-blue-500 h-full"   :style="{ width: barPct(detail.content) }" />
                    <div class="bg-orange-400 h-full" :style="{ width: barPct(detail.stressed) }" />
                    <div class="bg-red-400 h-full"    :style="{ width: barPct(detail.drained) }" />
                </div>
            </div>

            <!-- Students list -->
            <div class="bg-white rounded-2xl border border-border-light shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-semibold text-text-primary">Students</h3>
                        <p class="text-xs text-text-muted mt-0.5">
                            {{ filteredStudents.length }} of {{ detail.students.length }} students
                        </p>
                    </div>

                    <div class="relative">
                        <MagnifyingGlassIcon
                            class="w-4 h-4 text-text-muted absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <input v-model="search" type="text" placeholder="Search by name or student no."
                            class="pl-9 pr-4 py-2 text-sm rounded-xl border border-border-light bg-white text-text-primary placeholder:text-text-muted focus:outline-none focus:ring-2 focus:ring-sidebar/20 focus:border-sidebar transition-colors w-64" />
                    </div>
                </div>

                <!-- Student cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                    <button v-for="student in filteredStudents" :key="student.id" type="button"
                        class="text-left border border-border-light rounded-xl p-4 hover:bg-gray-50 hover:border-sidebar/30 transition-all"
                        @click="openStudent(student.id)">
                        <div class="flex items-center gap-3 mb-3">
                            <div
                                class="w-9 h-9 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-text-primary shrink-0">
                                {{ student.initials }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-text-primary truncate">{{ student.name }}</p>
                                <p class="text-xs text-text-muted">{{ student.studentNumber }}</p>
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <span class="px-2.5 py-1 rounded-full bg-gray-100 text-text-muted text-xs font-medium">
                                {{ student.yearLevel }}
                            </span>
                            <span :class="['inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium', TREND_STYLE[student.trend]?.cls]">
                                <component :is="TREND_STYLE[student.trend]?.icon" class="w-3 h-3" />
                                {{ student.trend }}
                            </span>
                        </div>
                    </button>
                </div>

                <div v-if="filteredStudents.length === 0" class="py-16 text-center text-sm text-text-muted">
                    No students match your search.
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
