<script setup lang="ts">
defineProps<{
    firstName: string;
    lastName: string;
    role: string;
    department: string;
}>();
</script>

<template>
    <div class="bg-white rounded-2xl border border-border-light shadow-sm">
        <div class="h-24 bg-gradient-green rounded-t-2xl" />
        <div class="px-6 pb-6 -mt-8">
            <div class="flex items-end justify-between gap-4">
                <div
                    class="w-16 h-16 rounded-2xl bg-white border-4 border-white shadow-md flex items-center justify-center text-2xl font-extrabold text-sidebar shrink-0">
                    {{ firstName.charAt(0) }}{{ lastName.charAt(0) }}
                </div>
                <span class="mb-1 px-3 py-1 bg-green-50 text-green-600 text-xs font-semibold rounded-full shrink-0">
                    Active
                </span>
            </div>
            <div class="mt-3">
                <h2 class="text-lg font-bold text-text-primary">{{ firstName }} {{ lastName }}</h2>
                <p class="text-sm text-text-muted mt-0.5">{{ role }} · {{ department }}</p>
            </div>
        </div>
    </div>
</template>
