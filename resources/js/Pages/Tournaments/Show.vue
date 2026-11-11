<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    tournament: Object,
    tables: Array,
    fixtures: Array,
    scorers: Array,
});

const tab = ref('table');

const rounds = computed(() => props.fixtures);

function pip(result) {
    if (result === 'W') return 'bg-pitch text-ink';
    if (result === 'D') return 'bg-raised text-snow';
    return 'bg-flare text-white';
}
</script>

<template>
    <Head :title="tournament.name" />

    <PublicLayout>
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs uppercase tracking-[0.22em] text-pitch">{{ tournament.season }} · {{ tournament.format }}</p>
                <h1 class="font-display text-5xl uppercase sm:text-7xl">{{ tournament.name }}</h1>
            </div>
            <span class="rounded-full border border-line px-3 py-1 text-xs uppercase tracking-[0.16em] text-mist">
                {{ tournament.status_label }} · {{ tournament.points_for_win }}/{{ tournament.points_for_draw }}
            </span>
        </div>

        <div class="mt-6 flex gap-2">
            <button
                v-for="item in [['table', 'Таблиця'], ['fixtures', 'Календар'], ['scorers', 'Бомбардири']]"
                :key="item[0]"
                type="button"
                class="rounded-full px-4 py-2 text-sm"
                :class="tab === item[0] ? 'bg-pitch font-semibold text-ink' : 'bg-raised text-mist'"
                @click="tab = item[0]"
            >
                {{ item[1] }}
            </button>
        </div>

        <div v-if="tab === 'table'" class="mt-5 space-y-6">
            <section v-for="table in tables" :key="table.group || 'all'" class="panel overflow-x-auto">
                <h2 v-if="table.group" class="px-4 pt-4 font-display text-2xl uppercase">Група {{ table.group }}</h2>
                <table class="w-full min-w-[760px] text-left">
                    <thead class="text-[11px] uppercase tracking-[0.16em] text-mist">
                        <tr>
                            <th class="px-3 py-3">#</th>
                            <th>Команда</th>
                            <th>І</th>
                            <th>В</th>
                            <th>Н</th>
                            <th>П</th>
                            <th>З</th>
                            <th>Пр</th>
                            <th>Р</th>
                            <th>О</th>
                            <th>Форма</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="row in table.rows" :key="row.team_id" class="border-t border-line">
                            <td class="px-3 py-3 font-display text-xl text-mist">{{ row.position }}</td>
                            <td>
                                <Link :href="route('teams.show', row.team_slug)" class="inline-flex items-center gap-2 font-medium">
                                    <span class="h-3 w-3 rounded-full" :style="{ background: row.primary_color }" />
                                    {{ row.team_name }}
                                </Link>
                            </td>
                            <td>{{ row.played }}</td>
                            <td>{{ row.won }}</td>
                            <td>{{ row.drawn }}</td>
                            <td>{{ row.lost }}</td>
                            <td>{{ row.goals_for }}</td>
                            <td>{{ row.goals_against }}</td>
                            <td>{{ row.goal_difference }}</td>
                            <td class="font-display text-2xl text-pitch">{{ row.points }}</td>
                            <td>
                                <span class="flex gap-1">
                                    <span
                                        v-for="(result, index) in row.form"
                                        :key="index"
                                        class="inline-flex h-5 w-5 items-center justify-center rounded text-[10px] font-bold"
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

        <div v-else-if="tab === 'fixtures'" class="mt-5 space-y-5">
            <section v-for="round in rounds" :key="`${round.group_name}-${round.round}`" class="panel p-4">
                <h2 class="font-display text-2xl uppercase">
                    Тур {{ round.round }}
                    <span v-if="round.group_name" class="text-mist">· група {{ round.group_name }}</span>
                </h2>
                <div class="mt-3 divide-y divide-line">
                    <Link
                        v-for="match in round.matches"
                        :key="match.id"
                        :href="route('matches.show', match.id)"
                        class="flex items-center justify-between gap-3 py-3"
                    >
                        <div class="min-w-0">
                            <p v-if="match.is_bye" class="text-mist">{{ match.home_team?.name }} — вихідний</p>
                            <p v-else class="truncate">
                                {{ match.home_team?.name }}
                                <span class="font-display text-2xl text-pitch">
                                    {{ match.status === 'scheduled' ? '–' : match.home_score }}:{{ match.status === 'scheduled' ? '–' : match.away_score }}
                                </span>
                                {{ match.away_team?.name }}
                            </p>
                            <p class="text-xs text-mist">{{ match.kickoff_label }} · {{ match.venue }}</p>
                        </div>
                        <span
                            class="rounded-full px-2 py-1 text-[10px] uppercase tracking-[0.14em]"
                            :class="match.status === 'live' ? 'bg-flare text-white' : 'text-mist'"
                        >
                            {{ match.status_label }}
                        </span>
                    </Link>
                </div>
            </section>
        </div>

        <div v-else class="panel mt-5 overflow-x-auto">
            <table class="w-full min-w-[640px] text-left">
                <thead class="text-[11px] uppercase tracking-[0.16em] text-mist">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th>Гравець</th>
                        <th>Команда</th>
                        <th>Голи</th>
                        <th>Пен.</th>
                        <th>Асисти</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(scorer, index) in scorers" :key="scorer.player_id" class="border-t border-line">
                        <td class="px-4 py-3 text-mist">{{ index + 1 }}</td>
                        <td>
                            <Link :href="route('players.show', scorer.player_id)">{{ scorer.player_name }}</Link>
                        </td>
                        <td>
                            <Link :href="route('teams.show', scorer.team_slug)" class="text-mist">{{ scorer.team_name }}</Link>
                        </td>
                        <td class="font-display text-2xl text-pitch">{{ scorer.goals }}</td>
                        <td>{{ scorer.penalties }}</td>
                        <td>{{ scorer.assists }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </PublicLayout>
</template>
