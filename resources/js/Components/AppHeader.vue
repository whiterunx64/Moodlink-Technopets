<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { Bars3Icon, BellIcon } from '@heroicons/vue/24/outline';
import { getInitials } from '@/composables/useInitials';
import { usePolling } from '@/composables/usePolling';
import type { AdminNotification, AdminNotificationFeed, PageProps } from '@/types';

defineProps<{
  title: string;
}>();

const user = computed(() => usePage<PageProps>().props.auth.user);
const initials = computed(() => getInitials(user.value?.name ?? ''));

const emit = defineEmits<{
  'toggle-sidebar': [];
}>();

// ── Notifications inbox ───────────────────────────────────────────
const notifications = ref<AdminNotification[]>([]);
const unread = ref(0);
const notificationsOpen = ref(false);
const notificationsRoot = ref<HTMLElement | null>(null);

async function fetchNotifications() {
  try {
    const response = await fetch(route('notifications.index'), {
      headers: { Accept: 'application/json' },
      credentials: 'same-origin',
    });
    if (!response.ok) return;
    const feed = (await response.json()) as AdminNotificationFeed;
    notifications.value = feed.notifications;
    unread.value = feed.unread;
  } catch {
    // Silent: a failed poll just keeps the last known feed.
  }
}

function toggleNotifications() {
  notificationsOpen.value = !notificationsOpen.value;
  if (notificationsOpen.value) fetchNotifications();
}

function handleOutsideClick(event: MouseEvent) {
  if (
    notificationsOpen.value &&
    notificationsRoot.value &&
    !notificationsRoot.value.contains(event.target as Node)
  ) {
    notificationsOpen.value = false;
  }
}

function formatNotificationTime(datetime: string | null): string {
  if (!datetime) return '';
  return new Date(datetime).toLocaleString('en-US', {
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: true,
  });
}

usePolling(fetchNotifications, { immediate: true, interval: 30_000 });

const clock = ref('');
let clock_interval: ReturnType<typeof setInterval> | null = null;

function updateClock() {
  clock.value = new Date().toLocaleString('en-US', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
  });
}

onMounted(() => {
  updateClock();
  clock_interval = setInterval(updateClock, 1000);
  document.addEventListener('click', handleOutsideClick);
});

onUnmounted(() => {
  if (clock_interval) clearInterval(clock_interval);
  document.removeEventListener('click', handleOutsideClick);
});
</script>

<template>
  <header
    class="flex items-center gap-4 border-b border-header-border bg-header-bg px-6 py-4 shrink-0 sticky top-0 z-10">
    <button type="button" class="p-1 text-header-icon hover:text-header-icon-hover transition-colors lg:hidden"
      @click="emit('toggle-sidebar')">
      <Bars3Icon class="h-6 w-6" />
    </button>

    <div class="flex-1 min-w-0">
      <h2 class="text-xl font-bold text-text-primary truncate">{{ title }}</h2>
      <p class="text-xs text-text-muted mt-0.5">{{ clock }}</p>
    </div>

    <div class="flex items-center gap-3 shrink-0">
      <!-- Notifications -->
      <div ref="notificationsRoot" class="relative">
        <button type="button" class="relative p-1.5 text-header-icon hover:text-header-icon-hover transition-colors"
          aria-label="Notifications" @click="toggleNotifications">
          <BellIcon class="h-6 w-6" />
          <span v-if="unread > 0"
            class="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-none text-white">
            {{ unread > 99 ? '99+' : unread }}
          </span>
        </button>

        <!-- Dropdown -->
        <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0 -translate-y-1"
          enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-100"
          leave-from-class="opacity-100" leave-to-class="opacity-0">
          <div v-if="notificationsOpen"
            class="absolute right-0 z-30 mt-2 w-[420px] overflow-hidden border-2 border-slate-400 bg-slate-50 shadow-2xl">
            <!-- Header -->
            <div class="flex items-center justify-between border-b-2 border-slate-400 bg-slate-200 px-5 py-4">
              <div>
                <p class="text-base font-bold uppercase tracking-wide text-slate-800">
                  Notifications
                </p>
                <p class="text-xs text-slate-500">
                  System alerts and activity
                </p>
              </div>

              <span v-if="unread > 0"
                class="border border-red-700 bg-red-700 px-3 py-1 text-xs font-bold uppercase text-white">
                {{ unread }} New
              </span>
            </div>

            <!-- Content -->
            <div class="max-h-[350px] overflow-y-auto">
              <div v-if="notifications.length === 0" class="flex flex-col items-center justify-center py-16">
                <BellIcon class="mb-3 h-10 w-10 text-slate-400" />
                <p class="text-base font-bold text-slate-700">
                  No Notifications
                </p>
                <p class="mt-1 text-sm text-slate-500">
                  New notifications will appear here.
                </p>
              </div>

              <template v-else>
                <div v-for="notification in notifications" :key="notification.id" :class="[
                  'border-b border-slate-300 px-5 py-4',
                  notification.is_seen
                    ? 'bg-white'
                    : 'border-l-[6px] border-l-amber-600 bg-amber-50'
                ]">
                  <div class="flex gap-4">
                    <div class="pt-1">
                      <div :class="[
                        'h-4 w-4 border',
                        notification.is_seen
                          ? 'border-slate-300 bg-white'
                          : 'border-red-700 bg-red-600'
                      ]" />
                    </div>

                    <div class="min-w-0 flex-1">
                      <p class="text-[15px] font-bold text-slate-900">
                        {{ notification.title ?? 'Notification' }}
                      </p>

                      <p v-if="notification.content" class="mt-2 text-sm leading-6 text-slate-700">
                        {{ notification.content }}
                      </p>

                      <p
                        class="mt-3 border-t border-slate-200 pt-2 text-xs font-semibold uppercase tracking-wide text-slate-500">
                        {{ formatNotificationTime(notification.datetime) }}
                      </p>
                    </div>
                  </div>
                </div>
              </template>
            </div>

            <!-- Footer -->
            <div
              class="border-t-2 border-slate-400 bg-slate-100 px-5 py-3 text-xs font-semibold uppercase text-slate-600">
              Total Notifications: {{ notifications.length }}
            </div>
          </div>
        </Transition>
      </div>

      <div class="w-8 h-8 rounded-full bg-sidebar/10 flex items-center justify-center overflow-hidden">
        <img v-if="user?.avatar" :src="user.avatar" alt="Avatar" class="w-full h-full object-cover" />
        <span v-else class="text-sidebar text-sm font-bold">{{ initials }}</span>
      </div>
    </div>
  </header>
</template>
