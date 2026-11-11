<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    rounds: Array,
});
</script>

<template>
    <Head title="Дашборд" />
    <AdminLayout>
        <h1 class="font-display text-5xl uppercase">Дашборд</h1>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <article v-for="[label, value] in [['Матчі', stats.matches], ['Голи', stats.goals], ['Голів за матч', stats.average], ['Зараз live', stats.live]]" :key="label" class="rounded-3xl border border-line bg-panel p-5">
                <p class="text-[11px] uppercase tracking-[0.16em] text-mist">{{ label }}</p>
                <p class="mt-2 font-display text-5xl text-pitch">{{ value }}</p>
            </article>
        </div>
        <section class="mt-8 rounded-3xl border border-line bg-panel p-5">
            <h2 class="font-display text-3xl uppercase">Найрезультативніші тури</h2>
            <ul class="mt-4 space-y-3">
                <li v-for="round in rounds" :key="`${round.tournament}-${round.round}`">
                    <div class="flex justify-between text-sm">
                        <span>{{ round.tournament }} · тур {{ round.round }}</span>
                        <span class="text-pitch">{{ round.goals }}</span>
                    </div>
                    <div class="mt-1 h-2 rounded-full bg-raised">
                        <div class="h-2 rounded-full bg-pitch" :style="{ width: `${Math.min(100, round.goals * 8)}%` }" />
                    </div>
                </li>
            </ul>
        </section>
    </AdminLayout>
</template>
