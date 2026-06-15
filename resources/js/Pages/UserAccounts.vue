<script setup lang="ts">
import { ref, toRef, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchInput from '@/Components/UI/SearchInput.vue';
import StudentTabs from '@/Components/Students/StudentTabs.vue';
import StudentTable from '@/Components/Students/StudentTable.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import { YEAR_LEVEL_OPTIONS } from '@/composables/useStudentFilters';
import { usePaginatorNav } from '@/composables/usePaginatorNav';
import type { Paginated, Student, StudentAccountFilters, StudentTab } from '@/types';

const props = defineProps<{
    students: Paginated<Student>;
    tabCounts: Record<StudentTab, number>;
    filters: StudentAccountFilters;
}>();

// ── Local filter state, seeded from the server's echoed filters ───────────────
const search = ref(props.filters.search ?? '');
const yearFilter = ref(props.filters.year_level ? String(props.filters.year_level) : 'All');
const activeTab = ref<StudentTab>((props.filters.tab as StudentTab) ?? 'All');

const paginator = usePaginatorNav(toRef(props, 'students'));

/** Push the current filter state to the server (one source of truth). */
function reload(overrides: Record<string, unknown> = {}) {
    const yl = yearFilter.value === 'All' ? null : Number(yearFilter.value);

    router.get(
        route('user-accounts.index'),
        {
            search: search.value || undefined,
            year_level: yl ?? undefined,
            tab: activeTab.value === 'All' ? undefined : activeTab.value,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

/** Search only when Enter is pressed. */
function searchStudents() {
    reload({ page: undefined });
}

// Year / tab changes reset to page 1 immediately.
watch([yearFilter, activeTab], () => reload({ page: undefined }));

function goToPage(page: number) {
    reload({ page });
}

// ── Status actions ────────────────────────────────────────────────────────────
// Each action PATCHes the target status; the enum guards the transition server-side.
function changeStatus(student: Student, status: string) {
    router.patch(
        route('user-accounts.update-status', student.id),
        { status },
        { preserveScroll: true, preserveState: true, only: ['students', 'tabCounts'] },
    );
}

const verify = (student: Student) => changeStatus(student, 'verified');
const reject = (student: Student) => changeStatus(student, 'unverified');
const suspend = (student: Student) => changeStatus(student, 'suspended');
const reactivate = (student: Student) => changeStatus(student, 'verified');
</script>

<template>

    <Head title="User Accounts" />

    <AdminLayout title="User Accounts">
        <div class="space-y-4 pb-20">
            <p class="text-sm text-text-muted -mt-2">
                Manage student account verification and access control.
            </p>

            <!-- Top bar -->
            <div class="flex items-center gap-3 flex-wrap">
                <SearchInput v-model="search" placeholder="Search by name or student ID..."
                    @keyup.enter="searchStudents" />

                <select v-model="yearFilter"
                    class="py-2 pl-3 pr-8 text-sm rounded-xl border border-border-light bg-white text-text-secondary focus:outline-none focus:ring-2 focus:ring-sidebar/20 focus:border-sidebar transition-colors">
                    <option v-for="y in YEAR_LEVEL_OPTIONS" :key="y.value" :value="y.value">
                        {{ y.label }}
                    </option>
                </select>
            </div>

            <!-- Tab pills -->
            <StudentTabs v-model="activeTab" :counts="tabCounts" />

            <!-- Table -->
            <StudentTable :rows="students.data" @verify="verify" @reject="reject" @suspend="suspend"
                @reactivate="reactivate" />
        </div>

        <!-- Fixed pagination bar -->
        <Pagination
            :fixed="true"
            :current-page="paginator.currentPage.value"
            :total-pages="paginator.totalPages.value"
            :page-numbers="paginator.pageNumbers.value"
            :range-start="paginator.rangeStart.value"
            :range-end="paginator.rangeEnd.value"
            :total="paginator.total.value"
            @update:current-page="goToPage"
            @prev="goToPage(paginator.currentPage.value - 1)"
            @next="goToPage(paginator.currentPage.value + 1)"
        />
    </AdminLayout>
</template>