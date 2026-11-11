<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    match: Object,
    tournaments: Array,
    teams: Array,
    referees: Array,
    statuses: Array,
});

const form = useForm({
    tournament_id: props.match?.tournament_id ?? props.tournaments[0]?.id ?? '',
    round: props.match?.round ?? 1,
    group_name: props.match?.group_name ?? '',
    home_team_id: props.match?.home_team_id ?? '',
    away_team_id: props.match?.away_team_id ?? '',
    referee_id: props.match?.referee_id ?? '',
    kickoff_at: props.match?.kickoff_at ?? '',
    venue: props.match?.venue ?? '',
    status: props.match?.status ?? 'scheduled',
    home_score: props.match?.home_score ?? 0,
    away_score: props.match?.away_score ?? 0,
    is_bye: props.match?.is_bye ?? false,
});

function submit() {
    if (props.match) {
        form.put(route('admin.matches.update', props.match.id));
        return;
    }

    form.post(route('admin.matches.store'));
}
</script>

<template>
    <Head title="Match" />
    <AdminLayout>
        <form class="max-w-2xl space-y-4" @submit.prevent="submit">
            <h1 class="text-xl font-bold">{{ match ? 'Match' : 'New match' }}</h1>
            <label class="block text-[13px]">Tournament
                <select v-model="form.tournament_id" class="field">
                    <option v-for="tournament in tournaments" :key="tournament.id" :value="tournament.id">{{ tournament.name }}</option>
                </select>
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-[13px]">Round<input v-model.number="form.round" type="number" min="1" class="field" /></label>
                <label class="text-[13px]">Group<input v-model="form.group_name" class="field" /></label>
                <label class="text-[13px]">Home
                    <select v-model="form.home_team_id" class="field">
                        <option value="">—</option>
                        <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                    </select>
                </label>
                <label class="text-[13px]">Away
                    <select v-model="form.away_team_id" class="field">
                        <option value="">—</option>
                        <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                    </select>
                </label>
                <label class="text-[13px]">Referee
                    <select v-model="form.referee_id" class="field">
                        <option value="">—</option>
                        <option v-for="referee in referees" :key="referee.id" :value="referee.id">{{ referee.name }}</option>
                    </select>
                </label>
                <label class="text-[13px]">Status
                    <select v-model="form.status" class="field">
                        <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </select>
                </label>
                <label class="text-[13px]">Kickoff<input v-model="form.kickoff_at" type="datetime-local" class="field" /></label>
                <label class="text-[13px]">Venue<input v-model="form.venue" class="field" /></label>
                <label class="text-[13px]">Home goals<input v-model.number="form.home_score" type="number" min="0" class="field" /></label>
                <label class="text-[13px]">Away goals<input v-model.number="form.away_score" type="number" min="0" class="field" /></label>
            </div>
            <button class="btn">Save</button>
            <Link :href="route('admin.matches.index')" class="ml-3 text-[13px] text-mist">Back</Link>
        </form>
    </AdminLayout>
</template>
