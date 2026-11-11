<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    team: Object,
    players: Array,
    recent: Array,
});
</script>

<template>
    <Head :title="team.name" />

    <PublicLayout>
        <section class="panel overflow-hidden">
            <div class="h-3" :style="{ background: team.primary_color }" />
            <div class="flex flex-wrap items-end justify-between gap-4 p-6">
                <div class="flex items-center gap-4">
                    <span class="flex h-16 w-16 items-center justify-center rounded-2xl font-display text-3xl" :style="{ background: team.primary_color, color: team.secondary_color }">
                        {{ team.initials }}
                    </span>
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-mist">{{ team.city }}</p>
                        <h1 class="font-display text-5xl uppercase">{{ team.name }}</h1>
                    </div>
                </div>
                <dl class="grid grid-cols-3 gap-4 text-center">
                    <div>
                        <dt class="text-[11px] uppercase tracking-[0.16em] text-mist">І</dt>
                        <dd class="font-display text-3xl">{{ team.stats.played }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-[0.16em] text-mist">В-Н-П</dt>
                        <dd class="font-display text-3xl">{{ team.stats.won }}-{{ team.stats.drawn }}-{{ team.stats.lost }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase tracking-[0.16em] text-mist">М'ячі</dt>
                        <dd class="font-display text-3xl">{{ team.stats.goals_for }}:{{ team.stats.goals_against }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <div class="mt-6 grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
            <section class="panel overflow-x-auto">
                <table class="w-full min-w-[560px] text-left text-sm">
                    <thead class="text-[11px] uppercase tracking-[0.16em] text-mist">
                        <tr>
                            <th class="px-4 py-3">#</th>
                            <th>Гравець</th>
                            <th>Ампл.</th>
                            <th>Г</th>
                            <th>А</th>
                            <th>Ж</th>
                            <th>Ч</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="player in players" :key="player.id" class="border-t border-line">
                            <td class="px-4 py-3 text-mist">{{ player.number }}</td>
                            <td>
                                <Link :href="route('players.show', player.id)">{{ player.name }}</Link>
                            </td>
                            <td class="text-mist">{{ player.position_short }}</td>
                            <td>{{ player.stats.goals }}</td>
                            <td>{{ player.stats.assists }}</td>
                            <td>{{ player.stats.yellows }}</td>
                            <td>{{ player.stats.reds }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="panel p-5">
                <h2 class="font-display text-3xl uppercase">Останні матчі</h2>
                <ul class="mt-3 divide-y divide-line text-sm">
                    <li v-for="match in recent" :key="match.id" class="py-3">
                        <Link :href="route('matches.show', match.id)">
                            {{ match.home_team?.name }} {{ match.home_score }}:{{ match.away_score }} {{ match.away_team?.name }}
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </PublicLayout>
</template>
