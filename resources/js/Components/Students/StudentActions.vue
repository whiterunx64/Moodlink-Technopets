<script setup lang="ts">
import { CheckCircle, XCircle, Ban, RotateCcw } from 'lucide-vue-next';
import type { Student } from '@/types';

defineProps<{ student: Student }>();

defineEmits<{
    register: [];
    verify: [];
    reject: [];
    suspend: [];
    reactivate: [];
}>();
</script>

<template>
    <div class="flex items-center justify-end gap-2">
        <!-- Pending: verify or reject -->
        <template v-if="student.verification_status === 'pending'">
            <button @click="$emit('register')"
                class="w-24 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold bg-sidebar text-white hover:bg-sidebar/90 transition-colors">
                Verify
                <CheckCircle class="w-3.5 h-3.5" />
            </button>

            <button @click="$emit('reject')"
                class="w-24 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold border border-red-300 text-red-500 hover:bg-red-50 transition-colors">
                Reject
                <XCircle class="w-3.5 h-3.5" />
            </button>
        </template>

        <!-- Unverified: verify only -->
        <template v-else-if="student.verification_status === 'unverified'">
            <button @click="$emit('verify')"
                class="w-24 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold border border-sidebar text-sidebar hover:bg-sidebar/10 transition-colors">
                Verify
                <CheckCircle class="w-3.5 h-3.5" />
            </button>
        </template>

        <!-- Verified + active: suspend -->
        <template v-else-if="student.verification_status === 'verified' && student.account_status === 'active'">
            <button @click="$emit('suspend')"
                class="w-24 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold border border-orange-300 text-orange-500 hover:bg-orange-50 transition-colors">
                Suspend
                <Ban class="w-3.5 h-3.5" />
            </button>
        </template>

        <!-- Suspended: reactivate -->
        <template v-else-if="student.account_status === 'suspended'">
            <button @click="$emit('reactivate')"
                class="w-24 flex items-center justify-center gap-1.5 py-1.5 rounded-lg text-xs font-semibold border border-blue-300 text-blue-500 hover:bg-blue-50 transition-colors">
                Reactivate
                <RotateCcw class="w-3.5 h-3.5" />
            </button>
        </template>
    </div>
</template>