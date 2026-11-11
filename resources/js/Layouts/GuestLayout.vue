<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="pitch-grid flex min-h-screen flex-col items-center justify-center bg-ink px-4 py-10">
        <Link href="/" class="mb-6 font-display text-5xl tracking-wide text-snow">
            MATCH<span class="text-pitch">DAY</span>
        </Link>
        <div class="w-full max-w-md rounded-3xl bg-white px-6 py-5 text-gray-900 shadow-2xl shadow-black/40">
            <slot />
        </div>
    </div>
</template>
