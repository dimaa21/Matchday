<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    tournament: Object,
    teams: Array,
});

const form = useForm({
    name: props.tournament?.name ?? '',
    season: props.tournament?.season ?? '2026/27',
    format: props.tournament?.format ?? 'league',
    status: props.tournament?.status ?? 'draft',
    points_for_win: props.tournament?.points_for_win ?? 3,
    points_for_draw: props.tournament?.points_for_draw ?? 1,
    legs: props.tournament?.legs ?? 2,
    teams: props.tournament?.teams ?? [],
});

const selected = computed(() => new Set(form.teams.map((team) => team.id)));

function toggle(id) {
    if (selected.value.has(id)) {
        form.teams = form.teams.filter((team) => team.id !== id);
        return;
    }

    form.teams.push({ id, group_name: '' });
}

function groupOf(id) {
    return form.teams.find((team) => team.id === id)?.group_name ?? '';
}

function setGroup(id, value) {
    const team = form.teams.find((item) => item.id === id);
    if (team) team.group_name = value;
}

function submit() {
    if (props.tournament) {
        form.put(route('admin.tournaments.update', props.tournament.slug));
        return;
    }

    form.post(route('admin.tournaments.store'));
}
</script>

<template>
    <Head :title="tournament ? 'Tournament' : 'New tournament'" />
    <AdminLayout>
        <form class="max-w-3xl space-y-4" @submit.prevent="submit">
            <h1 class="text-xl font-bold">{{ tournament ? tournament.name : 'New tournament' }}</h1>
            <label class="block text-[13px]">Name
                <input v-model="form.name" class="field" />
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-[13px]">Season
                    <input v-model="form.season" class="field" />
                </label>
                <label class="text-[13px]">Format
                    <select v-model="form.format" class="field">
                        <option value="league">League</option>
                        <option value="cup">Cup</option>
                        <option value="groups_playoff">Groups + playoff</option>
                    </select>
                </label>
                <label class="text-[13px]">Status
                    <select v-model="form.status" class="field">
                        <option value="draft">Draft</option>
                        <option value="active">In progress</option>
                        <option value="finished">Finished</option>
                    </select>
                </label>
                <label class="text-[13px]">Legs
                    <select v-model="form.legs" class="field">
                        <option :value="1">One</option>
                        <option :value="2">Two</option>
                    </select>
                </label>
                <label class="text-[13px]">Points for a win
                    <input v-model.number="form.points_for_win" type="number" min="0" max="10" class="field" />
                </label>
                <label class="text-[13px]">Points for a draw
                    <input v-model.number="form.points_for_draw" type="number" min="0" max="10" class="field" />
                </label>
            </div>
            <fieldset class="bg-white p-4">
                <legend class="px-1 text-[13px] font-bold">Teams</legend>
                <label v-for="team in teams" :key="team.id" class="flex items-center gap-3 py-2 text-[13px]">
                    <input type="checkbox" :checked="selected.has(team.id)" @change="toggle(team.id)" />
                    <span class="flex-1">{{ team.name }} <span class="text-mist">{{ team.city }}</span></span>
                    <input
                        v-if="form.format === 'groups_playoff' && selected.has(team.id)"
                        :value="groupOf(team.id)"
                        placeholder="A"
                        class="w-16 rounded border-gray-300 text-center text-sm"
                        @input="setGroup(team.id, $event.target.value)"
                    />
                </label>
            </fieldset>
            <p v-if="form.hasErrors" class="text-[13px] text-live">Check the form fields.</p>
            <div class="flex gap-3">
                <button class="btn" :disabled="form.processing">Save</button>
                <Link :href="route('admin.tournaments.index')" class="px-3 py-2 text-[13px] text-mist">Back</Link>
            </div>
        </form>
    </AdminLayout>
</template>
