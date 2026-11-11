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
        <section class="bg-white px-3 py-4">
            <p v-if="player.team" class="text-[12px] text-mist">
                <Link :href="route('teams.show', player.team.slug)" class="font-bold text-navy hover:underline">{{ player.team.name }}</Link>
            </p>
            <h1 class="mt-1 text-[22px] font-bold">
                <span class="text-mist">{{ player.number }}</span> {{ player.name }}
            </h1>
            <p class="text-[13px] text-mist">{{ player.position }}</p>
            <p v-if="player.suspended_next" class="mt-2 inline-flex bg-live px-2 py-1 text-[11px] font-bold uppercase text-white">
                Suspended for the next match
            </p>
            <dl class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-4">
                <div v-for="[label, value] in [['Goals', player.stats.goals], ['Assists', player.stats.assists], ['Yellows', player.stats.yellows], ['Reds', player.stats.reds]]" :key="label" class="bg-[#f7f9fa] px-3 py-2">
                    <dt class="text-[11px] uppercase text-mist">{{ label }}</dt>
                    <dd class="text-[22px] font-bold">{{ value }}</dd>
                </div>
            </dl>
        </section>

        <section class="mt-3 bg-white">
            <h2 class="border-b border-[#eee] bg-[#f0f2f4] px-3 py-2 text-[13px] font-bold">Season events</h2>
            <ul>
                <li v-for="event in events" :key="event.id" class="flex items-center justify-between border-b border-[#eee] px-3 py-2 text-[13px]">
                    <Link :href="route('matches.show', event.match_id)" class="hover:underline">
                        {{ event.minute }}' {{ event.label }} · {{ event.opponent }}
                    </Link>
                    <span class="text-mist">{{ event.tournament }}</span>
                </li>
                <li v-if="events.length === 0" class="px-3 py-3 text-[13px] text-mist">No events yet.</li>
            </ul>
        </section>
    </PublicLayout>
</template>
