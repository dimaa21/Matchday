<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted } from 'vue';

const props = defineProps({
    match: Object,
});

let channel;

onMounted(() => {
    if (!window.Echo) {
        return;
    }

    channel = window.Echo.channel(`matches.${props.match.id}`);
    channel.listen('.match.updated', () => {
        router.reload({ only: ['match'] });
    });
});

onUnmounted(() => {
    window.Echo?.leave(`matches.${props.match.id}`);
});

function side(teamId) {
    return teamId === props.match.home_team?.id ? 'text-left' : 'text-right';
}
</script>

<template>
    <Head :title="`${match.home_team?.name ?? 'Матч'} — ${match.away_team?.name ?? ''}`" />

    <PublicLayout>
        <Link v-if="match.tournament" :href="route('tournaments.show', match.tournament.slug)" class="text-sm text-mist hover:text-snow">
            ← {{ match.tournament.name }}
        </Link>

        <section class="panel mt-4 px-4 py-8 text-center sm:px-8">
            <div class="flex items-center justify-center gap-3 text-xs uppercase tracking-[0.2em] text-mist">
                <span>Тур {{ match.round }}</span>
                <span
                    v-if="match.status === 'live'"
                    class="inline-flex items-center gap-2 rounded-full bg-flare px-2 py-1 text-white"
                >
                    <span class="h-2 w-2 animate-pulse rounded-full bg-white" />
                    Live
                </span>
                <span v-else>{{ match.status_label }}</span>
            </div>

            <div class="mt-6 grid items-center gap-4 sm:grid-cols-[1fr_auto_1fr]">
                <Link v-if="match.home_team" :href="route('teams.show', match.home_team.slug)" class="sm:text-right">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl font-display text-2xl sm:ml-auto" :style="{ background: match.home_team.primary_color, color: match.home_team.secondary_color }">
                        {{ match.home_team.initials }}
                    </span>
                    <p class="mt-3 font-display text-4xl uppercase">{{ match.home_team.name }}</p>
                </Link>
                <p class="font-display text-7xl text-pitch sm:text-8xl">{{ match.home_score }}:{{ match.away_score }}</p>
                <Link v-if="match.away_team" :href="route('teams.show', match.away_team.slug)" class="sm:text-left">
                    <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl font-display text-2xl sm:mr-auto" :style="{ background: match.away_team.primary_color, color: match.away_team.secondary_color }">
                        {{ match.away_team.initials }}
                    </span>
                    <p class="mt-3 font-display text-4xl uppercase">{{ match.away_team.name }}</p>
                </Link>
            </div>
            <p class="mt-4 text-sm text-mist">{{ match.kickoff_label }} · {{ match.venue }}<span v-if="match.referee"> · {{ match.referee }}</span></p>
        </section>

        <section class="mt-6 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
            <div class="panel p-5">
                <h2 class="font-display text-3xl uppercase">Хронологія</h2>
                <ol class="mt-4 space-y-3">
                    <li v-if="match.events.length === 0" class="text-sm text-mist">Подій ще немає.</li>
                    <li v-for="event in match.events" :key="event.id" class="flex items-start gap-3" :class="side(event.team_id)">
                        <span class="w-10 font-display text-2xl text-pitch">{{ event.minute }}'</span>
                        <div>
                            <p class="font-medium">{{ event.label }} · {{ event.player }}</p>
                            <p v-if="event.assist" class="text-xs text-mist">асист {{ event.assist }}</p>
                            <p v-if="event.secondary" class="text-xs text-mist">вийшов {{ event.secondary }}</p>
                        </div>
                    </li>
                </ol>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-1">
                <div v-for="sideName in ['home', 'away']" :key="sideName" class="panel p-5">
                    <h2 class="font-display text-2xl uppercase">
                        {{ sideName === 'home' ? match.home_team?.name : match.away_team?.name }}
                    </h2>
                    <ul class="mt-3 space-y-2 text-sm">
                        <li v-for="player in match.lineups[sideName]" :key="player.player_id" class="flex justify-between gap-3">
                            <Link :href="route('players.show', player.player_id)">
                                <span class="mr-2 text-mist">{{ player.number }}</span>{{ player.name }}
                            </Link>
                            <span class="text-mist">{{ player.position }}{{ player.is_starter ? '' : ' · запас' }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>
    </PublicLayout>
</template>
