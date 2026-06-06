<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { PlusIcon } from '@heroicons/vue/24/outline';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SearchInput from '@/Components/UI/SearchInput.vue';
import StudentTabs from '@/Components/Students/StudentTabs.vue';
import StudentTable from '@/Components/Students/StudentTable.vue';
import { useStudentFilters, YEAR_LEVELS } from '@/composables/useStudentFilters';
import { usePagination } from '@/composables/usePagination';
import type { Student } from '@/types';

// ── Mock data (replace with Inertia props from the controller) ────────────────
const STUDENTS: Student[] = [
    { id:  1, student_id: '2021-00123', name: 'Maria Santos',    year_level: '3rd Year', section: 'DW31',  verification_status: 'verified',   account_status: 'active'    },
    { id:  2, student_id: '2021-00456', name: 'Juan dela Cruz',  year_level: '2nd Year', section: 'DX30',  verification_status: 'verified',   account_status: 'suspended' },
    { id:  3, student_id: '2022-00789', name: 'Ana Reyes',       year_level: '2nd Year', section: 'DX31A', verification_status: 'pending',    account_status: 'active'    },
    { id:  4, student_id: '2020-01011', name: 'Carlos Bautista', year_level: '4th Year', section: 'DW31',  verification_status: 'verified',   account_status: 'active'    },
    { id:  5, student_id: '2023-01213', name: 'Lea Villanueva',  year_level: '1st Year', section: 'DX30',  verification_status: 'pending',    account_status: 'active'    },
    { id:  6, student_id: '2022-01415', name: 'Mark Ocampo',     year_level: '2nd Year', section: 'DX31A', verification_status: 'unverified', account_status: 'active'    },
    { id:  7, student_id: '2021-01617', name: 'Sofia Mendoza',   year_level: '3rd Year', section: 'DW32',  verification_status: 'verified',   account_status: 'active'    },
    { id:  8, student_id: '2023-01819', name: 'Rico Fontanilla', year_level: '1st Year', section: 'DX32',  verification_status: 'pending',    account_status: 'active'    },
    { id:  9, student_id: '2020-02021', name: 'Diane Castillo',  year_level: '4th Year', section: 'DW31',  verification_status: 'unverified', account_status: 'active'    },
    { id: 10, student_id: '2021-02223', name: 'Paolo Guerrero',  year_level: '3rd Year', section: 'DX30',  verification_status: 'verified',   account_status: 'suspended' },
    { id: 11, student_id: '2022-02425', name: 'Trisha Lim',      year_level: '2nd Year', section: 'DX31A', verification_status: 'pending',    account_status: 'active'    },
    { id: 12, student_id: '2023-02627', name: 'Nico Adriano',    year_level: '1st Year', section: 'DW32',  verification_status: 'unverified', account_status: 'active'    },
    { id: 13, student_id: '2020-02829', name: 'Camille Torres',  year_level: '4th Year', section: 'DX32',  verification_status: 'verified',   account_status: 'active'    },
    { id: 14, student_id: '2021-03031', name: 'Luis Evangelista',year_level: '3rd Year', section: 'DW31',  verification_status: 'verified',   account_status: 'suspended' },
    { id: 15, student_id: '2022-03233', name: 'Hannah Peralta',  year_level: '2nd Year', section: 'DX30',  verification_status: 'pending',    account_status: 'active'    },
];

const { search, yearFilter, activeTab, filtered, tabCounts } = useStudentFilters(STUDENTS);
const page = usePagination(filtered, { pageSize: 7 });

// ── Actions (wire to Inertia router later) ────────────────────────────────────
function verify(student: Student)     { console.log('verify', student.id); }
function reject(student: Student)     { console.log('reject', student.id); }
function suspend(student: Student)    { console.log('suspend', student.id); }
function reactivate(student: Student) { console.log('reactivate', student.id); }
</script>

<template>
    <Head title="User Accounts" />

    <AdminLayout title="User Accounts">
        <div class="space-y-4">
            <p class="text-sm text-text-muted -mt-2">Manage student account verification and access control.</p>

            <!-- Top bar -->
            <div class="flex items-center gap-3 flex-wrap">
                <SearchInput
                    v-model="search"
                    placeholder="Search by name, student ID, or year level..."
                />

                <select
                    v-model="yearFilter"
                    class="py-2 pl-3 pr-8 text-sm rounded-xl border border-border-light bg-white text-text-secondary focus:outline-none focus:ring-2 focus:ring-sidebar/20 focus:border-sidebar transition-colors"
                >
                    <option v-for="y in YEAR_LEVELS" :key="y" :value="y">
                        {{ y === 'All' ? 'All Year Levels' : y }}
                    </option>
                </select>

                <button class="ml-auto inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold rounded-lg bg-sidebar text-white hover:bg-sidebar/90 transition-colors">
                    <PlusIcon class="w-3 h-3" />
                    Add Student
                </button>
            </div>

            <!-- Tab pills -->
            <StudentTabs v-model="activeTab" :counts="tabCounts" />

            <!-- Table + pagination -->
            <StudentTable
                :rows="page.pageItems.value"
                :total="filtered.length"
                :current-page="page.currentPage.value"
                :total-pages="page.totalPages.value"
                :page-numbers="page.pageNumbers.value"
                :range-start="page.rangeStart.value"
                :range-end="page.rangeEnd.value"
                @verify="verify"
                @reject="reject"
                @suspend="suspend"
                @reactivate="reactivate"
                @update:current-page="page.goTo"
                @prev="page.prev"
                @next="page.next"
            />
        </div>
    </AdminLayout>
</template>
