<script setup lang="ts">
import PeriodFilter from '@/Components/SummaryReports/PeriodFilter.vue';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import type { ProgramDetail, SummaryPeriod } from '@/types';
import {
    ArrowDownIcon,
    ArrowLeftIcon,
    ArrowUpIcon,
    MagnifyingGlassIcon,
    MinusIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, type Component } from 'vue';

const props = defineProps<{
    detail: ProgramDetail;
    filters: { period: SummaryPeriod; search: string | null };
}>();

const OVERVIEW_ITEMS = computed(() => [
    {
        label: 'Total',
        value: props.detail.total,
        color: 'text-text-primary',
        bg: 'bg-gray-50',
    },
    {
        label: 'Excited',
        value: props.detail.excited,
        color: 'text-green-600',
        bg: 'bg-green-50',
    },
    {
        label: 'Content',
        value: props.detail.content,
        color: 'text-blue-600',
        bg: 'bg-blue-50',
    },
    {
        label: 'Stressed',
        value: props.detail.stressed,
        color: 'text-orange-500',
        bg: 'bg-orange-50',
    },
    {
        label: 'Drained',
        value: props.detail.drained,
        color: 'text-red-500',
        bg: 'bg-red-50',
    },
    {
        label: 'At-Risk',
        value: props.detail.at_risk,
        color: 'text-red-600',
        bg: 'bg-red-50',
    },
]);

// Server-side search: the term is a query param; the backend filters the
// student list in the DB. Runs only on an explicit user action (Enter or the
// Search button), never on every keystroke.
const search = ref(props.filters.search ?? '');

function submitSearch() {
    router.get(
        route('reports.programs.show', props.detail.program),
        { period: props.filters.period, search: search.value.trim() },
        {
            preserveScroll: true,
            replace: true,
        },
    );
}

function clearSearch() {
    search.value = '';
    submitSearch();
}

const TREND_STYLE: Record<string, { icon: Component; cls: string }> = {
    Declining: {
        icon: ArrowDownIcon,
        cls: 'bg-red-50 text-red-500 border border-red-100',
    },
    Stable: {
        icon: MinusIcon,
        cls: 'bg-gray-50 text-text-muted border border-border-light',
    },
    Improving: {
        icon: ArrowUpIcon,
        cls: 'bg-green-50 text-green-600 border border-green-100',
    },
};

function barPct(count: number): string {
    return props.detail.total > 0
        ? `${Math.round((count / props.detail.total) * 100)}%`
        : '0%';
}

function onPeriodChange(p: SummaryPeriod) {
    router.get(
        route('reports.programs.show', props.detail.program),
        { period: p },
        {
            preserveState: true,
            replace: true,
        },
    );
}

function goBack() {
    router.get(route('reports.index'), {
        period: props.filters.period,
        tab: 'programs',
    });
}

function openStudent(studentId: number) {
    router.get(route('reports.students.show', studentId), {
        period: props.filters.period,
    });
}

// Download the whole program report as a server-rendered PDF.
function exportPdf() {
    const url = route('reports.programs.pdf', props.detail.program) as string;
    const params = new URLSearchParams({ period: props.filters.period });
    window.open(`${url}?${params.toString()}`, '_blank');
}
</script>

<template>
    <Head :title="`Program ${detail.program}`" />

    <AdminLayout :title="`Program ${detail.program}`">
        <div class="space-y-5">
            <PeriodFilter
                :model-value="filters.period"
                @update:model-value="onPeriodChange"
                @export="exportPdf"
            />

            <div class="flex flex-col gap-3">
                <button
                    type="button"
                    aria-label="Back"
                    class="text-text-muted hover:text-text-primary inline-flex w-fit items-center gap-1.5 text-xs font-medium transition-colors"
                    @click="goBack"
                >
                    <ArrowLeftIcon class="h-4 w-4" />
                    Back
                </button>
                <div>
                    <h2 class="text-text-primary text-lg font-bold">
                        Program {{ detail.program }}
                    </h2>
                    <p class="text-text-muted text-xs">
                        Mood summary and student list
                    </p>
                </div>
            </div>

            <div
                class="border-border-light border bg-white p-6 shadow-sm"
            >
                <h3 class="text-text-primary mb-4 text-sm font-semibold">
                    Mood Overview
                </h3>

                <div class="mb-5 grid grid-cols-3 gap-3 sm:grid-cols-6">
                    <div
                        v-for="item in OVERVIEW_ITEMS"
                        :key="item.label"
                        :class="['rounded-xl p-4 text-center', item.bg]"
                    >
                        <p :class="['text-2xl font-extrabold', item.color]">
                            {{ item.value }}
                        </p>
                        <p class="text-text-muted mt-1 text-xs">
                            {{ item.label }}
                        </p>
                    </div>
                </div>

                <div class="flex h-3 overflow-hidden rounded-full">
                    <div
                        class="h-full bg-green-500"
                        :style="{ width: barPct(detail.excited) }"
                    />
                    <div
                        class="h-full bg-blue-500"
                        :style="{ width: barPct(detail.content) }"
                    />
                    <div
                        class="h-full bg-orange-400"
                        :style="{ width: barPct(detail.stressed) }"
                    />
                    <div
                        class="h-full bg-red-400"
                        :style="{ width: barPct(detail.drained) }"
                    />
                </div>
            </div>

            <div
                class="border-border-light border bg-white p-6 shadow-sm"
            >
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-text-primary text-sm font-semibold">
                            Students
                        </h3>
                        <p class="text-text-muted mt-0.5 text-xs">
                            {{ detail.students.length }} students
                            <span v-if="filters.search"> · results for “{{ filters.search }}”</span>
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <div class="relative">
                            <MagnifyingGlassIcon
                                class="text-text-muted pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2"
                            />
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Search by name or student no."
                                class="border-border-light text-text-primary placeholder:text-text-muted focus:ring-sidebar/20 focus:border-sidebar w-64 border bg-white py-2 pr-8 pl-9 text-sm transition-colors focus:ring-2 focus:outline-none"
                                @keyup.enter="submitSearch"
                            />
                            <button
                                v-if="search"
                                type="button"
                                class="text-text-muted hover:text-text-primary absolute top-1/2 right-2 -translate-y-1/2"
                                aria-label="Clear search"
                                @click="clearSearch"
                            >
                                <XMarkIcon class="h-4 w-4" />
                            </button>
                        </div>
                        <button
                            type="button"
                            class="bg-sidebar hover:bg-sidebar/90 border-sidebar border px-4 py-2 text-sm font-medium text-white transition-colors"
                            @click="submitSearch"
                        >
                            Search
                        </button>
                    </div>
                </div>

                <div class="h-140 overflow-y-auto pr-1">
                    <div
                        class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
                    >
                        <button
                            v-for="student in detail.students"
                            :key="student.id"
                            type="button"
                            class="border-border-light hover:border-sidebar/30 cursor-pointer rounded-xl border p-4 text-left transition-all hover:bg-gray-100"
                            @click="openStudent(student.id)"
                        >
                            <div class="mb-3 flex items-center gap-3">
                                <div
                                    class="text-text-primary flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gray-200 text-xs font-bold"
                                >
                                    {{ student.initials }}
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="text-text-primary truncate text-sm font-semibold"
                                    >
                                        {{ student.name }}
                                    </p>
                                    <p class="text-text-muted text-xs">
                                        {{ student.student_number }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center justify-between">
                                <span
                                    class="text-text-muted rounded-full bg-gray-100 px-2.5 py-1 text-xs font-medium"
                                >
                                    {{ student.year_level }}
                                </span>
                                <span
                                    :class="[
                                        'inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium',
                                        TREND_STYLE[student.trend]?.cls,
                                    ]"
                                >
                                    <component
                                        :is="TREND_STYLE[student.trend]?.icon"
                                        class="h-3 w-3"
                                    />
                                    {{ student.trend }}
                                </span>
                            </div>
                        </button>
                    </div>

                    <div
                        v-if="detail.students.length === 0"
                        class="text-text-muted flex h-full items-center justify-center text-center text-sm"
                    >
                        No students match your search.
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
