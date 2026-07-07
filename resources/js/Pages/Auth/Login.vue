<script setup lang="ts">
import InputError from '@/Components/InputError.vue';
import { useAdminLoginSubmission } from '@/composables/useAdminLoginSubmission';
import { Link, usePage } from '@inertiajs/vue3';
import { onMounted, ref } from 'vue';
import type { LoginPageProps } from '@/types';

const props = defineProps<LoginPageProps>();

const page = usePage();
const showPassword = ref(false);
const logoUrl = page.props.assets.logo;

const { form, submit, showInitialMessages } = useAdminLoginSubmission(props.status);

onMounted(showInitialMessages);
</script>

<template>
    <div class="font-inter min-h-screen bg-[#eceae6] flex items-center justify-center px-3 py-8 md:px-4 md:py-12">

        <Link :href="route('landing')"
            class="font-inter fixed top-3 left-3 inline-flex items-center gap-2 text-xs font-semibold text-white bg-[#2d5016] hover:bg-[#1e3a14] rounded-full px-4 py-2 shadow-md transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg hover:shadow-[#2d5016]/30 active:scale-95 active:translate-y-0 z-50 md:top-5 md:left-6 md:px-5 md:py-2.5 md:text-sm">
            ← Back to site
        </Link>

        <div class="w-full max-w-108 flex rounded-2xl overflow-hidden shadow-2xl sm:max-w-132 md:max-w-200 md:rounded-3xl lg:max-w-232 xl:max-w-264">

            <div class="hidden md:flex flex-col items-center justify-center bg-[#2d5016] p-10 w-[44%] shrink-0 text-center lg:p-14">
                <div class="mb-5 flex items-center justify-center rounded-3xl border border-white/10 p-6 lg:mb-6 lg:p-8" style="background: rgba(255,255,255,0.03); box-shadow: 0 4px 24px rgba(0,0,0,0.12), 0 1px 4px rgba(0,0,0,0.08), inset 0 1px 0 rgba(255,255,255,0.06);">
                    <img :src="logoUrl" alt="MoodLink" class="h-24 w-24 lg:h-32 lg:w-32" style="filter: drop-shadow(0 12px 24px rgba(0,0,0,0.5)) drop-shadow(0 4px 8px rgba(0,0,0,0.3));" />
                </div>
                <div class="font-montserrat text-3xl font-black text-white tracking-tight mb-2 lg:text-4xl">MoodLink</div>
                <div class="font-inter text-sm text-white/50 mb-8 lg:mb-10">GCU Admin Portal</div>

                <div class="w-12 h-0.5 bg-white/20 rounded-full mb-8 lg:mb-10"></div>

                <h1 class="font-montserrat text-xl font-bold text-white leading-snug mb-4 lg:text-2xl">
                    Welcome to MoodLink<br />GCU Admin Portal
                </h1>
                <p class="font-inter text-sm text-white/50 leading-relaxed max-w-xs">
                    Enter your provided MoodLink email and password to access the GCU Admin Portal.
                </p>
            </div>

            <div class="flex-1 bg-white px-6 py-8 flex flex-col justify-center sm:px-8 sm:py-10 md:px-12 md:py-14 lg:px-14">
                <h2 class="font-montserrat text-2xl font-bold text-gray-900 mb-1.5 sm:text-3xl">Sign In</h2>
                <p class="font-inter text-sm text-gray-400 mb-8 sm:text-base sm:mb-10">Use your provided MoodLink credentials</p>

                <form @submit.prevent="submit" class="space-y-5 sm:space-y-6">
                    <div>
                        <label for="email" class="font-inter block text-xs font-semibold text-gray-600 uppercase tracking-widest mb-2.5">
                            Email Address
                        </label>
                        <input
                            id="email"
                            v-model="form.email"
                            type="email"
                            name="email"
                            placeholder=""
                            autocomplete="email"
                            class="font-inter w-full rounded-xl border border-gray-200 bg-[#f8f8f6] px-4 py-3 text-sm text-gray-800 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#2d5016]/20 focus:border-[#4a5e28] transition sm:px-5 sm:py-4 sm:text-base"
                            :class="{ 'border-red-300 bg-red-50': form.errors.email }"
                        />
                        <InputError :message="form.errors.email" class="mt-1.5" />
                    </div>

                    <div>
                        <div class="mb-2.5">
                            <label for="password" class="font-inter text-xs font-semibold text-gray-600 uppercase tracking-widest">
                                Password
                            </label>
                        </div>
                        <div class="relative">
                            <input
                                id="password"
                                v-model="form.password"
                                :type="showPassword ? 'text' : 'password'"
                                name="password"
                                autocomplete="current-password"
                                class="font-inter w-full rounded-xl border border-gray-200 bg-[#f8f8f6] px-4 py-3 text-sm text-gray-800 placeholder-gray-300 focus:outline-none focus:ring-2 focus:ring-[#2d5016]/20 focus:border-[#4a5e28] transition pr-16 sm:px-5 sm:py-4 sm:text-base sm:pr-20"
                                :class="{ 'border-red-300 bg-red-50': form.errors.password }"
                            />
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-4 top-1/2 -translate-y-1/2 font-inter text-xs text-gray-400 hover:text-gray-700 transition-colors font-medium sm:right-5 sm:text-sm"
                            >
                                {{ showPassword ? 'Hide' : 'Show' }}
                            </button>
                        </div>
                        <InputError :message="form.errors.password" class="mt-1.5" />
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="font-montserrat w-full bg-[#2d5016] text-white font-bold rounded-xl py-3 text-sm hover:bg-[#1e3a14] hover:-translate-y-0.5 hover:shadow-lg hover:shadow-[#2d5016]/30 active:scale-[0.97] active:translate-y-0 transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed tracking-widest mt-2 sm:py-4 sm:text-base"
                    >
                        {{ form.processing ? 'SIGNING IN…' : 'SIGN IN' }}
                    </button>

                    <p class="font-inter text-xs text-gray-500 text-center sm:text-sm">
                        Forgot your password or can't sign in?
                        <span class="text-[#4a5e28] font-medium">Contact the system administrator</span>
                        to reset your password or unlock your account.
                    </p>
                </form>

                <p class="font-inter text-xs text-gray-500 text-center mt-8 sm:text-sm sm:mt-10">
                    Admin data is encrypted · Authorized personnel only
                </p>
            </div>
        </div>
    </div>
</template>
