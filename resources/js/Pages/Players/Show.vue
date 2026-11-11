<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    player: Object,
    events: Array,
});
</script>

<template>
    <Head :title="player.name" />

    <PublicLayout>
        <section class="panel p-6">
            <p v-if="player.team" class="text-xs uppercase tracking-[0.2em] text-mist">
                <Link :href="route('teams.show', player.team.slug)">{{ player.team.name }}</Link>
            </p>
            <h1 class="mt-2 font-display text-5xl uppercase sm:text-7xl">
                <span class="text-pitch">{{ player.number }}</span> {{ player.name }}
            </h1>
            <p class="mt-2 text-mist">{{ player.position }}</p>
            <p v-if="player.suspended_next" class="mt-3 inline-flex rounded-full bg-flare px-3 py-1 text-xs font-semibold uppercase tracking-[0.14em] text-white">
                Дискваліфікація на наступний матч
            </p>
            <dl class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div v-for="[label, value] in [['Голи', player.stats.goals], ['Асисти', player.stats.assists], ['Жовті', player.stats.yellows], ['Червоні', player.stats.reds]]" :key="label" class="rounded-2xl bg-raised px-4 py-3">
                    <dt class="text-[11px] uppercase tracking-[0.16em] text-mist">{{ label }}</dt>
                    <dd class="font-display text-4xl">{{ value }}</dd>
                </div>
            </dl>
        </section>

        <section class="panel mt-6 p-5">
            <h2 class="font-display text-3xl uppercase">Події сезону</h2>
            <ul class="mt-3 divide-y divide-line text-sm">
                <li v-for="event in events" :key="event.id" class="flex items-center justify-between py-3">
                    <Link :href="route('matches.show', event.match_id)">
                        {{ event.minute }}' {{ event.label }} · {{ event.opponent }}
                    </Link>
                    <span class="text-mist">{{ event.tournament }}</span>
                </li>
            </ul>
        </section>
    </PublicLayout>
</template>
