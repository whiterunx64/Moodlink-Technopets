import { computed, ref, toValue, type MaybeRefOrGetter } from 'vue';
import type { Student, StudentTab } from '@/types';

export const STUDENT_TABS: { value: StudentTab; label: string }[] = [
    { value: 'all', label: 'All' },
    { value: 'pending', label: 'Pending' },
    { value: 'verified',  label: 'Active' },
    { value: 'suspended', label: 'Suspended' },
];
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
    if (tab === 'all') return true;
    if (tab === 'pending')   return student.verification_status === 'pending';
    if (tab === 'verified')  return student.verification_status === 'verified' 
    && student.account_status === 'active';
    if (tab === 'suspended') return student.account_status === 'suspended';
    return true;
}

/**
 * Client-side search / year / tab filtering for the student table.
 * Returns the filtered list plus per-tab counts (computed before the tab filter).
 */
export function useStudentFilters(source: MaybeRefOrGetter<Student[]>) {
    const search = ref('');
    const yearFilter = ref('All');
    const activeTab = ref<StudentTab>('all');

    const all = computed<Student[]>(() => toValue(source));

    const afterSearch = computed(() => {
        const query = search.value.trim().toLowerCase();
        if (!query) return all.value;
        return all.value.filter(s =>
            s.student_id.toLowerCase().includes(query) ||
            s.name.toLowerCase().includes(query) ||
            s.year_level.toLowerCase().includes(query),
        );
    });

    const afterYear = computed(() =>
        yearFilter.value === 'All'
            ? afterSearch.value
            : afterSearch.value.filter(s => s.year_level === yearFilter.value),
    );

    const tabCounts = computed<Record<StudentTab, number>>(() => ({
        all: afterYear.value.length,
        pending: afterYear.value.filter(s => matchesTab(s, 'pending')).length,
        verified: afterYear.value.filter(s => matchesTab(s, 'verified')).length,
        suspended: afterYear.value.filter(s => matchesTab(s, 'suspended')).length,
    }));

    const filtered = computed(() => afterYear.value.filter(s => matchesTab(s, activeTab.value)));

    return { search, yearFilter, activeTab, filtered, tabCounts };
}
