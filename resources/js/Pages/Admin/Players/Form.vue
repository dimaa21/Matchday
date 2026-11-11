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
    <Head title="Player" />
    <AdminLayout>
        <form class="max-w-xl space-y-4" @submit.prevent="submit">
            <h1 class="text-xl font-bold">{{ player ? player.name : 'New player' }}</h1>
            <label class="block text-[13px]">Team
                <select v-model="form.team_id" class="field">
                    <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                </select>
            </label>
            <label class="block text-[13px]">Name<input v-model="form.name" class="field" /></label>
            <div class="grid gap-4 sm:grid-cols-2">
                <label class="text-[13px]">Number<input v-model.number="form.squad_number" type="number" min="1" max="99" class="field" /></label>
                <label class="text-[13px]">Position
                    <select v-model="form.position" class="field">
                        <option value="gk">Goalkeeper</option>
                        <option value="df">Defender</option>
                        <option value="mf">Midfielder</option>
                        <option value="fw">Forward</option>
                    </select>
                </label>
            </div>
            <label class="block text-[13px]">Photo<input type="file" accept="image/*" class="mt-1 block text-sm" @input="form.photo = $event.target.files[0]" /></label>
            <button class="btn">Save</button>
            <Link :href="route('admin.players.index')" class="ml-3 text-[13px] text-mist">Back</Link>
        </form>
    </AdminLayout>
</template>
