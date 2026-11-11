<script setup>
import ScoreRow from '@/Components/ScoreRow.vue';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    sections: Array,
    tournaments: Array,
});

const filter = ref('all');
const filters = [
    ['all', 'All'],
    ['live', 'Live'],
    ['finished', 'Finished'],
    ['scheduled', 'Scheduled'],
];

const board = computed(() => props.sections.map((section) => ({
    ...section,
    rounds: section.rounds
        .map((round) => ({
            ...round,
            matches: round.matches.filter((match) => filter.value === 'all' || match.status === filter.value),
        }))
        .filter((round) => round.matches.length > 0),
})).filter((section) => section.rounds.length > 0));
</script>

<template>
    <Head title="Scores" />

    <PublicLayout>
        <div class="mb-2 flex overflow-hidden bg-white text-[13px] font-bold">
            <button
                v-for="[value, label] in filters"
                :key="value"
                type="button"
                class="px-4 py-2"
                :class="filter === value ? 'border-b-[3px] border-live text-live' : 'text-mist'"
                @click="filter = value"
            >
                {{ label }}
            </button>
        </div>

        <div v-if="board.length === 0" class="bg-white px-4 py-8 text-center text-[13px] text-mist">
            No matches for this filter.
        </div>

        <section v-for="section in board" :key="section.slug" class="mb-3 overflow-hidden bg-white">
            <div class="flex items-center justify-between bg-[#f0f2f4] px-3 py-2">
                <Link :href="route('tournaments.show', section.slug)" class="text-[13px] font-bold hover:underline">
                    {{ section.name }}
                </Link>
                <Link :href="route('tournaments.show', section.slug)" class="text-[12px] text-mist hover:underline">
                    Standings
                </Link>
            </div>
            <div v-for="round in section.rounds" :key="round.round">
                <p class="border-b border-[#eee] bg-[#fafbfb] px-3 py-1 text-[11px] font-bold uppercase tracking-wide text-mist">
                    Round {{ round.round }}
                </p>
                <ScoreRow v-for="match in round.matches" :key="match.id" :match="match" />
            </div>
        </section>

        <section v-if="tournaments.length" class="bg-white">
            <p class="bg-[#f0f2f4] px-3 py-2 text-[13px] font-bold">All competitions</p>
            <Link
                v-for="tournament in tournaments"
                :key="tournament.slug"
                :href="route('tournaments.show', tournament.slug)"
                class="flex items-center justify-between border-t border-[#eee] px-3 py-2 text-[13px] hover:bg-[#f7f9fa]"
            >
                <span class="font-bold">{{ tournament.name }}</span>
                <span class="text-mist">{{ tournament.teams_count }} teams · {{ tournament.status_label }}</span>
            </Link>
        </section>
    </PublicLayout>
</template>
