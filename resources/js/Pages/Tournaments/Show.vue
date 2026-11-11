<script setup>
import ScoreRow from '@/Components/ScoreRow.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    tournament: Object,
    tables: Array,
    fixtures: Array,
    scorers: Array,
});

const tab = ref('table');
const tabs = [
    ['table', 'Standings'],
    ['fixtures', 'Fixtures'],
    ['scorers', 'Top scorers'],
];

function pip(result) {
    if (result === 'W') return 'bg-[#00a83f] text-white';
    if (result === 'D') return 'bg-[#9aa3a8] text-white';
    return 'bg-live text-white';
}
</script>

<template>
    <Head :title="tournament.name" />

    <PublicLayout>
        <div class="bg-white">
            <div class="border-b border-[#eee] px-3 py-3">
                <p class="text-[11px] uppercase tracking-wide text-mist">{{ tournament.season }} · {{ tournament.format }}</p>
                <div class="mt-1 flex flex-wrap items-center justify-between gap-2">
                    <h1 class="text-[22px] font-bold">{{ tournament.name }}</h1>
                    <span class="text-[12px] text-mist">{{ tournament.status_label }} · {{ tournament.points_for_win }} pts for a win</span>
                </div>
            </div>
            <div class="flex text-[13px] font-bold">
                <button
                    v-for="item in tabs"
                    :key="item[0]"
                    type="button"
                    class="px-4 py-2"
                    :class="tab === item[0] ? 'border-b-[3px] border-live text-live' : 'text-mist'"
                    @click="tab = item[0]"
                >
                    {{ item[1] }}
                </button>
            </div>
        </div>

        <div v-if="tab === 'table'" class="mt-3 space-y-3">
            <section v-for="table in tables" :key="table.group || 'all'" class="overflow-x-auto bg-white">
                <h2 v-if="table.group" class="border-b border-[#eee] px-3 py-2 text-[13px] font-bold">Group {{ table.group }}</h2>
                <table class="w-full min-w-[720px] text-left text-[13px]">
                    <thead class="bg-[#f0f2f4] text-[11px] uppercase text-mist">
                        <tr>
                            <th class="px-2 py-2">#</th>
                            <th>Team</th>
                            <th class="px-2 text-center">MP</th>
                            <th class="px-2 text-center">W</th>
                            <th class="px-2 text-center">D</th>
                            <th class="px-2 text-center">L</th>
                            <th class="px-2 text-center">GF</th>
                            <th class="px-2 text-center">GA</th>
                            <th class="px-2 text-center">GD</th>
                            <th class="px-2 text-center">Pts</th>
                            <th class="px-2">Form</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in table.rows" :key="row.team_id" class="border-t border-[#eee]">
                            <td class="px-2 py-2 text-mist">{{ row.position }}</td>
                            <td class="py-2">
                                <Link :href="route('teams.show', row.team_slug)" class="inline-flex items-center gap-2 font-bold hover:underline">
                                    <span class="h-2.5 w-2.5 rounded-full" :style="{ background: row.primary_color }" />
                                    {{ row.team_name }}
                                </Link>
                            </td>
                            <td class="px-2 text-center">{{ row.played }}</td>
                            <td class="px-2 text-center">{{ row.won }}</td>
                            <td class="px-2 text-center">{{ row.drawn }}</td>
                            <td class="px-2 text-center">{{ row.lost }}</td>
                            <td class="px-2 text-center">{{ row.goals_for }}</td>
                            <td class="px-2 text-center">{{ row.goals_against }}</td>
                            <td class="px-2 text-center">{{ row.goal_difference }}</td>
                            <td class="px-2 text-center font-bold">{{ row.points }}</td>
                            <td class="px-2 py-2">
                                <span class="flex gap-1">
                                    <span
                                        v-for="(result, index) in row.form"
                                        :key="index"
                                        class="inline-flex h-4 w-4 items-center justify-center text-[10px] font-bold"
                                        :class="pip(result)"
                                    >
                                        {{ result }}
                                    </span>
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>

        <div v-else-if="tab === 'fixtures'" class="mt-3 space-y-3">
            <section v-for="round in fixtures" :key="`${round.group_name}-${round.round}`" class="overflow-hidden bg-white">
                <h2 class="bg-[#f0f2f4] px-3 py-2 text-[13px] font-bold">
                    Round {{ round.round }}
                    <span v-if="round.group_name" class="font-normal text-mist">· Group {{ round.group_name }}</span>
                </h2>
                <div v-for="match in round.matches" :key="match.id">
                    <p v-if="match.is_bye" class="border-b border-[#eee] px-3 py-2 text-[13px] text-mist">
                        {{ match.home_team?.name }} — bye
                    </p>
                    <ScoreRow v-else :match="match" />
                </div>
            </section>
        </div>

        <div v-else class="mt-3 overflow-x-auto bg-white">
            <table class="w-full min-w-[560px] text-left text-[13px]">
                <thead class="bg-[#f0f2f4] text-[11px] uppercase text-mist">
                    <tr>
                        <th class="px-3 py-2">#</th>
                        <th>Player</th>
                        <th>Team</th>
                        <th class="px-2 text-center">Goals</th>
                        <th class="px-2 text-center">Pen.</th>
                        <th class="px-2 text-center">Assists</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(scorer, index) in scorers" :key="scorer.player_id" class="border-t border-[#eee]">
                        <td class="px-3 py-2 text-mist">{{ index + 1 }}</td>
                        <td class="py-2">
                            <Link :href="route('players.show', scorer.player_id)" class="font-bold hover:underline">{{ scorer.player_name }}</Link>
                        </td>
                        <td>
                            <Link :href="route('teams.show', scorer.team_slug)" class="text-mist hover:underline">{{ scorer.team_name }}</Link>
                        </td>
                        <td class="px-2 text-center font-bold">{{ scorer.goals }}</td>
                        <td class="px-2 text-center">{{ scorer.penalties }}</td>
                        <td class="px-2 text-center">{{ scorer.assists }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PublicLayout>
</template>
