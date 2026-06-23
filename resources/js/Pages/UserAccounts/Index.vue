<script setup lang="ts">
import { ref, toRef, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchInput from '@/Components/UI/SearchInput.vue';
import StudentTabs from '@/Components/Students/StudentTabs.vue';
import StudentTable from '@/Components/Students/StudentTable.vue';
import Pagination from '@/Components/UI/Pagination.vue';
import VerifyStudentModal from '@/Pages/UserAccounts/Modal/VerifyStudentModal.vue';
import ManageAccountModal from '@/Pages/UserAccounts/Modal/ManageAccountModal.vue';
import { YEAR_LEVEL_OPTIONS } from '@/composables/useStudentFilters';
import { usePaginatorNav } from '@/composables/usePaginatorNav';
import { usePollingReload } from '@/composables/usePolling';
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

usePollingReload(['students', 'tabCounts']);

const verifyModalOpen = ref(false);
const manageModalOpen = ref(false);
const selectedStudent = ref<Student | null>(null);

function openModal(student: Student) {
    selectedStudent.value = student;
    if (student.verification_status === 'pending') {
        verifyModalOpen.value = true;
    } else {
        manageModalOpen.value = true;
    }
}

function closeModal() {
    verifyModalOpen.value = false;
    manageModalOpen.value = false;
    selectedStudent.value = null;
}

function onVerified() {
    router.reload({ only: ['students', 'tabCounts'] });
}

function reload(overrides: Record<string, unknown> = {}) {
    const yl = yearFilter.value === 'All' ? null : Number(yearFilter.value);

    router.get(
        route('student-accounts.index'),
        {
            search: search.value || undefined,
            year_level: yl ?? undefined,
            tab: activeTab.value === 'All' ? undefined : activeTab.value,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function searchStudents() {
    reload({ page: undefined });
}

watch([yearFilter, activeTab], () => reload({ page: undefined }));

function goToPage(page: number) {
    reload({ page });
}
</script>

<template>

    <Head title="User Accounts" />

    <AdminLayout title="User Accounts">
        <div class="space-y-4 pb-20">

            <!-- Top bar -->
            <div class="flex items-center gap-3 flex-wrap">

                <!-- Search input (uses your existing SearchInput component) -->
                <SearchInput v-model="search" placeholder="Search by name or student ID..." @search="searchStudents" />

                <!-- Year level filter with icon -->
                <div class="relative">
                    <!-- Filter / funnel icon -->
                    <span
                        class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-text-secondary/60">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3" />
                        </svg>
                    </span>

                    <select v-model="yearFilter"
                        class="appearance-none py-2.5 pl-9 pr-8 text-sm border border-border-light bg-white text-text-secondary focus:outline-none focus:ring-2 focus:ring-sidebar/20 focus:border-sidebar transition-colors cursor-pointer">
                        <option v-for="y in YEAR_LEVEL_OPTIONS" :key="y.value" :value="y.value">
                            {{ y.label }}
                        </option>
                    </select>

                    <!-- Chevron-down icon -->
                    <span
                        class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-text-secondary/60">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none"
                            stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="6 9 12 15 18 9" />
                        </svg>
                    </span>
                </div>
            </div>

            <!-- Tab pills -->
            <StudentTabs v-model="activeTab" :counts="tabCounts" />

            <!-- Table -->
            <StudentTable :rows="students.data" @open="openModal" />

        </div>

        <!-- Fixed pagination bar -->
        <Pagination :fixed="true" :current-page="paginator.currentPage.value" :total-pages="paginator.totalPages.value"
            :page-numbers="paginator.pageNumbers.value" :range-start="paginator.rangeStart.value"
            :range-end="paginator.rangeEnd.value" :total="paginator.total.value" @update:current-page="goToPage"
            @prev="goToPage(paginator.currentPage.value - 1)" @next="goToPage(paginator.currentPage.value + 1)" />
    </AdminLayout>

    <!-- Pending students: create-account flow -->
    <VerifyStudentModal :show="verifyModalOpen" :student="selectedStudent" @close="closeModal" @verified="onVerified" />

    <!-- Verified / suspended students: manage account -->
    <ManageAccountModal :show="manageModalOpen" :student="selectedStudent" @close="closeModal"
        @updated="onVerified" />
</template>