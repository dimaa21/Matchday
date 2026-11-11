<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    player: Object,
    teams: Array,
});

const form = useForm({
    team_id: props.player?.team_id ?? props.teams[0]?.id ?? '',
    name: props.player?.name ?? '',
    squad_number: props.player?.squad_number ?? 1,
    position: props.player?.position ?? 'mf',
    photo: null,
});

function submit() {
    const options = { forceFormData: true };

    if (props.player) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.players.update', props.player.id), options);
        return;
    }

    form.post(route('admin.players.store'), options);
}
</script>

<template>
    <Head title="Гравець" />
    <AdminLayout>
        <form class="max-w-xl space-y-4" @submit.prevent="submit">
            <h1 class="font-display text-5xl uppercase">{{ player ? player.name : 'Новий гравець' }}</h1>
            <label class="block text-sm">Команда
                <select v-model="form.team_id" class="mt-1 w-full rounded-xl border-line bg-raised">
                    <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                </select>
            </label>
            <label class="block text-sm">Ім'я<input v-model="form.name" class="mt-1 w-full rounded-xl border-line bg-raised" /></label>
            <div class="grid grid-cols-2 gap-4">
                <label class="text-sm">Номер<input v-model.number="form.squad_number" type="number" min="1" max="99" class="mt-1 w-full rounded-xl border-line bg-raised" /></label>
                <label class="text-sm">Позиція
                    <select v-model="form.position" class="mt-1 w-full rounded-xl border-line bg-raised">
                        <option value="gk">Воротар</option>
                        <option value="df">Захисник</option>
                        <option value="mf">Півзахисник</option>
                        <option value="fw">Нападник</option>
                    </select>
                </label>
            </div>
            <label class="block text-sm">Фото<input type="file" accept="image/*" class="mt-1 block" @input="form.photo = $event.target.files[0]" /></label>
            <button class="rounded-full bg-pitch px-5 py-2 text-sm font-semibold text-ink">Зберегти</button>
            <Link :href="route('admin.players.index')" class="ml-3 text-sm text-mist">Назад</Link>
        </form>
    </AdminLayout>
</template>
