<template>
    <div class="animate-pulse space-y-4">

        <!-- Stat cards — full-width row -->
        <div class="grid grid-cols-2 gap-4 xl:grid-cols-4">
            <div v-for="i in 4" :key="i"
                class="rounded-2xl border border-border-light bg-white p-5 shadow-sm">
                <!-- Icon + label -->
                <div class="mb-4 flex items-center gap-3">
                    <div class="h-11 w-11 shrink-0 rounded-xl bg-gray-200" />
                    <div class="h-3 w-20 rounded bg-gray-200" />
                </div>
                <!-- Count -->
                <div class="mb-5 h-10 w-14 rounded bg-gray-200" />
                <!-- Breakdown rows -->
                <div class="space-y-3 border-t border-border-light pt-4">
                    <div v-for="j in 3" :key="j" class="flex items-center justify-between">
                        <div class="h-3.5 w-16 rounded bg-gray-100" />
                        <div class="h-7 w-12 rounded-full bg-gray-200" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Bottom panels -->
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-[3fr_2fr]">

            <!-- Recent activity -->
            <div class="flex flex-col rounded-2xl border border-border-light bg-white shadow-sm">
                <div class="border-b border-border-light px-5 pt-5 pb-0">
                    <div class="mb-1.5 h-4 w-32 rounded bg-gray-200" />
                    <div class="mb-3 h-3 w-48 rounded bg-gray-100" />
                    <div class="flex gap-5 pb-3">
                        <div class="h-3 w-24 rounded bg-gray-200" />
                        <div class="h-3 w-20 rounded bg-gray-100" />
                        <div class="h-3 w-20 rounded bg-gray-100" />
                    </div>
                </div>
                <div class="divide-y divide-border-light">
                    <div v-for="i in 5" :key="i" class="flex items-center gap-3 px-5 py-3.5">
                        <div class="h-8 w-8 shrink-0 rounded-full bg-gray-200" />
                        <div class="flex-1 space-y-1.5">
                            <div class="h-3 w-28 rounded bg-gray-200" />
                            <div class="h-2.5 w-40 rounded bg-gray-100" />
                        </div>
                        <div class="h-5 w-16 shrink-0 rounded-full bg-gray-100" />
                    </div>
                </div>
                <div class="border-t border-border-light px-5 py-3">
                    <div class="h-8 w-36 rounded-lg bg-gray-100" />
                </div>
            </div>

            <!-- Mood trend -->
            <div class="flex flex-col rounded-2xl border border-border-light bg-white shadow-sm">
                <div class="border-b border-border-light px-5 pt-5 pb-4">
                    <div class="mb-1.5 h-4 w-24 rounded bg-gray-200" />
                    <div class="h-3 w-32 rounded bg-gray-100" />
                </div>
                <div class="flex items-center gap-2 px-5 pt-4">
                    <div class="flex flex-1 gap-1 rounded-lg bg-gray-100 p-1">
                        <div v-for="i in 3" :key="i" class="flex-1 h-7 rounded-md bg-gray-200" />
                    </div>
                    <div class="h-9 w-28 shrink-0 rounded-lg bg-gray-100" />
                </div>
                <div class="flex items-center justify-center py-8">
                    <div class="h-40 w-40 rounded-full border-20 border-gray-200 bg-transparent" />
                </div>
                <div class="grid grid-cols-2 gap-2 border-t border-border-light p-4">
                    <div v-for="i in 4" :key="i"
                        class="flex items-center gap-2 rounded-xl bg-post-card-bg px-3 py-2">
                        <div class="h-2 w-2 shrink-0 rounded-full bg-gray-300" />
                        <div class="h-3 flex-1 rounded bg-gray-200" />
                        <div class="h-3 w-6 rounded bg-gray-100" />
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
