<script setup lang="ts">
import { ArrowDownTrayIcon } from '@heroicons/vue/24/solid';
import { usePage } from '@inertiajs/vue3';
const logoUrl = usePage().props.assets.logo;

defineProps<{
    apk: {
        version: string;
        size: string;
        minOs: string;
        updated: string;
    };
}>();
</script>

<template>
    <div class="landing-page font-inter">
        <!-- Navigation -->
        <nav class="sticky top-0 z-50 border-b border-gray-100 bg-white">
            <div
                class="mx-auto flex h-16 max-w-7xl items-center px-4 md:h-24 md:px-8"
            >
                <!-- Logo -->
                <div class="flex flex-1 items-center gap-2 md:gap-3">
                    <img
                        :src="logoUrl"
                        alt="MoodLink"
                        class="h-11 w-11 shrink-0 md:h-20 md:w-20"
                    />
                    <div
                        class="font-inter hidden text-xs leading-tight text-gray-400 sm:block"
                    >
                        FEU Diliman<br />GCU
                    </div>
                </div>

                <!-- Nav Links -->
                <div class="hidden items-center gap-8 lg:flex">
                    <a
                        href="#features"
                        class="font-inter text-base font-medium text-gray-600 transition-colors hover:text-[#4a5e28]"
                        >Features</a
                    >
                    <a
                        href="#how-it-works"
                        class="font-inter text-base font-medium text-gray-600 transition-colors hover:text-[#4a5e28]"
                        >How it works</a
                    >
                    <a
                        href="#download"
                        class="font-inter text-base font-medium text-gray-600 transition-colors hover:text-[#4a5e28]"
                        >Download</a
                    >
                </div>

                <!-- CTA Buttons -->
                <div
                    class="flex flex-1 items-center justify-end gap-2 sm:gap-2.5 md:gap-3"
                >
                    <a
                        href="#download"
                        class="font-inter hover:bg-search-button-active inline-flex items-center gap-1.5 rounded-full bg-[#3a4f1e] px-3 py-1.5 text-xs font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-[#3a4f1e]/30 active:translate-y-0 active:scale-95 sm:gap-2 sm:px-5 sm:py-2.5 sm:text-sm md:px-7 md:py-3.5 md:text-base"
                    >
                        <ArrowDownTrayIcon
                            class="h-3.5 w-3.5 shrink-0 sm:h-4 sm:w-4 md:h-5 md:w-5"
                        />
                        Download APK
                    </a>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <section
            class="hero-gradient flex min-h-screen items-center overflow-hidden"
        >
            <div
                class="mx-auto grid w-full max-w-7xl grid-cols-1 items-center gap-10 px-4 py-16 md:gap-16 md:px-8 md:py-24 lg:grid-cols-2"
            >
                <!-- Left Content -->
                <div>
                    <div
                        class="mb-6 flex flex-wrap items-center gap-2 md:gap-3"
                    >
                        <span
                            class="font-inter rounded-full bg-[#3a4f1e] px-3 py-1.5 text-xs font-bold tracking-widest text-white uppercase"
                            >FEU DILIMAN</span
                        >
                        <span
                            class="font-inter text-sm text-gray-500 md:text-base"
                            >The official GCU wellness app</span
                        >
                    </div>
                    <h1
                        class="font-montserrat mb-6 text-4xl leading-[1.05] font-black text-gray-900 md:text-6xl lg:text-7xl"
                    >
                        How are you<br />feeling today?
                    </h1>
                    <p
                        class="font-inter mb-6 max-w-lg text-base leading-relaxed text-gray-500 md:mb-10 md:text-lg"
                    >
                        MoodLink helps FEU Diliman students check in on their
                        mood, journal their patterns, share safely in MoodSpace,
                        chat with MoLi, and book time with a GCU counselor — all
                        in one gentle app.
                    </p>
                    <div
                        class="mb-8 flex flex-wrap items-center gap-4 md:mb-12 md:gap-5"
                    >
                        <a
                            href="#download"
                            class="font-inter hover:bg-search-button-active inline-flex items-center gap-2 rounded-full bg-[#3a4f1e] px-7 py-3.5 text-base font-semibold text-white transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-[#3a4f1e]/30 active:translate-y-0 active:scale-95"
                        >
                            <ArrowDownTrayIcon class="h-5 w-5 shrink-0" />
                            Download APK
                        </a>
                        <span class="font-inter text-sm text-gray-400"
                            >v{{ apk.version }} · {{ apk.size }} ·
                            {{ apk.minOs }}</span
                        >
                    </div>
                    <!-- Stats -->
                    <div class="flex flex-wrap gap-6 md:gap-10">
                        <div>
                            <div
                                class="font-montserrat text-3xl font-black text-gray-800"
                            >
                                5
                            </div>
                            <div class="font-inter mt-1 text-sm text-gray-400">
                                Connected modules
                            </div>
                        </div>
                        <div>
                            <div
                                class="font-montserrat text-3xl font-black text-gray-800"
                            >
                                4
                            </div>
                            <div class="font-inter mt-1 text-sm text-gray-400">
                                Doors per check-in
                            </div>
                        </div>
                        <div>
                            <div
                                class="font-montserrat text-3xl font-black text-[#4a5e28]"
                            >
                                100%
                            </div>
                            <div class="font-inter mt-1 text-sm text-gray-400">
                                Confidential
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right: Phone Mockup -->
                <div class="flex justify-center lg:justify-end">
                    <div class="phone-frame">
                        <div class="phone-notch"></div>
                        <div class="phone-screen">
                            <div class="p-5">
                                <!-- Greeting -->
                                <div
                                    class="mb-1 flex items-start justify-between"
                                >
                                    <div>
                                        <div
                                            class="font-inter text-xs text-gray-400"
                                        >
                                            Good evening ✨
                                        </div>
                                        <div
                                            class="font-montserrat text-lg leading-tight font-bold text-gray-800"
                                        >
                                            How are you<br />feeling today?
                                        </div>
                                    </div>
                                    <div
                                        class="font-inter flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#d4a574] text-sm font-bold text-white"
                                    >
                                        GM
                                    </div>
                                </div>

                                <!-- Mood Check-in Card -->
                                <div
                                    class="mt-4 mb-4 rounded-2xl bg-white p-4 shadow-sm"
                                >
                                    <div class="mb-3 flex items-center gap-2">
                                        <div
                                            class="flex h-7 w-7 items-center justify-center rounded-lg bg-[#e8f0d8] text-sm"
                                        >
                                            📋
                                        </div>
                                        <div>
                                            <div
                                                class="font-inter text-sm font-semibold text-gray-800"
                                            >
                                                Mood Check-In
                                            </div>
                                            <div
                                                class="font-inter text-xs text-gray-400"
                                            >
                                                4 quick questions.
                                            </div>
                                        </div>
                                    </div>
                                    <div class="mb-3 grid grid-cols-4 gap-1.5">
                                        <div
                                            class="flex flex-col items-center gap-1"
                                        >
                                            <div
                                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-yellow-50 text-base"
                                            >
                                                ⚡
                                            </div>
                                            <span
                                                class="font-inter text-[10px] text-gray-500"
                                                >Energy</span
                                            >
                                        </div>
                                        <div
                                            class="flex flex-col items-center gap-1"
                                        >
                                            <div
                                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-red-50 text-base"
                                            >
                                                ⏰
                                            </div>
                                            <span
                                                class="font-inter text-[10px] text-gray-500"
                                                >Duration</span
                                            >
                                        </div>
                                        <div
                                            class="flex flex-col items-center gap-1"
                                        >
                                            <div
                                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-pink-50 text-base"
                                            >
                                                ❤️
                                            </div>
                                            <span
                                                class="font-inter text-[10px] text-gray-500"
                                                >Heart</span
                                            >
                                        </div>
                                        <div
                                            class="flex flex-col items-center gap-1"
                                        >
                                            <div
                                                class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-base"
                                            >
                                                📦
                                            </div>
                                            <span
                                                class="font-inter text-[10px] text-gray-500"
                                                >Context</span
                                            >
                                        </div>
                                    </div>
                                    <div
                                        class="font-inter rounded-full bg-[#3a4f1e] py-2 text-center text-xs font-semibold text-white"
                                    >
                                        Start Check-In →
                                    </div>
                                </div>

                                <!-- Stats Row -->
                                <div class="mb-4 grid grid-cols-3 gap-2">
                                    <div
                                        class="rounded-xl bg-white p-2.5 text-center shadow-sm"
                                    >
                                        <div
                                            class="font-montserrat text-base font-bold text-gray-800"
                                        >
                                            9
                                        </div>
                                        <div
                                            class="font-inter text-[10px] text-gray-400"
                                        >
                                            Check-Ins
                                        </div>
                                    </div>
                                    <div
                                        class="rounded-xl bg-[#e8f0d8] p-2.5 text-center shadow-sm"
                                    >
                                        <div
                                            class="font-inter text-sm font-bold text-[#3a4f1e]"
                                        >
                                            Drained
                                        </div>
                                        <div
                                            class="font-inter text-[10px] text-gray-400"
                                        >
                                            Top mood
                                        </div>
                                    </div>
                                    <div
                                        class="rounded-xl bg-yellow-50 p-2.5 text-center shadow-sm"
                                    >
                                        <div
                                            class="font-inter text-sm font-bold text-yellow-500"
                                        >
                                            ⚡ 3
                                        </div>
                                        <div
                                            class="font-inter text-[10px] text-gray-400"
                                        >
                                            Best streak
                                        </div>
                                    </div>
                                </div>

                                <!-- Bottom Nav -->
                                <div
                                    class="flex items-center justify-around rounded-2xl bg-white px-2 py-2.5 shadow-sm"
                                >
                                    <div
                                        class="flex flex-col items-center gap-0.5"
                                    >
                                        <span class="text-xl">🏠</span>
                                        <span
                                            class="font-inter text-[9px] font-semibold text-[#3a4f1e]"
                                            >Home</span
                                        >
                                    </div>
                                    <div
                                        class="flex flex-col items-center gap-0.5"
                                    >
                                        <span class="text-xl">📓</span>
                                        <span
                                            class="font-inter text-[9px] text-gray-400"
                                            >Journal</span
                                        >
                                    </div>
                                    <div
                                        class="flex flex-col items-center gap-0.5"
                                    >
                                        <span class="text-xl">🌸</span>
                                        <span
                                            class="font-inter text-[9px] text-gray-400"
                                            >Space</span
                                        >
                                    </div>
                                    <div
                                        class="flex flex-col items-center gap-0.5"
                                    >
                                        <span class="text-xl">🔔</span>
                                        <span
                                            class="font-inter text-[9px] text-gray-400"
                                            >Alerts</span
                                        >
                                    </div>
                                    <div
                                        class="flex flex-col items-center gap-0.5"
                                    >
                                        <span class="text-xl">📅</span>
                                        <span
                                            class="font-inter text-[9px] text-gray-400"
                                            >Book</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Four Moods Section -->
        <section id="features" class="bg-[#f0f4eb] px-4 py-12 md:px-8 md:py-24">
            <div class="mx-auto max-w-7xl">
                <h2
                    class="font-montserrat mb-8 text-center text-3xl font-bold text-[#1e3a14] md:mb-14 md:text-4xl"
                >
                    Four moods, honestly tracked
                </h2>
                <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-4">
                    <!-- Excited — golden yellow -->
                    <div
                        class="mood-card cursor-pointer rounded-2xl border border-[#fbbf24] bg-[#fefce8] p-5 text-center shadow-sm transition-all duration-300 hover:-translate-y-2 hover:border-[#f59e0b] hover:shadow-xl hover:shadow-yellow-100 sm:p-8"
                    >
                        <div
                            class="mood-icon mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#fde047] text-2xl transition-transform duration-300 hover:scale-110 sm:mb-4 sm:h-14 sm:w-14 sm:text-3xl"
                        >
                            ⚡
                        </div>
                        <div
                            class="font-montserrat mb-2 text-base font-bold text-[#713f12] sm:text-lg"
                        >
                            Excited
                        </div>
                        <div
                            class="font-inter text-sm text-[#92400e] sm:text-base"
                        >
                            Buzzing, charged, full of fuel
                        </div>
                    </div>
                    <!-- Content — mint green -->
                    <div
                        class="mood-card cursor-pointer rounded-2xl border border-[#4ade80] bg-[#f0fdf4] p-5 text-center shadow-sm transition-all duration-300 hover:-translate-y-2 hover:border-[#22c55e] hover:shadow-xl hover:shadow-green-100 sm:p-8"
                    >
                        <div
                            class="mood-icon mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#4ade80] text-2xl transition-transform duration-300 sm:mb-4 sm:h-14 sm:w-14 sm:text-3xl"
                        >
                            🍀
                        </div>
                        <div
                            class="font-montserrat mb-2 text-base font-bold text-[#14532d] sm:text-lg"
                        >
                            Content
                        </div>
                        <div
                            class="font-inter text-sm text-[#166534] sm:text-base"
                        >
                            Calm, settled, at ease
                        </div>
                    </div>
                    <!-- Drained — soft purple -->
                    <div
                        class="mood-card cursor-pointer rounded-2xl border border-[#a78bfa] bg-[#f5f3ff] p-5 text-center shadow-sm transition-all duration-300 hover:-translate-y-2 hover:border-[#8b5cf6] hover:shadow-xl hover:shadow-purple-100 sm:p-8"
                    >
                        <div
                            class="mood-icon mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#c4b5fd] text-2xl transition-transform duration-300 sm:mb-4 sm:h-14 sm:w-14 sm:text-3xl"
                        >
                            🌧️
                        </div>
                        <div
                            class="font-montserrat mb-2 text-base font-bold text-[#4c1d95] sm:text-lg"
                        >
                            Drained
                        </div>
                        <div
                            class="font-inter text-sm text-[#5b21b6] sm:text-base"
                        >
                            Tired, sluggish, running low
                        </div>
                    </div>
                    <!-- Stressed — deep red -->
                    <div
                        class="mood-card cursor-pointer rounded-2xl border border-red-200 bg-[#fef2f2] p-5 text-center shadow-sm transition-all duration-300 hover:-translate-y-2 hover:border-red-400 hover:shadow-xl hover:shadow-red-100 sm:p-8"
                    >
                        <div
                            class="mood-icon mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-2xl bg-red-400 text-2xl transition-transform duration-300 sm:mb-4 sm:h-14 sm:w-14 sm:text-3xl"
                        >
                            😫
                        </div>
                        <div
                            class="font-montserrat mb-2 text-base font-bold text-[#7f1d1d] sm:text-lg"
                        >
                            Stressed
                        </div>
                        <div
                            class="font-inter text-sm text-[#991b1b] sm:text-base"
                        >
                            Tense, on-edge, overwhelmed
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Five Modules Section -->
        <section class="bg-white px-4 py-12 md:px-8 md:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="mb-8 text-center md:mb-14">
                    <div
                        class="font-inter mb-4 text-sm font-bold tracking-widest text-[#4a5e28] uppercase"
                    >
                        EVERYTHING IN ONE APP
                    </div>
                    <h2
                        class="font-montserrat text-3xl font-black text-gray-900 sm:text-4xl md:text-5xl"
                    >
                        Five modules for student wellbeing
                    </h2>
                </div>
                <div
                    class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3"
                >
                    <div
                        class="module-card group cursor-pointer rounded-2xl border border-gray-100 bg-[#f8faf4] p-6 transition-all duration-300 hover:-translate-y-2 hover:border-[#c8dba0] hover:shadow-xl hover:shadow-green-100 sm:p-8"
                    >
                        <div
                            class="module-icon mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e0ead0] text-xl transition-transform duration-300 group-hover:scale-110 sm:mb-5 sm:h-14 sm:w-14 sm:text-2xl"
                        >
                            📋
                        </div>
                        <h3
                            class="font-montserrat mb-2 text-lg font-bold text-gray-900 transition-colors duration-300 group-hover:text-[#4a5e28] sm:mb-3 sm:text-xl"
                        >
                            Mood Check-In
                        </h3>
                        <p
                            class="font-inter text-sm leading-relaxed text-gray-500 sm:text-base"
                        >
                            Four quick "doors" — Energy, Duration, Heart, and
                            Context — capture how you really feel in under a
                            minute.
                        </p>
                    </div>
                    <div
                        class="module-card group cursor-pointer rounded-2xl border border-gray-100 bg-[#f8faf4] p-6 transition-all duration-300 hover:-translate-y-2 hover:border-[#c8dba0] hover:shadow-xl hover:shadow-green-100 sm:p-8"
                    >
                        <div
                            class="module-icon mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e0ead0] text-xl transition-transform duration-300 group-hover:scale-110 sm:mb-5 sm:h-14 sm:w-14 sm:text-2xl"
                        >
                            📓
                        </div>
                        <h3
                            class="font-montserrat mb-2 text-lg font-bold text-gray-900 transition-colors duration-300 group-hover:text-[#4a5e28] sm:mb-3 sm:text-xl"
                        >
                            My Journal
                        </h3>
                        <p
                            class="font-inter text-sm leading-relaxed text-gray-500 sm:text-base"
                        >
                            Weekly, monthly, and yearly mood trends, a color
                            heatmap, check-in streaks, and every entry in one
                            private place.
                        </p>
                    </div>
                    <div
                        class="module-card group cursor-pointer rounded-2xl border border-gray-100 bg-[#f8faf4] p-6 transition-all duration-300 hover:-translate-y-2 hover:border-[#c8dba0] hover:shadow-xl hover:shadow-green-100 sm:p-8"
                    >
                        <div
                            class="module-icon mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e0ead0] text-xl transition-transform duration-300 group-hover:scale-110 sm:mb-5 sm:h-14 sm:w-14 sm:text-2xl"
                        >
                            🌸
                        </div>
                        <h3
                            class="font-montserrat mb-2 text-lg font-bold text-gray-900 transition-colors duration-300 group-hover:text-[#4a5e28] sm:mb-3 sm:text-xl"
                        >
                            MoodSpace
                        </h3>
                        <p
                            class="font-inter text-sm leading-relaxed text-gray-500 sm:text-base"
                        >
                            A safe, anonymous space to share how you feel and
                            react to others. Every post is screened by the GCU
                            before it goes live.
                        </p>
                    </div>
                    <div
                        class="module-card group cursor-pointer rounded-2xl border border-gray-100 bg-[#f8faf4] p-6 transition-all duration-300 hover:-translate-y-2 hover:border-[#c8dba0] hover:shadow-xl hover:shadow-green-100 sm:p-8"
                    >
                        <div
                            class="module-icon mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e0ead0] text-xl transition-transform duration-300 group-hover:scale-110 sm:mb-5 sm:h-14 sm:w-14 sm:text-2xl"
                        >
                            📅
                        </div>
                        <h3
                            class="font-montserrat mb-2 text-lg font-bold text-gray-900 transition-colors duration-300 group-hover:text-[#4a5e28] sm:mb-3 sm:text-xl"
                        >
                            Appointments
                        </h3>
                        <p
                            class="font-inter text-sm leading-relaxed text-gray-500 sm:text-base"
                        >
                            Book a confidential session with a GCU counselor,
                            see your scheduled visits, and get gentle reminders
                            before each one.
                        </p>
                    </div>
                    <div
                        class="module-card group cursor-pointer rounded-2xl border border-gray-100 bg-[#f8faf4] p-6 transition-all duration-300 hover:-translate-y-2 hover:border-[#c8dba0] hover:shadow-xl hover:shadow-green-100 sm:p-8"
                    >
                        <div
                            class="module-icon mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e0ead0] text-xl transition-transform duration-300 group-hover:scale-110 sm:mb-5 sm:h-14 sm:w-14 sm:text-2xl"
                        >
                            💬
                        </div>
                        <h3
                            class="font-montserrat mb-2 text-lg font-bold text-gray-900 transition-colors duration-300 group-hover:text-[#4a5e28] sm:mb-3 sm:text-xl"
                        >
                            MoLi · Wellness AI
                        </h3>
                        <p
                            class="font-inter text-sm leading-relaxed text-gray-500 sm:text-base"
                        >
                            An always-on companion for emotional support that
                            remembers your recent moods — never a replacement
                            for a real counselor.
                        </p>
                    </div>
                    <div
                        class="module-card group cursor-pointer rounded-2xl border border-gray-100 bg-[#f8faf4] p-6 transition-all duration-300 hover:-translate-y-2 hover:border-[#c8dba0] hover:shadow-xl hover:shadow-green-100 sm:p-8"
                    >
                        <div
                            class="module-icon mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-[#e0ead0] text-xl transition-transform duration-300 group-hover:scale-110 sm:mb-5 sm:h-14 sm:w-14 sm:text-2xl"
                        >
                            🔔
                        </div>
                        <h3
                            class="font-montserrat mb-2 text-lg font-bold text-gray-900 transition-colors duration-300 group-hover:text-[#4a5e28] sm:mb-3 sm:text-xl"
                        >
                            Smart Alerts
                        </h3>
                        <p
                            class="font-inter text-sm leading-relaxed text-gray-500 sm:text-base"
                        >
                            Stay updated on appointments set by the GCU,
                            MoodSpace post approvals, and daily check-in
                            reminders — all in one hub.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- How It Works Section -->
        <section
            id="how-it-works"
            class="bg-[#1e2a14] px-4 py-12 md:px-8 md:py-24"
        >
            <div class="mx-auto max-w-7xl">
                <div class="mb-8 text-center md:mb-14">
                    <div
                        class="font-inter mb-4 text-sm font-bold tracking-widest text-[#8bba5e] uppercase"
                    >
                        GETTING STARTED
                    </div>
                    <h2
                        class="font-montserrat text-3xl font-black text-white sm:text-4xl md:text-5xl"
                    >
                        Up and running in three steps
                    </h2>
                </div>
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <!-- Step 1 -->
                    <div
                        class="group cursor-pointer rounded-2xl border border-white/5 bg-[#263318] p-6 transition-all duration-300 hover:-translate-y-2 hover:border-white/15 hover:bg-[#2e3d1e] hover:shadow-xl hover:shadow-black/30 sm:p-9"
                    >
                        <div
                            class="font-montserrat mb-4 flex h-10 w-10 items-center justify-center rounded-xl bg-[#4a5e28] text-base font-black text-white transition-transform duration-300 group-hover:scale-110 sm:mb-6 sm:h-11 sm:w-11"
                        >
                            1
                        </div>
                        <h3
                            class="font-montserrat mb-3 text-xl font-bold text-white transition-colors duration-300 group-hover:text-[#8bba5e] sm:text-2xl"
                        >
                            Download the APK
                        </h3>
                        <p
                            class="font-inter text-sm leading-relaxed text-gray-400 sm:text-base"
                        >
                            Get the official MoodLink build for Android from
                            this page and install it on your phone.
                        </p>
                    </div>
                    <!-- Step 2 -->
                    <div
                        class="group cursor-pointer rounded-2xl border border-white/5 bg-[#263318] p-6 transition-all duration-300 hover:-translate-y-2 hover:border-white/15 hover:bg-[#2e3d1e] hover:shadow-xl hover:shadow-black/30 sm:p-9"
                    >
                        <div
                            class="font-montserrat mb-6 flex h-11 w-11 items-center justify-center rounded-xl bg-[#c8922a] text-base font-black text-white transition-transform duration-300 group-hover:scale-110"
                        >
                            2
                        </div>
                        <h3
                            class="font-montserrat mb-3 text-xl font-bold text-white transition-colors duration-300 group-hover:text-[#e6b05a] sm:text-2xl"
                        >
                            Verify with the GCU
                        </h3>
                        <p
                            class="font-inter text-sm leading-relaxed text-gray-400 sm:text-base"
                        >
                            Submit your name, year, program, and student number.
                            The Guidance &amp; Counseling Unit reviews and
                            approves your account.
                        </p>
                    </div>
                    <!-- Step 3 -->
                    <div
                        class="group cursor-pointer rounded-2xl border border-white/5 bg-[#263318] p-6 transition-all duration-300 hover:-translate-y-2 hover:border-white/15 hover:bg-[#2e3d1e] hover:shadow-xl hover:shadow-black/30 sm:p-9"
                    >
                        <div
                            class="font-montserrat mb-6 flex h-11 w-11 items-center justify-center rounded-xl bg-[#3a4f1e] text-base font-black text-white transition-transform duration-300 group-hover:scale-110"
                        >
                            3
                        </div>
                        <h3
                            class="font-montserrat mb-3 text-xl font-bold text-white transition-colors duration-300 group-hover:text-[#8bba5e] sm:text-2xl"
                        >
                            Sign in &amp; check in
                        </h3>
                        <p
                            class="font-inter text-sm leading-relaxed text-gray-400 sm:text-base"
                        >
                            Log in with your FEU student credentials and start
                            your first daily mood check-in right from the home
                            screen.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Download Section -->
        <section id="download" class="bg-[#f0f4eb] px-4 py-12 md:px-8 md:py-24">
            <div class="mx-auto max-w-7xl">
                <div class="max-w-2xl rounded-3xl bg-white p-6 sm:p-8 md:p-12">
                    <h2
                        class="font-montserrat mb-4 text-2xl font-black text-gray-900 md:text-4xl"
                    >
                        Download MoodLink for Android
                    </h2>
                    <p
                        class="font-inter mb-6 text-base leading-relaxed text-gray-400 md:mb-10"
                    >
                        Install the latest build directly. If your phone asks,
                        allow installs from this source, then open MoodLink and
                        sign in with your FEU student credentials.
                    </p>
                    <div
                        class="mb-6 flex flex-wrap items-center gap-4 md:mb-10"
                    >
                        <a
                            :href="route('download.apk')"
                            rel="noopener noreferrer"
                            referrerpolicy="no-referrer"
                            class="font-inter hover:bg-search-button-active inline-flex items-center gap-2 rounded-full bg-[#3a4f1e] px-7 py-3.5 text-base font-semibold text-white transition-colors"
                        >
                            <ArrowDownTrayIcon class="h-5 w-5 shrink-0" />
                            Download APK
                        </a>
                        <a
                            href="#"
                            class="font-inter inline-flex items-center gap-2 rounded-full bg-[#e8f0d8] px-7 py-3.5 text-base font-semibold text-[#4a5e28] transition-colors hover:bg-[#dde6c4]"
                        >
                            Release notes
                        </a>
                    </div>
                    <div class="flex flex-wrap gap-6 sm:gap-10">
                        <div>
                            <div
                                class="font-inter mb-1.5 text-xs font-bold tracking-widest text-gray-400 uppercase"
                            >
                                VERSION
                            </div>
                            <div
                                class="font-montserrat text-base font-bold text-gray-800"
                            >
                                {{ apk.version }}
                            </div>
                        </div>
                        <div>
                            <div
                                class="font-inter mb-1.5 text-xs font-bold tracking-widest text-gray-400 uppercase"
                            >
                                SIZE
                            </div>
                            <div
                                class="font-montserrat text-base font-bold text-gray-800"
                            >
                                {{ apk.size }}
                            </div>
                        </div>
                        <div>
                            <div
                                class="font-inter mb-1.5 text-xs font-bold tracking-widest text-gray-400 uppercase"
                            >
                                REQUIRES
                            </div>
                            <div
                                class="font-montserrat text-base font-bold text-gray-800"
                            >
                                {{ apk.minOs }}
                            </div>
                        </div>
                        <div>
                            <div
                                class="font-inter mb-1.5 text-xs font-bold tracking-widest text-gray-400 uppercase"
                            >
                                UPDATED
                            </div>
                            <div
                                class="font-montserrat text-base font-bold text-gray-800"
                            >
                                {{ apk.updated }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer
            class="border-t border-[#dde8c8] bg-[#f0f4eb] px-4 py-8 md:px-8 md:py-10"
        >
            <div
                class="mx-auto flex max-w-7xl flex-col items-center justify-between gap-5 md:flex-row"
            >
                <div class="flex items-center gap-2">
                    <img
                        :src="logoUrl"
                        alt="MoodLink"
                        class="h-12 w-12 shrink-0"
                    />
                    <span class="font-inter text-sm text-gray-400"
                        >FEU Diliman · Guidance &amp; Counseling Unit</span
                    >
                </div>
                <div class="flex items-center gap-5 md:gap-8">
                    <a
                        href="#features"
                        class="font-inter text-sm text-gray-500 transition-colors hover:text-[#4a5e28]"
                        >Features</a
                    >
                    <a
                        href="#download"
                        class="font-inter text-sm text-gray-500 transition-colors hover:text-[#4a5e28]"
                        >Download</a
                    >
                </div>
                <div class="font-inter text-sm text-gray-400">
                    © 2026 MoodLink · For FEU Diliman students
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.hero-gradient {
    background:
        radial-gradient(
            circle at top left,
            rgba(134, 178, 80, 0.2) 0%,
            transparent 50%
        ),
        radial-gradient(
            circle at bottom right,
            rgba(74, 94, 40, 0.12) 0%,
            transparent 45%
        ),
        linear-gradient(135deg, #f4f8ee 0%, #e8f0d8 50%, #ddeac4 100%);
}

.phone-frame {
    width: 270px;
    height: 540px;
    background: #1a1a1a;
    border-radius: 44px;
    padding: 12px;
    box-shadow:
        0 40px 80px rgba(0, 0, 0, 0.3),
        0 0 0 1px rgba(255, 255, 255, 0.05);
    position: relative;
    flex-shrink: 0;
}

.phone-notch {
    width: 70px;
    height: 20px;
    background: #1a1a1a;
    border-radius: 0 0 16px 16px;
    position: absolute;
    top: 12px;
    left: 50%;
    transform: translateX(-50%);
    z-index: 10;
}

.phone-screen {
    width: 100%;
    height: 100%;
    background: #f5f5f5;
    border-radius: 34px;
    overflow: hidden;
    padding-top: 26px;
}

.mood-card:hover .mood-icon {
    animation: mood-bounce 0.4s ease;
}

@keyframes mood-bounce {
    0% {
        transform: scale(1);
    }

    40% {
        transform: scale(1.2);
    }

    70% {
        transform: scale(0.92);
    }

    100% {
        transform: scale(1);
    }
}
</style>
