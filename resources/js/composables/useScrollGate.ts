import { nextTick, ref } from 'vue';

export function useScrollGate(thresholdPx = 8) {
    const contentEl = ref<HTMLElement | null>(null);
    const atBottom = ref(false);

    function updateAtBottom() {
        const el = contentEl.value;
        if (!el) return;
        atBottom.value = el.scrollHeight - el.scrollTop - el.clientHeight <= thresholdPx;
    }

    function scrollToBottom() {
        contentEl.value?.scrollTo({ top: contentEl.value.scrollHeight, behavior: 'smooth' });
    }

    // Close the gate, then re-check after render in case the content already fits.
    function reset() {
        atBottom.value = false;
        nextTick(updateAtBottom);
    }

    return { contentEl, atBottom, updateAtBottom, scrollToBottom, reset };
}
