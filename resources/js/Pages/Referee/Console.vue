<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    match: Object,
    players: Array,
    eventTypes: Array,
});

const type = ref(null);
const form = useForm({
    type: '',
    player_id: '',
    minute: props.match.events.at(-1)?.minute + 1 || 1,
    assist_player_id: '',
    secondary_player_id: '',
});

const grouped = computed(() => {
    return [
        { id: props.match.home_team?.id, name: props.match.home_team?.name },
        { id: props.match.away_team?.id, name: props.match.away_team?.name },
    ].filter((team) => team.id).map((team) => ({
        ...team,
        players: props.players.filter((player) => player.team_id === team.id),
    }));
});

function choose(value) {
    type.value = value;
    form.type = value;
    form.player_id = '';
    form.assist_player_id = '';
    form.secondary_player_id = '';
}

function save() {
    form.post(route('referee.matches.events.store', props.match.id), {
        preserveScroll: true,
        onSuccess: () => {
            form.minute = Math.min(120, Number(form.minute) + 1);
            type.value = null;
        },
    });
}

function remove(id) {
    router.delete(route('referee.matches.events.destroy', [props.match.id, id]), { preserveScroll: true });
}
</script>

<template>
    <Head title="Live-матч" />
    <AdminLayout>
        <div class="mx-auto max-w-3xl">
            <p class="text-xs uppercase tracking-[0.2em] text-mist">{{ match.tournament?.name }} · тур {{ match.round }}</p>
            <div class="mt-2 text-center">
                <p class="font-display text-3xl uppercase">{{ match.home_team?.name }}</p>
                <p class="font-display text-8xl text-pitch">{{ match.home_score }}:{{ match.away_score }}</p>
                <p class="font-display text-3xl uppercase">{{ match.away_team?.name }}</p>
                <p class="mt-2 text-sm" :class="match.status === 'live' ? 'text-flare' : 'text-mist'">{{ match.status_label }}</p>
            </div>

            <div class="mt-6 grid grid-cols-2 gap-3">
                <button
                    v-if="match.status === 'scheduled'"
                    type="button"
                    class="col-span-2 min-h-16 rounded-2xl bg-pitch text-lg font-semibold text-ink"
                    @click="router.post(route('referee.matches.start', match.id))"
                >
                    Почати матч
                </button>
                <button
                    v-if="match.status === 'live'"
                    type="button"
                    class="col-span-2 min-h-16 rounded-2xl bg-flare text-lg font-semibold text-white"
                    @click="router.post(route('referee.matches.finish', match.id))"
                >
                    Завершити матч
                </button>
            </div>

            <div v-if="match.status === 'live'" class="mt-4 grid grid-cols-2 gap-3">
                <button
                    v-for="item in eventTypes"
                    :key="item.value"
                    type="button"
                    class="min-h-16 rounded-2xl border text-lg font-semibold"
                    :class="type === item.value ? 'border-pitch bg-pitch text-ink' : 'border-line bg-panel'"
                    @click="choose(item.value)"
                >
                    {{ item.label }}
                </button>
            </div>

            <form v-if="type && match.status === 'live'" class="mt-4 space-y-4 rounded-3xl border border-line bg-panel p-4" @submit.prevent="save">
                <label class="block text-sm">Хвилина
                    <input v-model.number="form.minute" type="number" min="1" max="120" class="mt-1 w-full rounded-xl border-line bg-raised text-2xl" />
                </label>
                <div v-for="team in grouped" :key="team.id">
                    <p class="text-xs uppercase tracking-[0.16em] text-mist">{{ team.name }}</p>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <button
                            v-for="player in team.players"
                            :key="player.id"
                            type="button"
                            class="min-h-14 rounded-xl border px-2 text-left text-sm"
                            :disabled="player.suspended"
                            :class="form.player_id === player.id ? 'border-pitch bg-pitch text-ink' : 'border-line'"
                            @click="form.player_id = player.id"
                        >
                            {{ player.number }} {{ player.name }}
                            <span v-if="player.suspended" class="block text-[10px] uppercase text-flare">дискваліфікація</span>
                        </button>
                    </div>
                </div>
                <label v-if="type === 'goal' || type === 'penalty'" class="block text-sm">Асистент
                    <select v-model="form.assist_player_id" class="mt-1 w-full rounded-xl border-line bg-raised">
                        <option value="">Без асиста</option>
                        <option v-for="player in players.filter((item) => !item.suspended)" :key="player.id" :value="player.id">
                            {{ player.number }} {{ player.name }}
                        </option>
                    </select>
                </label>
                <label v-if="type === 'substitution'" class="block text-sm">Виходить на поле
                    <select v-model="form.secondary_player_id" class="mt-1 w-full rounded-xl border-line bg-raised">
                        <option value="">Оберіть гравця</option>
                        <option v-for="player in players.filter((item) => !item.suspended)" :key="player.id" :value="player.id">
                            {{ player.number }} {{ player.name }}
                        </option>
                    </select>
                </label>
                <p v-if="form.hasErrors" class="text-sm text-flare">{{ Object.values(form.errors)[0] }}</p>
                <button class="min-h-14 w-full rounded-2xl bg-pitch font-semibold text-ink" :disabled="form.processing">Додати подію</button>
            </form>

            <ul class="mt-6 space-y-2">
                <li v-for="event in [...match.events].reverse()" :key="event.id" class="flex items-center justify-between rounded-2xl border border-line px-4 py-3 text-sm">
                    <span>{{ event.minute }}' {{ event.label }} · {{ event.player }}</span>
                    <button v-if="match.status === 'live'" type="button" class="text-flare" @click="remove(event.id)">Скасувати</button>
                </li>
            </ul>
            <Link :href="route('matches.show', match.id)" class="mt-4 inline-block text-sm text-mist">Публічна сторінка</Link>
        </div>
    </AdminLayout>
</template>
