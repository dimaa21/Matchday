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
    if (confirm('Прибрати гравця зі складу?')) {
        router.delete(route('captain.players.destroy', id));
    }
}
</script>

<template>
    <Head title="Склад" />
    <AdminLayout>
        <div v-if="!team" class="rounded-3xl border border-line bg-panel p-6">
            <h1 class="font-display text-5xl uppercase">Склад ще не призначено</h1>
            <p class="mt-3 max-w-lg text-mist">Адміністратор має прив’язати ваш акаунт до команди. Після цього тут з’явиться заявка.</p>
        </div>
        <div v-else class="max-w-3xl">
            <p class="text-xs uppercase tracking-[0.2em] text-mist">{{ team.city }}</p>
            <h1 class="font-display text-5xl uppercase">{{ team.name }}</h1>
            <ul class="mt-6 space-y-3">
                <li v-for="player in players" :key="player.id" class="grid gap-2 rounded-2xl border border-line bg-panel p-3 sm:grid-cols-[80px_1fr_140px_auto]">
                    <input v-model.number="player.number" type="number" min="1" max="99" class="rounded-xl border-line bg-raised" />
                    <input v-model="player.name" class="rounded-xl border-line bg-raised" />
                    <select v-model="player.position" class="rounded-xl border-line bg-raised">
                        <option value="gk">Воротар</option>
                        <option value="df">Захисник</option>
                        <option value="mf">Півзахисник</option>
                        <option value="fw">Нападник</option>
                    </select>
                    <span class="flex gap-2">
                        <button type="button" class="rounded-xl bg-pitch px-3 text-sm font-semibold text-ink" @click="save(player)">Ок</button>
                        <button type="button" class="text-sm text-flare" @click="remove(player.id)">×</button>
                    </span>
                </li>
            </ul>
            <form class="mt-6 grid gap-2 rounded-3xl border border-dashed border-line p-4 sm:grid-cols-[1fr_90px_140px_auto]" @submit.prevent="add">
                <input v-model="form.name" placeholder="Новий гравець" class="rounded-xl border-line bg-raised" required />
                <input v-model.number="form.squad_number" type="number" min="1" max="99" class="rounded-xl border-line bg-raised" />
                <select v-model="form.position" class="rounded-xl border-line bg-raised">
                    <option value="gk">Воротар</option>
                    <option value="df">Захисник</option>
                    <option value="mf">Півзахисник</option>
                    <option value="fw">Нападник</option>
                </select>
                <button class="rounded-xl bg-pitch px-4 py-2 text-sm font-semibold text-ink">Додати</button>
            </form>
        </div>
    </AdminLayout>
</template>
