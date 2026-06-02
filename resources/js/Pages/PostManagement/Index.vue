<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import FilterTabs from '@/Components/Filters/FilterTabs.vue';
import PostCard from '@/Components/Posts/PostCard.vue';
import type { Post, PostFilter } from '@/types';
import type { FilterTab } from '@/Components/Filters/FilterTabs.vue';

const props = defineProps<{
    posts: Post[];
}>();

const activeFilter = ref<PostFilter>('All');

// Local copy for optimistic flag toggling without waiting for a round-trip
const localPosts = ref<Post[]>(props.posts.map(p => ({ ...p })));

// Keep in sync when Inertia refreshes the prop (partial reload after patch)
watch(() => props.posts, newPosts => {
    localPosts.value = newPosts.map(p => ({ ...p }));
});

const tabs = computed<FilterTab[]>(() => [
    { label: 'All', value: 'All', badge: localPosts.value.length },
    { label: 'Flagged', value: 'Flagged', badge: localPosts.value.filter(p => p.status === 'flagged').length, badgeInactiveClass: 'bg-status-flagged-bg text-status-flagged' },
    { label: 'Safe', value: 'Safe', badge: localPosts.value.filter(p => p.status === 'safe').length, badgeInactiveClass: 'bg-status-safe-bg text-status-safe' },
]);

const filtered = computed(() => {
    const posts = localPosts.value;

    switch (activeFilter.value) {
        case 'Flagged':
            return posts.filter(p => p.status === 'flagged');

        case 'Safe':
            return posts.filter(p => p.status === 'safe');

        case 'All':
        default:
            return posts;
    }
});

/** Trigger Inertia controller flag toggle */
function toggleFlag(post: Post) {
    const target = localPosts.value.find(p => p.id === post.id);
    if (!target) return;

    target.status = target.status === 'flagged' ? 'safe' : 'flagged';

    router.patch(route('post-management.toggle-status', post.id), {}, {
        preserveScroll: true,
        only: ['posts'],
        onError: () => { target.status = target.status === 'flagged' ? 'safe' : 'flagged'; },
    });
}

</script>

<template>

    <Head title="Posts" />

    <AdminLayout title="Posts">
        <div class="space-y-5">

            <FilterTabs v-model="activeFilter" :tabs="tabs" />

            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                <PostCard v-for="post in filtered" :key="post.id" :post="post" @toggle-flag="toggleFlag(post)"
                    @view="router.get(route('posts.show', post.id))" />

                <div v-if="filtered.length === 0"
                    class="col-span-full py-20 flex flex-col items-center gap-3 text-text-muted">
                    <i class="fas fa-file-alt text-4xl opacity-20"></i>
                    <p class="text-sm">No posts in this category</p>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
