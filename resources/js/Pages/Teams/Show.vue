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
        <section class="bg-white">
            <div class="h-1" :style="{ background: team.primary_color }" />
            <div class="flex flex-wrap items-end justify-between gap-4 px-3 py-4">
                <div>
                    <p class="text-[11px] uppercase tracking-wide text-mist">{{ team.city }}</p>
                    <h1 class="text-[22px] font-bold">{{ team.name }}</h1>
                </div>
                <dl class="grid grid-cols-3 gap-4 text-center text-[13px]">
                    <div>
                        <dt class="text-[11px] uppercase text-mist">MP</dt>
                        <dd class="text-[18px] font-bold">{{ team.stats.played }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase text-mist">W-D-L</dt>
                        <dd class="text-[18px] font-bold">{{ team.stats.won }}-{{ team.stats.drawn }}-{{ team.stats.lost }}</dd>
                    </div>
                    <div>
                        <dt class="text-[11px] uppercase text-mist">Goals</dt>
                        <dd class="text-[18px] font-bold">{{ team.stats.goals_for }}:{{ team.stats.goals_against }}</dd>
                    </div>
                </dl>
            </div>
        </section>

        <div class="mt-3 grid gap-3 lg:grid-cols-[1.3fr_0.7fr]">
            <section class="overflow-x-auto bg-white">
                <table class="w-full min-w-[520px] text-left text-[13px]">
                    <thead class="bg-[#f0f2f4] text-[11px] uppercase text-mist">
                        <tr>
                            <th class="px-3 py-2">#</th>
                            <th>Player</th>
                            <th>Pos</th>
                            <th class="px-2 text-center">G</th>
                            <th class="px-2 text-center">A</th>
                            <th class="px-2 text-center">YC</th>
                            <th class="px-2 text-center">RC</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="player in players" :key="player.id" class="border-t border-[#eee]">
                            <td class="px-3 py-2 text-mist">{{ player.number }}</td>
                            <td>
                                <Link :href="route('players.show', player.id)" class="font-bold hover:underline">{{ player.name }}</Link>
                            </td>
                            <td class="text-mist">{{ player.position_short }}</td>
                            <td class="px-2 text-center">{{ player.stats.goals }}</td>
                            <td class="px-2 text-center">{{ player.stats.assists }}</td>
                            <td class="px-2 text-center">{{ player.stats.yellows }}</td>
                            <td class="px-2 text-center">{{ player.stats.reds }}</td>
                        </tr>
                    </tbody>
                </table>
            </section>

            <section class="bg-white">
                <h2 class="border-b border-[#eee] bg-[#f0f2f4] px-3 py-2 text-[13px] font-bold">Recent matches</h2>
                <ul>
                    <li v-for="match in recent" :key="match.id" class="border-b border-[#eee] px-3 py-2 text-[13px]">
                        <Link :href="route('matches.show', match.id)" class="hover:underline">
                            {{ match.home_team?.name }} {{ match.home_score }}:{{ match.away_score }} {{ match.away_team?.name }}
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </PublicLayout>
</template>
