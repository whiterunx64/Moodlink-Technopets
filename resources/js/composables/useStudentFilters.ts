import { computed, ref, unref, type MaybeRefOrGetter } from 'vue';
import type { Student, StudentTab } from '@/types';

export const STUDENT_TABS: StudentTab[] = ['All', 'Pending', 'Verified', 'Suspended'];
export const YEAR_LEVELS = ['All', '1st Year', '2nd Year', '3rd Year', '4th Year'];

/** Year-level select options for server-side filtering (value = DB integer). */
export const YEAR_LEVEL_OPTIONS: { value: string; label: string }[] = [
    { value: 'All', label: 'All Year Levels' },
    { value: '1', label: '1st Year' },
    { value: '2', label: '2nd Year' },
    { value: '3', label: '3rd Year' },
    { value: '4', label: '4th Year' },
];

function matchesTab(student: Student, tab: StudentTab): boolean {
    switch (tab) {
        case 'Pending':
            return student.verification_status === 'pending';
        case 'Verified':
            return student.verification_status === 'verified' && student.account_status === 'active';
        case 'Suspended':
            return student.account_status === 'suspended';
        default:
            return true;
    }
}

/**
 * Client-side search / year / tab filtering for the student table.
 * Returns the filtered list plus per-tab counts (computed before the tab filter).
 */
export function useStudentFilters(source: MaybeRefOrGetter<Student[]>) {
    const search = ref('');
    const yearFilter = ref('All');
    const activeTab = ref<StudentTab>('All');

    const all = computed<Student[]>(() =>
        typeof source === 'function' ? (source as () => Student[])() : unref(source),
    );

    const afterSearch = computed(() => {
        const q = search.value.trim().toLowerCase();
        if (!q) return all.value;
        return all.value.filter(s =>
            s.student_id.toLowerCase().includes(q) ||
            s.name.toLowerCase().includes(q) ||
            s.year_level.toLowerCase().includes(q),
        );
    });

    const afterYear = computed(() =>
        yearFilter.value === 'All'
            ? afterSearch.value
            : afterSearch.value.filter(s => s.year_level === yearFilter.value),
    );

    const tabCounts = computed<Record<StudentTab, number>>(() => ({
        All: afterYear.value.length,
        Pending: afterYear.value.filter(s => matchesTab(s, 'Pending')).length,
        Verified: afterYear.value.filter(s => matchesTab(s, 'Verified')).length,
        Suspended: afterYear.value.filter(s => matchesTab(s, 'Suspended')).length,
    }));

    const filtered = computed(() => afterYear.value.filter(s => matchesTab(s, activeTab.value)));

    return { search, yearFilter, activeTab, filtered, tabCounts };
}
