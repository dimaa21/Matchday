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
    <Head title="Матч" />
    <AdminLayout>
        <form class="max-w-2xl space-y-4" @submit.prevent="submit">
            <h1 class="font-display text-5xl uppercase">{{ match ? 'Матч' : 'Новий матч' }}</h1>
            <label class="block text-sm">Турнір
                <select v-model="form.tournament_id" class="mt-1 w-full rounded-xl border-line bg-raised">
                    <option v-for="tournament in tournaments" :key="tournament.id" :value="tournament.id">{{ tournament.name }}</option>
                </select>
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-sm">Тур<input v-model.number="form.round" type="number" min="1" class="mt-1 w-full rounded-xl border-line bg-raised" /></label>
                <label class="text-sm">Група<input v-model="form.group_name" class="mt-1 w-full rounded-xl border-line bg-raised" /></label>
                <label class="text-sm">Господарі
                    <select v-model="form.home_team_id" class="mt-1 w-full rounded-xl border-line bg-raised">
                        <option value="">—</option>
                        <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                    </select>
                </label>
                <label class="text-sm">Гості
                    <select v-model="form.away_team_id" class="mt-1 w-full rounded-xl border-line bg-raised">
                        <option value="">—</option>
                        <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                    </select>
                </label>
                <label class="text-sm">Суддя
                    <select v-model="form.referee_id" class="mt-1 w-full rounded-xl border-line bg-raised">
                        <option value="">—</option>
                        <option v-for="referee in referees" :key="referee.id" :value="referee.id">{{ referee.name }}</option>
                    </select>
                </label>
                <label class="text-sm">Статус
                    <select v-model="form.status" class="mt-1 w-full rounded-xl border-line bg-raised">
                        <option v-for="status in statuses" :key="status.value" :value="status.value">{{ status.label }}</option>
                    </select>
                </label>
                <label class="text-sm">Початок<input v-model="form.kickoff_at" type="datetime-local" class="mt-1 w-full rounded-xl border-line bg-raised" /></label>
                <label class="text-sm">Стадіон<input v-model="form.venue" class="mt-1 w-full rounded-xl border-line bg-raised" /></label>
                <label class="text-sm">Голи господарів<input v-model.number="form.home_score" type="number" min="0" class="mt-1 w-full rounded-xl border-line bg-raised" /></label>
                <label class="text-sm">Голи гостей<input v-model.number="form.away_score" type="number" min="0" class="mt-1 w-full rounded-xl border-line bg-raised" /></label>
            </div>
            <button class="rounded-full bg-pitch px-5 py-2 text-sm font-semibold text-ink">Зберегти</button>
            <Link :href="route('admin.matches.index')" class="ml-3 text-sm text-mist">Назад</Link>
        </form>
    </AdminLayout>
</template>
