<script setup lang="ts">
import SettingsSection from '@/Components/UI/SettingsSection.vue';
import ToggleSwitch from '@/Components/UI/ToggleSwitch.vue';
import type { NotificationPreferences } from '@/types';

type NotifKey = keyof NotificationPreferences;

const LABELS: Record<NotifKey, { label: string; description: string }> = {
    newFlags: { label: 'New Flags', description: 'Get notified when a post is flagged' },
    appointments: { label: 'Appointments', description: 'Reminders for upcoming appointments' },
    escalations: { label: 'Escalations', description: 'Alerts for new escalation requests' },
    weeklyReports: { label: 'Weekly Reports', description: 'Weekly summary report delivered to email' },
    systemUpdates: { label: 'System Updates', description: 'Platform announcements and updates' },
};

const model = defineModel<NotificationPreferences>({ required: true });
</script>

<template>
    <SettingsSection title="Notification Preferences" description="Choose what alerts you receive">
        <div class="space-y-4">
            <div v-for="(meta, key) in LABELS" :key="key"
                class="flex items-center justify-between py-2 border-b border-border-light last:border-0">
                <div>
                    <p class="text-sm font-medium text-text-primary">{{ meta.label }}</p>
                    <p class="text-xs text-text-muted mt-0.5">{{ meta.description }}</p>
                </div>
                <ToggleSwitch :model-value="model[key as NotifKey]"
                    @update:model-value="model[key as NotifKey] = $event" />
            </div>
        </div>
    </SettingsSection>
</template>
