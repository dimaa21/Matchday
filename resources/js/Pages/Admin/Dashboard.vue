<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    rounds: Array,
});
</script>

<template>
    <Head title="Dashboard" />
    <AdminLayout>
        <h1 class="text-xl font-bold">Dashboard</h1>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <article v-for="[label, value] in [['Matches', stats.matches], ['Goals', stats.goals], ['Goals per match', stats.average], ['Live now', stats.live]]" :key="label" class="bg-white p-4">
                <p class="text-[11px] uppercase text-mist">{{ label }}</p>
                <p class="mt-1 text-[28px] font-bold">{{ value }}</p>
            </article>
        </div>
        <section class="mt-4 bg-white p-4">
            <h2 class="text-[15px] font-bold">Highest-scoring rounds</h2>
            <ul class="mt-3 space-y-3">
                <li v-for="round in rounds" :key="`${round.tournament}-${round.round}`">
                    <div class="flex justify-between text-[13px]">
                        <span>{{ round.tournament }} · round {{ round.round }}</span>
                        <span class="font-bold">{{ round.goals }}</span>
                    </div>
                    <div class="mt-1 h-1.5 bg-[#eef0f2]">
                        <div class="h-1.5 bg-live" :style="{ width: `${Math.min(100, round.goals * 8)}%` }" />
                    </div>
                </li>
            </ul>
        </section>
    </AdminLayout>
</template>
