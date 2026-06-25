import { computed, ref, toValue, watch, type MaybeRefOrGetter } from 'vue';
import { buildPageButtons } from '@/utils/pagination';

export interface UsePaginationOptions {
    pageSize?: number;
}

/**
 * Client-side pagination over a reactive list.
 * Resets to page 1 whenever the source list changes.
 */
export function usePagination<T>(
    source: MaybeRefOrGetter<T[]>,
    options: UsePaginationOptions = {},
) {
    const pageSize = options.pageSize ?? 10;
    const currentPage = ref(1);

    const items = computed<T[]>(() => toValue(source));

    const total = computed(() => items.value.length);
    const totalPages = computed(() => Math.max(1, Math.ceil(total.value / pageSize)));

    watch(items, () => { currentPage.value = 1; });

    const pageItems = computed<T[]>(() => {
        const start = (currentPage.value - 1) * pageSize;
        return items.value.slice(start, start + pageSize);
    });

    const rangeStart = computed(() => (total.value === 0 ? 0 : (currentPage.value - 1) * pageSize + 1));
    const rangeEnd = computed(() => Math.min(currentPage.value * pageSize, total.value));

    const pageNumbers = computed(() => buildPageButtons(currentPage.value, totalPages.value));

    function goTo(page: number) {
        currentPage.value = Math.min(Math.max(1, page), totalPages.value);
    }
    function next() { goTo(currentPage.value + 1); }
    function prev() { goTo(currentPage.value - 1); }

    return {
        currentPage,
        pageSize,
        total,
        totalPages,
        pageItems,
        pageNumbers,
        rangeStart,
        rangeEnd,
        goTo,
        next,
        prev,
    };
}
