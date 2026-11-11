<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

const props = defineProps({
    match: Object,
});

onMounted(() => {
    if (!window.Echo) {
        return;
    }

    window.Echo.channel(`matches.${props.match.id}`).listen('.match.updated', () => {
        router.reload({ only: ['match'] });
    });
});

onUnmounted(() => {
    window.Echo?.leave(`matches.${props.match.id}`);
});
</script>

<template>
    <Head :title="`${match.home_team?.name ?? 'Match'} - ${match.away_team?.name ?? ''}`" />

    <PublicLayout>
        <div class="bg-white">
            <div class="flex flex-wrap items-center gap-2 border-b border-[#eee] px-3 py-2 text-[12px] text-mist">
                <Link v-if="match.tournament" :href="route('tournaments.show', match.tournament.slug)" class="font-bold text-navy hover:underline">
                    {{ match.tournament.name }}
                </Link>
                <span>Round {{ match.round }}</span>
                <span v-if="match.status === 'live'" class="font-bold text-live">LIVE</span>
                <span v-else>{{ match.status_label }}</span>
                <span v-if="match.kickoff_label">{{ match.kickoff_label }}</span>
                <span v-if="match.venue">{{ match.venue }}</span>
                <span v-if="match.referee">Referee {{ match.referee }}</span>
            </div>

            <div class="grid grid-cols-[1fr_auto_1fr] items-center gap-3 px-4 py-6">
                <Link v-if="match.home_team" :href="route('teams.show', match.home_team.slug)" class="text-right text-[18px] font-bold hover:underline">
                    {{ match.home_team.name }}
                </Link>
                <span v-else />
                <p class="min-w-[88px] text-center text-[32px] font-bold leading-none" :class="match.status === 'live' ? 'text-live' : ''">
                    {{ match.home_score }}:{{ match.away_score }}
                </p>
                <Link v-if="match.away_team" :href="route('teams.show', match.away_team.slug)" class="text-[18px] font-bold hover:underline">
                    {{ match.away_team.name }}
                </Link>
            </div>
        </div>

        <section class="mt-3 bg-white">
            <h2 class="border-b border-[#eee] bg-[#f0f2f4] px-3 py-2 text-[13px] font-bold">Summary</h2>
            <p v-if="match.events.length === 0" class="px-3 py-4 text-[13px] text-mist">No events yet.</p>
            <ul>
                <li
                    v-for="event in match.events"
                    :key="event.id"
                    class="grid grid-cols-[42px_1fr] items-start border-b border-[#eee] px-3 py-2 text-[13px]"
                    :class="event.team_id === match.away_team?.id ? 'text-right' : ''"
                >
                    <span class="font-bold" :class="event.team_id === match.away_team?.id ? 'col-start-2 row-start-1' : ''">{{ event.minute }}'</span>
                    <div :class="event.team_id === match.away_team?.id ? 'col-start-1 row-start-1' : ''">
                        <p>
                            <Link v-if="event.player_id" :href="route('players.show', event.player_id)" class="font-bold hover:underline">{{ event.player }}</Link>
                            <span v-else>{{ event.player }}</span>
                            <span class="text-mist"> · {{ event.label }}</span>
                        </p>
                        <p v-if="event.assist" class="text-[12px] text-mist">assist {{ event.assist }}</p>
                        <p v-if="event.secondary" class="text-[12px] text-mist">on {{ event.secondary }}</p>
                    </div>
                </li>
            </ul>
        </section>

        <div class="mt-3 grid gap-3 sm:grid-cols-2">
            <section v-for="sideName in ['home', 'away']" :key="sideName" class="bg-white">
                <h2 class="border-b border-[#eee] bg-[#f0f2f4] px-3 py-2 text-[13px] font-bold">
                    {{ sideName === 'home' ? match.home_team?.name : match.away_team?.name }}
                </h2>
                <ul>
                    <li v-for="player in match.lineups[sideName]" :key="player.player_id" class="flex justify-between gap-3 border-b border-[#eee] px-3 py-1.5 text-[13px]">
                        <Link :href="route('players.show', player.player_id)" class="hover:underline">
                            <span class="mr-2 text-mist">{{ player.number }}</span>{{ player.name }}
                        </Link>
                        <span class="text-mist">{{ player.position }}{{ player.is_starter ? '' : ' · bench' }}</span>
                    </li>
                    <li v-if="!match.lineups[sideName]?.length" class="px-3 py-3 text-[13px] text-mist">Lineup not announced.</li>
                </ul>
            </section>
        </div>
    </PublicLayout>
</template>
