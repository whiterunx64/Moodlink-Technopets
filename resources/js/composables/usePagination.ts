import { computed, ref, unref, watch, type MaybeRefOrGetter } from 'vue';

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
    const pageSize = options.pageSize ?? 7;
    const currentPage = ref(1);

    const items = computed<T[]>(() =>
        typeof source === 'function' ? (source as () => T[])() : unref(source),
    );

    const total = computed(() => items.value.length);
    const totalPages = computed(() => Math.max(1, Math.ceil(total.value / pageSize)));

    watch(items, () => { currentPage.value = 1; });

    const pageItems = computed<T[]>(() => {
        const start = (currentPage.value - 1) * pageSize;
        return items.value.slice(start, start + pageSize);
    });

    const rangeStart = computed(() => (total.value === 0 ? 0 : (currentPage.value - 1) * pageSize + 1));
    const rangeEnd = computed(() => Math.min(currentPage.value * pageSize, total.value));

    /** Page buttons with '…' ellipsis gaps for long ranges. */
    const pageNumbers = computed<(number | '…')[]>(() => {
        const t = totalPages.value;
        const c = currentPage.value;
        if (t <= 7) return Array.from({ length: t }, (_, i) => i + 1);
        if (c <= 4) return [1, 2, 3, 4, 5, '…', t];
        if (c >= t - 3) return [1, '…', t - 4, t - 3, t - 2, t - 1, t];
        return [1, '…', c - 1, c, c + 1, '…', t];
    });

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
