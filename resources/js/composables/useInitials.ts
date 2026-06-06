import { unref, type MaybeRef } from 'vue';

const PALETTE = [
    'bg-amber-400', 'bg-emerald-500', 'bg-sky-500', 'bg-violet-500',
    'bg-rose-400', 'bg-teal-500', 'bg-indigo-400', 'bg-orange-400',
] as const;

/** Two-letter initials from a full name (first + last, else first two chars). */
export function getInitials(name: string): string {
    const parts = name.trim().split(/\s+/).filter(Boolean);
    if (parts.length === 0) return '?';
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.slice(0, 2).toUpperCase();
}

/** Deterministic palette colour derived from a name. */
export function getAvatarColor(name: string): string {
    const seed = name.charCodeAt(0) || 0;
    return PALETTE[seed % PALETTE.length];
}

/** Reactive helper for templates. */
export function useInitials(name: MaybeRef<string>) {
    return {
        initials: () => getInitials(unref(name)),
        color: () => getAvatarColor(unref(name)),
    };
}
