<script setup lang="ts">
withDefaults(defineProps<{
  appointmentsTotal?: number;
  appointmentsUrgent?: number;
  appointmentsConsultation?: number;
  escalations?: number;
}>(), {
  appointmentsTotal: 0,
  appointmentsUrgent: 0,
  appointmentsConsultation: 0,
  escalations: 0,
});
</script>

<template>
  <div class="grid grid-cols-2 gap-6">
    <!-- Upcoming Appointments -->
    <div class="bg-stat-card-bg rounded-xl p-5 flex items-center gap-5 border border-stat-card-border h-24">
      <div class="shrink-0 w-14 h-14 flex items-center justify-center rounded-lg bg-stat-icon-neutral-bg"
        aria-label="Appointments">
        <i class="fas fa-calendar text-2xl text-stat-icon-neutral-text" />
      </div>

      <div class="flex-1 min-w-0">
        <p class="text-base text-text-muted truncate">Upcoming Appointments</p>
        <div class="flex gap-3 mt-1">
          <span class="text-2xl font-bold text-text-primary shrink-0">
            {{ appointmentsTotal }}
          </span>
          <span class="text-sm text-stat-urgent-text shrink-0">
            Urgent: {{ appointmentsUrgent }}
          </span>
          <span class="text-sm text-text-muted shrink-0">
            Consultation: {{ appointmentsConsultation }}
          </span>
        </div>
      </div>
    </div>

    <!-- Escalation Requests -->
    <div class="bg-stat-card-bg rounded-xl p-5 flex items-center gap-5 border border-stat-card-border h-24">
      <div class="shrink-0 w-14 h-14 flex items-center justify-center rounded-lg bg-stat-icon-danger-bg"
        aria-label="Escalation Requests">
        <i class="fas fa-exclamation-circle text-2xl text-stat-icon-danger-text" />
      </div>

      <div class="flex-1 min-w-0">
        <p class="text-base text-text-muted truncate">Escalation Requests</p>
        <p class="text-2xl font-bold text-text-primary mt-1 truncate">
          {{ escalations }}
        </p>
      </div>
    </div>
  </div>
</template>