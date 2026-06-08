import { computed, type Ref } from 'vue';
import type { Paginated } from '@/types';

/**
 * Derives the page-button list (with '…' gaps) from a server-side Laravel
 * paginator, so the presentational <Pagination> component can stay the same
 * whether pagination is client- or server-driven.
 */
export function usePaginatorNav<T>(paginator: Ref<Paginated<T>>) {
    const currentPage = computed(() => paginator.value.current_page);
    const totalPages = computed(() => paginator.value.last_page);

    const pageNumbers = computed<(number | '…')[]>(() => {
        const t = totalPages.value;
        const c = currentPage.value;
        if (t <= 7) return Array.from({ length: t }, (_, i) => i + 1);
        if (c <= 4) return [1, 2, 3, 4, 5, '…', t];
        if (c >= t - 3) return [1, '…', t - 4, t - 3, t - 2, t - 1, t];
        return [1, '…', c - 1, c, c + 1, '…', t];
    });

    return {
        currentPage,
        totalPages,
        pageNumbers,
        rangeStart: computed(() => paginator.value.from ?? 0),
        rangeEnd: computed(() => paginator.value.to ?? 0),
        total: computed(() => paginator.value.total),
    };
}
