<script setup>
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div class="flex min-h-screen flex-col items-center justify-center bg-navy px-4 py-10">
        <Link href="/" class="mb-6 text-[28px] font-bold tracking-tight text-white">MATCHDAY</Link>
        <div class="w-full max-w-md bg-white px-6 py-5 text-navy">
            <slot />
        </div>
    </div>
</template>
