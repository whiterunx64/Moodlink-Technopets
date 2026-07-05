<script setup lang="ts">
import type { ReportedPost, ReportStatus } from '@/types';

defineProps<{
    report: ReportedPost | null;
    show: boolean;
}>();

const emit = defineEmits<{
    close: [];
    unreport: ['safe' | 'flag'];
}>();

const REASON_ROWS = [
    { key: 'harassment', label: 'Harassment', bar: 'bg-red-400' },
    {
        key: 'offensive_language',
        label: 'Offensive Language',
        bar: 'bg-orange-400',
    },
    { key: 'bullying', label: 'Bullying', bar: 'bg-purple-400' },
    {
        key: 'false_information',
        label: 'False Information',
        bar: 'bg-blue-400',
    },
    { key: 'spam', label: 'Spam', bar: 'bg-gray-400' },
    { key: 'other', label: 'Other', bar: 'bg-gray-300' },
] as const;

const REASON_BADGE: Record<string, string> = {
    Harassment: 'bg-red-50 text-red-700 border border-red-100',
    'Offensive Language':
        'bg-orange-50 text-orange-700 border border-orange-100',
    Bullying: 'bg-purple-50 text-purple-700 border border-purple-100',
    'False Information': 'bg-blue-50 text-blue-700 border border-blue-100',
    Spam: 'bg-gray-100 text-gray-600 border border-gray-200',
    Other: 'bg-gray-100 text-gray-600 border border-gray-200',
};

const STATUS_BADGE: Record<ReportStatus, string> = {
    pending: 'bg-amber-50 text-amber-700 border border-amber-200',
    flagged: 'bg-red-50 text-red-600 border border-red-100',
    resolved: 'bg-green-50 text-green-700 border border-green-200',
};

const STATUS_LABEL: Record<ReportStatus, string> = {
    pending: 'Pending Review',
    flagged: 'Flagged',
    resolved: 'Resolved',
};

function pct(count: number, total: number): number {
    return total === 0 ? 0 : Math.round((count / total) * 100);
}
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition ease-out duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition ease-in duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="show && report"
                class="fixed inset-0 z-50 flex items-center justify-center p-4"
            >
                <!-- Overlay -->
                <div
                    class="absolute inset-0 bg-black/40 backdrop-blur-sm"
                    @click="emit('close')"
                />

                <!-- Modal card -->
                <div
                    class="relative mx-auto flex w-full max-w-2xl flex-col bg-white shadow-xl"
                    style="max-height: 90vh"
                >
                    <!-- ── Header ────────────────────────────────────── -->
                    <div
                        class="border-border-light flex shrink-0 items-start justify-between border-b px-6 pt-5 pb-4"
                    >
                        <div>
                            <h3 class="text-text-primary text-base font-bold">
                                Report Detail
                            </h3>
                            <p class="text-text-muted mt-0.5 text-xs">
                                Review submitted reports before taking
                                moderation action
                            </p>
                        </div>
                        <button
                            type="button"
                            class="text-text-muted hover:bg-hover-soft ml-4 flex h-7 w-7 shrink-0 items-center justify-center transition-colors"
                            @click="emit('close')"
                        >
                            <i class="fas fa-times text-sm" />
                        </button>
                    </div>

                    <!-- ── Scrollable body ────────────────────────────── -->
                    <div class="flex-1 space-y-6 overflow-y-auto px-6 py-5">
                        <!-- Post Information -->
                        <section>
                            <p
                                class="text-text-muted mb-3 text-[10px] font-semibold tracking-wider uppercase"
                            >
                                Post Information
                            </p>

                            <!-- Author row -->
                            <div
                                class="mb-4 flex items-start justify-between gap-3"
                            >
                                <div class="flex items-center gap-3">
                                    <div
                                        class="bg-sidebar flex h-10 w-10 shrink-0 items-center justify-center text-sm font-bold text-white"
                                    >
                                        {{
                                            (report.anonymous_name ?? 'U')
                                                .charAt(0)
                                                .toUpperCase()
                                        }}
                                    </div>
                                    <div>
                                        <p
                                            class="text-text-primary text-sm font-semibold"
                                        >
                                            {{ report.anonymous_name }}
                                        </p>
                                        <p
                                            class="text-text-muted mt-0.5 flex items-center gap-1.5 text-xs"
                                        >
                                            <i
                                                class="far fa-clock text-[10px]"
                                            />
                                            Posted on {{ report.date_posted }}
                                        </p>
                                    </div>
                                </div>
                                <span
                                    :class="[
                                        'inline-flex shrink-0 items-center gap-1 px-2.5 py-1 text-xs font-medium',
                                        STATUS_BADGE[report.status],
                                    ]"
                                >
                                    <i
                                        :class="[
                                            'fas text-[8px]',
                                            report.status === 'resolved'
                                                ? 'fa-check'
                                                : report.status === 'flagged'
                                                  ? 'fa-flag'
                                                  : 'fa-clock',
                                        ]"
                                    />
                                    {{ STATUS_LABEL[report.status] }}
                                </span>
                            </div>

                            <!-- Full post content -->
                            <div class="bg-gray-50 px-4 py-4">
                                <p
                                    class="text-text-muted mb-2 text-[10px] font-semibold tracking-wider uppercase"
                                >
                                    Post Content
                                </p>
                                <p
                                    class="text-text-secondary text-sm leading-relaxed"
                                >
                                    {{ report.full_content }}
                                </p>
                            </div>
                        </section>

                        <!-- Reports Summary -->
                        <section>
                            <p
                                class="text-text-muted mb-3 text-[10px] font-semibold tracking-wider uppercase"
                            >
                                Reports Summary
                            </p>

                            <div
                                class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-4"
                            >
                                <!-- Total count -->
                                <div
                                    class="mb-4 flex items-center justify-between"
                                >
                                    <span
                                        class="text-text-secondary text-sm font-medium"
                                        >Total Reports Received</span
                                    >
                                    <span
                                        class="text-text-primary text-2xl font-extrabold tracking-tight"
                                    >
                                        {{ report.report_count }}
                                    </span>
                                </div>

                                <!-- Breakdown bars -->
                                <div class="space-y-3">
                                    <div
                                        v-for="row in REASON_ROWS"
                                        :key="row.key"
                                        class="space-y-1"
                                    >
                                        <div
                                            class="flex items-center justify-between text-xs"
                                        >
                                            <span class="text-text-secondary">{{
                                                row.label
                                            }}</span>
                                            <span
                                                class="text-text-muted font-medium"
                                            >
                                                {{
                                                    report.reason_breakdown[
                                                        row.key
                                                    ]
                                                }}
                                                <span class="text-gray-400"
                                                    >({{
                                                        pct(
                                                            report
                                                                .reason_breakdown[
                                                                row.key
                                                            ],
                                                            report.report_count,
                                                        )
                                                    }}%)</span
                                                >
                                            </span>
                                        </div>
                                        <div
                                            class="h-1.5 overflow-hidden rounded-full bg-gray-200"
                                        >
                                            <div
                                                :class="[
                                                    'h-full rounded-full transition-all duration-500',
                                                    row.bar,
                                                ]"
                                                :style="`width: ${pct(report.reason_breakdown[row.key], report.report_count)}%`"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                        <!-- Who Reported -->
                        <section>
                            <p
                                class="text-text-muted mb-3 text-[10px] font-semibold tracking-wider uppercase"
                            >
                                Who Reported
                                <span
                                    class="ml-1.5 rounded-full bg-gray-100 px-1.5 py-0.5 text-[9px] font-bold text-gray-500"
                                >
                                    {{ report.reporters.length }}
                                </span>
                            </p>

                            <div class="space-y-3">
                                <div
                                    v-for="reporter in report.reporters"
                                    :key="reporter.id"
                                    class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm transition-shadow hover:shadow-md"
                                >
                                    <div class="flex items-start gap-3">
                                        <!-- Avatar -->
                                        <div
                                            class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-gray-100 text-xs font-bold text-gray-500"
                                        >
                                            {{ reporter.name.charAt(0) }}
                                        </div>

                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="flex items-center justify-between gap-2"
                                            >
                                                <p
                                                    class="text-text-primary truncate text-sm font-semibold"
                                                >
                                                    {{ reporter.name }}
                                                </p>
                                                <span
                                                    class="text-text-muted shrink-0 text-xs"
                                                >
                                                    {{ reporter.date_reported }}
                                                </span>
                                            </div>
                                            <p
                                                class="text-text-muted mt-0.5 text-xs"
                                            >
                                                {{ reporter.student_number }} ·
                                                {{ reporter.program }}
                                            </p>

                                            <!-- Reason badge -->
                                            <div class="mt-2">
                                                <span
                                                    :class="[
                                                        'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[11px] font-medium',
                                                        REASON_BADGE[
                                                            reporter.reason
                                                        ] ??
                                                            'bg-gray-100 text-gray-600',
                                                    ]"
                                                >
                                                    <i
                                                        class="fas fa-flag text-[8px]"
                                                    />
                                                    {{ reporter.reason }}
                                                </span>
                                            </div>

                                            <!-- Optional comment -->
                                            <p
                                                v-if="reporter.comment"
                                                class="text-text-secondary mt-2 rounded-lg bg-gray-50 px-3 py-2 text-xs leading-relaxed italic"
                                            >
                                                "{{ reporter.comment }}"
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </div>

                    <!-- ── Footer ────────────────────────────────────── -->
                    <div
                        class="border-border-light flex shrink-0 items-center justify-end gap-3 border-t px-6 py-4"
                    >
                        <button
                            type="button"
                            class="border-status-safe text-status-safe hover:bg-status-safe inline-flex cursor-pointer items-center gap-1.5 rounded-xl border px-4 py-2 text-sm font-semibold transition-all hover:text-white"
                            @click="emit('unreport', 'safe')"
                        >
                            <i class="fas fa-check text-xs" />
                            Safe
                        </button>
                        <button
                            type="button"
                            class="border-status-flagged text-status-flagged hover:bg-status-flagged inline-flex cursor-pointer items-center gap-1.5 rounded-xl border px-4 py-2 text-sm font-semibold transition-all hover:text-white"
                            @click="emit('unreport', 'flag')"
                        >
                            <i class="fas fa-flag text-xs" />
                            Flag
                        </button>
                        <button
                            type="button"
                            class="bg-sidebar hover:bg-sidebar/90 cursor-pointer rounded-xl px-4 py-2 text-sm font-semibold text-white transition-all"
                            @click="emit('close')"
                        >
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
