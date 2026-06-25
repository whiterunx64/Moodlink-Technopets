/** A single pagination control: either a page number or an ellipsis gap. */
export type PageButton = number | '…';

/**
 * Builds the list of page buttons to render, inserting '…' gaps for long
 * ranges so the control never grows past ~7 slots. Shared by both the
 * client-side (usePagination) and server-side (usePaginatorNav) paginators.
 */
export function buildPageButtons(currentPage: number, totalPages: number): PageButton[] {
    if (totalPages <= 7) {
        return Array.from({ length: totalPages }, (_, i) => i + 1);
    }
    if (currentPage <= 4) {
        return [1, 2, 3, 4, 5, '…', totalPages];
    }
    if (currentPage >= totalPages - 3) {
        return [1, '…', totalPages - 4, totalPages - 3, totalPages - 2, totalPages - 1, totalPages];
    }
    return [1, '…', currentPage - 1, currentPage, currentPage + 1, '…', totalPages];
}
