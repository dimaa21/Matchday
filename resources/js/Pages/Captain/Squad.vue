<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    team: Object,
    players: Array,
});

const form = useForm({
    name: '',
    squad_number: (props.players.at(-1)?.number ?? 0) + 1,
    position: 'mf',
});

function add() {
    form.post(route('captain.players.store'), {
        onSuccess: () => form.reset('name'),
    });
}

function save(player) {
    router.patch(route('captain.players.update', player.id), {
        name: player.name,
        squad_number: player.number,
        position: player.position,
    }, { preserveScroll: true });
}

function remove(id) {
    if (confirm('Remove this player from the squad?')) {
        router.delete(route('captain.players.destroy', id));
    }
}
</script>

<template>
    <Head title="Squad" />
    <AdminLayout>
        <div v-if="!team" class="bg-white p-5">
            <h1 class="text-xl font-bold">No squad assigned</h1>
            <p class="mt-2 max-w-lg text-[13px] text-mist">An administrator needs to attach your account to a team. The squad will show up here after that.</p>
        </div>
        <div v-else class="max-w-3xl">
            <p class="text-[12px] text-mist">{{ team.city }}</p>
            <h1 class="text-xl font-bold">{{ team.name }}</h1>
            <ul class="mt-4 space-y-2">
                <li v-for="player in players" :key="player.id" class="grid gap-2 bg-white p-3 sm:grid-cols-[80px_1fr_150px_auto]">
                    <input v-model.number="player.number" type="number" min="1" max="99" class="rounded border-gray-300 text-sm" />
                    <input v-model="player.name" class="rounded border-gray-300 text-sm" />
                    <select v-model="player.position" class="rounded border-gray-300 text-sm">
                        <option value="gk">Goalkeeper</option>
                        <option value="df">Defender</option>
                        <option value="mf">Midfielder</option>
                        <option value="fw">Forward</option>
                    </select>
                    <span class="flex gap-2">
                        <button type="button" class="btn" @click="save(player)">Save</button>
                        <button type="button" class="text-[13px] text-live" @click="remove(player.id)">Remove</button>
                    </span>
                </li>
            </ul>
            <form class="mt-4 grid gap-2 bg-white p-3 sm:grid-cols-[1fr_90px_150px_auto]" @submit.prevent="add">
                <input v-model="form.name" placeholder="New player" class="rounded border-gray-300 text-sm" required />
                <input v-model.number="form.squad_number" type="number" min="1" max="99" class="rounded border-gray-300 text-sm" />
                <select v-model="form.position" class="rounded border-gray-300 text-sm">
                    <option value="gk">Goalkeeper</option>
                    <option value="df">Defender</option>
                    <option value="mf">Midfielder</option>
                    <option value="fw">Forward</option>
                </select>
                <button class="btn">Add</button>
            </form>
        </div>
    </AdminLayout>
</template>
