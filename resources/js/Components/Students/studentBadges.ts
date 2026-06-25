import {
    ClockIcon,
    CheckCircleIcon,
    NoSymbolIcon,
    XCircleIcon,
    CheckIcon,
} from '@heroicons/vue/24/outline';
import type { Component } from 'vue';
import type { VerificationStatus, AccountStatus } from '@/types';

interface BadgeMeta {
    label: string;
    variant: string;
    icon: Component;
}

export const VERIFICATION_BADGE: Record<string, BadgeMeta> = {
    pending:   { label: 'Pending',   variant: 'bg-amber-100 text-amber-600', icon: ClockIcon },
    verified:  { label: 'Verified',  variant: 'bg-blue-100 text-blue-600',   icon: CheckCircleIcon },
    suspended: { label: 'Verified',  variant: 'bg-blue-100 text-blue-600',   icon: CheckCircleIcon },
};

export const ACCOUNT_BADGE: Record<string, BadgeMeta | null> = {
    active:    { label: 'Active',    variant: 'bg-emerald-100 text-emerald-600', icon: CheckIcon },
    suspended: { label: 'Suspended', variant: 'bg-red-100 text-red-500',         icon: NoSymbolIcon },
    pending:   null,
};