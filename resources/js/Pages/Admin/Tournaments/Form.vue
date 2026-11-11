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
    <Head :title="tournament ? 'Турнір' : 'Новий турнір'" />
    <AdminLayout>
        <form class="max-w-3xl space-y-4" @submit.prevent="submit">
            <h1 class="font-display text-5xl uppercase">{{ tournament ? tournament.name : 'Новий турнір' }}</h1>
            <label class="block text-sm">Назва
                <input v-model="form.name" class="mt-1 w-full rounded-xl border-line bg-raised" />
            </label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-sm">Сезон
                    <input v-model="form.season" class="mt-1 w-full rounded-xl border-line bg-raised" />
                </label>
                <label class="text-sm">Формат
                    <select v-model="form.format" class="mt-1 w-full rounded-xl border-line bg-raised">
                        <option value="league">Ліга</option>
                        <option value="cup">Кубок</option>
                        <option value="groups_playoff">Групи + плей-оф</option>
                    </select>
                </label>
                <label class="text-sm">Статус
                    <select v-model="form.status" class="mt-1 w-full rounded-xl border-line bg-raised">
                        <option value="draft">Чернетка</option>
                        <option value="active">Триває</option>
                        <option value="finished">Завершено</option>
                    </select>
                </label>
                <label class="text-sm">Кола
                    <select v-model="form.legs" class="mt-1 w-full rounded-xl border-line bg-raised">
                        <option :value="1">Одне</option>
                        <option :value="2">Два</option>
                    </select>
                </label>
                <label class="text-sm">Очки за перемогу
                    <input v-model.number="form.points_for_win" type="number" min="0" max="10" class="mt-1 w-full rounded-xl border-line bg-raised" />
                </label>
                <label class="text-sm">Очки за нічию
                    <input v-model.number="form.points_for_draw" type="number" min="0" max="10" class="mt-1 w-full rounded-xl border-line bg-raised" />
                </label>
            </div>
            <fieldset class="rounded-3xl border border-line p-4">
                <legend class="px-2 text-sm text-mist">Команди</legend>
                <label v-for="team in teams" :key="team.id" class="flex items-center gap-3 py-2 text-sm">
                    <input type="checkbox" :checked="selected.has(team.id)" @change="toggle(team.id)" />
                    <span class="flex-1">{{ team.name }} <span class="text-mist">{{ team.city }}</span></span>
                    <input
                        v-if="form.format === 'groups_playoff' && selected.has(team.id)"
                        :value="groupOf(team.id)"
                        placeholder="A"
                        class="w-16 rounded-lg border-line bg-raised text-center"
                        @input="setGroup(team.id, $event.target.value)"
                    />
                </label>
            </fieldset>
            <p v-if="form.hasErrors" class="text-sm text-flare">Перевірте поля форми.</p>
            <div class="flex gap-3">
                <button class="rounded-full bg-pitch px-5 py-2 text-sm font-semibold text-ink" :disabled="form.processing">Зберегти</button>
                <Link :href="route('admin.tournaments.index')" class="px-3 py-2 text-sm text-mist">Назад</Link>
            </div>
        </form>
    </AdminLayout>
</template>
