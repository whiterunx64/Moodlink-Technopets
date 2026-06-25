import { computed, type Ref } from 'vue';
import type { Paginated } from '@/types';
import { buildPageButtons } from '@/utils/pagination';

/**
 * Derives the page-button list (with '…' gaps) from a server-side Laravel
 * paginator, so the presentational <Pagination> component can stay the same
 * whether pagination is client- or server-driven.
 */
export function usePaginatorNav<T>(paginator: Ref<Paginated<T>>) {
    const currentPage = computed(() => paginator.value.current_page);
    const totalPages = computed(() => paginator.value.last_page);

    const pageNumbers = computed(() => buildPageButtons(currentPage.value, totalPages.value));

    return {
        currentPage,
        totalPages,
        pageNumbers,
        rangeStart: computed(() => paginator.value.from ?? 0),
        rangeEnd: computed(() => paginator.value.to ?? 0),
        total: computed(() => paginator.value.total),
    };
}
