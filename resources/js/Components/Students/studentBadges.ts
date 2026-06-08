import {
    ClockIcon,
    CheckCircleIcon,
    XCircleIcon,
    CheckIcon,
    NoSymbolIcon,
} from '@heroicons/vue/24/outline';
import type { Component } from 'vue';
import type { VerificationStatus, AccountStatus } from '@/types';

interface BadgeMeta {
    label: string;
    variant: string;
    icon: Component;
}

export const VERIFICATION_BADGE: Record<VerificationStatus, BadgeMeta> = {
    pending: { label: 'Pending', variant: 'bg-amber-100 text-amber-600', icon: ClockIcon },
    verified: { label: 'Verified', variant: 'bg-green-100 text-green-600', icon: CheckCircleIcon },
    unverified: { label: 'Unverified', variant: 'bg-gray-100 text-gray-500', icon: XCircleIcon },
};

export const ACCOUNT_BADGE: Record<AccountStatus, BadgeMeta> = {
    active: { label: 'Active', variant: 'bg-emerald-100 text-emerald-600', icon: CheckIcon },
    suspended: { label: 'Suspended', variant: 'bg-red-100 text-red-500', icon: NoSymbolIcon },
};
