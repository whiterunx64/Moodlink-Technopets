<template>
    <div class="space-y-6 animate-pulse">

        <!-- Stat Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
            <div v-for="i in 4" :key="i" class="rounded-2xl p-5 bg-gray-200 h-28" />
        </div>

        <!-- Main Grid -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

            <!-- MoodSpace Feed skeleton -->
            <div class="xl:col-span-2 bg-white rounded-2xl border border-border-light shadow-sm flex flex-col">
                <div class="px-5 pt-5 pb-4 border-b border-border-light shrink-0">
                    <div class="h-4 bg-gray-200 rounded w-36 mb-1.5" />
                    <div class="h-3 bg-gray-100 rounded w-48" />
                </div>
                <div class="p-4 flex-1 flex flex-col gap-3 overflow-hidden max-h-96">
                    <div v-for="i in 4" :key="i" class="flex gap-3 p-3 rounded-xl bg-gray-50">
                        <div class="w-9 h-9 rounded-full bg-gray-200 shrink-0" />
                        <div class="flex-1 space-y-2 pt-0.5">
                            <div class="flex justify-between">
                                <div class="h-3 bg-gray-200 rounded w-28" />
                                <div class="h-3 bg-gray-100 rounded w-12" />
                            </div>
                            <div class="h-3 bg-gray-200 rounded w-full" />
                            <div class="h-5 bg-gray-100 rounded-full w-14" />
                        </div>
                    </div>
                </div>
                <div class="px-5 py-3 border-t border-border-light shrink-0">
                    <div class="h-3 bg-gray-100 rounded w-24" />
                </div>
            </div>

            <!-- Mood Trends skeleton -->
            <div
                class="bg-white rounded-2xl border border-border-light shadow-sm flex flex-col h-full min-h-120 overflow-hidden">
                <div class="px-5 pt-5 pb-4 border-b border-border-light shrink-0">
                    <div class="h-4 bg-gray-200 rounded w-28 mb-1.5" />
                    <div class="h-3 bg-gray-100 rounded w-44" />
                </div>
                <div class="p-5 flex flex-col flex-1 gap-4">
                    <!-- Tab pills -->
                    <div class="flex gap-1 bg-gray-100 rounded-lg p-1">
                        <div v-for="i in 3" :key="i" class="flex-1 h-7 bg-gray-200 rounded-md" />
                    </div>
                    <!-- Section pills -->
                    <div class="flex gap-1.5 flex-wrap">
                        <div v-for="i in 4" :key="i" class="h-6 bg-gray-100 rounded-full w-12" />
                    </div>
                    <!-- Distribution bars -->
                    <div class="flex-1 flex flex-col justify-center space-y-3">
                        <div v-for="i in 4" :key="i" class="space-y-1">
                            <div class="flex justify-between">
                                <div class="h-3 bg-gray-200 rounded w-14" />
                                <div class="h-3 bg-gray-100 rounded w-8" />
                            </div>
                            <div class="h-2 bg-gray-100 rounded-full w-full">
                                <div class="h-2 bg-gray-200 rounded-full"
                                    :style="{ width: `${[65, 42, 28, 15][i - 1]}%` }" />
                            </div>
                        </div>
                    </div>
                    <!-- Legend -->
                    <div class="grid grid-cols-2 gap-2 pt-1 border-t border-border-light shrink-0">
                        <div v-for="i in 4" :key="i" class="flex items-center gap-1.5">
                            <div class="w-2.5 h-2.5 rounded-full bg-gray-200" />
                            <div class="h-3 bg-gray-100 rounded w-12" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Upcoming Appointments skeleton -->
        <div class="bg-white rounded-2xl border border-border-light shadow-sm flex flex-col h-105 overflow-hidden">
            <div class="px-5 pt-5 pb-4 border-b border-border-light flex items-center justify-between shrink-0">
                <div class="h-4 bg-gray-200 rounded w-44" />
                <div class="h-3 bg-gray-100 rounded w-16" />
            </div>
            <div class="divide-y divide-border-light flex-1 overflow-hidden">
                <div v-for="i in 5" :key="i" class="flex items-center justify-between px-5 py-3.5 gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded-full bg-gray-200 shrink-0" />
                        <div class="space-y-1.5">
                            <div class="h-3 bg-gray-200 rounded w-32" />
                            <div class="h-3 bg-gray-100 rounded w-24" />
                        </div>
                    </div>
                    <div class="h-6 bg-gray-100 rounded-full w-20" />
                </div>
            </div>
        </div>

    </div>
</template>
