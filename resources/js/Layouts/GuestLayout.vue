<script setup lang="ts">
import { Toaster } from 'vue-sonner';
</script>

<template>
    <div class="bg-auth-bg relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-12 sm:px-8">
        <Toaster position="top-center" rich-colors />

        <!-- Background Effects -->
        <div class="heart-wrapper">
            <div class="heart"></div>
        </div>
        <div class="bubble-wrapper">
            <div class="bubble"></div>
        </div>

        <!-- Main Container -->
        <div class="relative z-10 flex w-full flex-col items-center">
            <slot />
        </div>
    </div>
</template>

<style>
@reference "../../css/app.css";

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-up {
    opacity: 0;
    animation: fadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.fade-up-1 {
    animation-delay: 0.05s;
}

.fade-up-2 {
    animation-delay: 0.15s;
}

.fade-up-3 {
    animation-delay: 0.25s;
}

.fade-up-4 {
    animation-delay: 0.35s;
}

.fade-up-5 {
    animation-delay: 0.45s;
}

@keyframes floatHeart {

    0%,
    100% {
        transform: translate(-50%, -50%) scale(1) rotate(-45deg);
    }

    50% {
        transform: translate(-50%, -50%) scale(1.06) rotate(-45deg);
    }
}

.heart-wrapper {
    position: fixed;
    top: -5%;
    left: -8%;
    width: 220px;
    height: 220px;
    pointer-events: none;
    z-index: 0;
    overflow: visible;
}

@media (min-width: 640px) {
    .heart-wrapper {
        width: 420px;
        height: 420px;
    }
}

.heart {
    width: 100%;
    height: 100%;
    background: var(--color-auth-deco-heart);
    opacity: 0.45;
    position: absolute;
    top: 90%;
    left: 100%;
    transform: translate(-50%, -50%) rotate(-45deg);
    animation: floatHeart 6s ease-in-out infinite;
}

.heart::before,
.heart::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    background: var(--color-auth-deco-heart);
    border-radius: 50%;
}

.heart::before {
    top: -50%;
    left: 0;
}

.heart::after {
    top: 0;
    left: 50%;
}

@keyframes floatBubble {

    0%,
    100% {
        transform: translate(50%, 50%) scale(1);
    }

    50% {
        transform: translate(50%, 50%) scale(1.05);
    }
}

.bubble-wrapper {
    position: fixed;
    bottom: 5%;
    right: 5%;
    width: 180px;
    height: 180px;
    pointer-events: none;
    z-index: 0;
}

@media (min-width: 640px) {
    .bubble-wrapper {
        bottom: 15%;
        right: 15%;
        width: 340px;
        height: 340px;
    }
}

.bubble {
    width: 100%;
    height: 100%;
    background: var(--color-auth-deco-bubble);
    border-radius: 50%;
    opacity: 0.4;
    position: absolute;
    top: 0;
    left: 0;
    animation: floatBubble 7s ease-in-out infinite;
}

@media (prefers-reduced-motion: reduce) {
    .fade-up {
        animation: none;
        opacity: 1;
    }

    .heart {
        animation: none;
    }

    .bubble {
        animation: none;
    }
}
</style>

<style>
@reference "../../css/app.css";

@keyframes fadeUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }

    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.fade-up {
    opacity: 0;
    animation: fadeUp 0.55s cubic-bezier(0.22, 1, 0.36, 1) forwards;
}

.fade-up-1 {
    animation-delay: 0.05s;
}

.fade-up-2 {
    animation-delay: 0.15s;
}

.fade-up-3 {
    animation-delay: 0.25s;
}

.fade-up-4 {
    animation-delay: 0.35s;
}

.fade-up-5 {
    animation-delay: 0.45s;
}

@keyframes floatHeart {

    0%,
    100% {
        transform: translate(-50%, -50%) scale(1) rotate(-45deg);
    }

    50% {
        transform: translate(-50%, -50%) scale(1.06) rotate(-45deg);
    }
}

.heart-wrapper {
    position: fixed;
    top: -5%;
    left: -8%;
    width: 220px;
    height: 220px;
    pointer-events: none;
    z-index: 0;
    overflow: visible;
}

@media (min-width: 640px) {
    .heart-wrapper {
        width: 420px;
        height: 420px;
    }
}

.heart {
    width: 100%;
    height: 100%;
    background: var(--color-auth-deco-heart);
    opacity: 0.45;
    position: absolute;
    top: 90%;
    left: 100%;
    transform: translate(-50%, -50%) rotate(-45deg);
    animation: floatHeart 6s ease-in-out infinite;
}

.heart::before,
.heart::after {
    content: '';
    position: absolute;
    width: 100%;
    height: 100%;
    background: var(--color-auth-deco-heart);
    border-radius: 50%;
}

.heart::before {
    top: -50%;
    left: 0;
}

.heart::after {
    top: 0;
    left: 50%;
}

@keyframes floatBubble {

    0%,
    100% {
        transform: translate(50%, 50%) scale(1);
    }

    50% {
        transform: translate(50%, 50%) scale(1.05);
    }
}

.bubble-wrapper {
    position: fixed;
    bottom: 5%;
    right: 5%;
    width: 180px;
    height: 180px;
    pointer-events: none;
    z-index: 0;
}

@media (min-width: 640px) {
    .bubble-wrapper {
        bottom: 15%;
        right: 15%;
        width: 340px;
        height: 340px;
    }
}

.bubble {
    width: 100%;
    height: 100%;
    background: var(--color-auth-deco-bubble);
    border-radius: 50%;
    opacity: 0.4;
    position: absolute;
    top: 0;
    left: 0;
    animation: floatBubble 7s ease-in-out infinite;
}

@media (prefers-reduced-motion: reduce) {
    .fade-up {
        animation: none;
        opacity: 1;
    }

    .heart {
        animation: none;
    }

    .bubble {
        animation: none;
    }
}
</style>
