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
    <Head title="Live match" />
    <AdminLayout>
        <div class="mx-auto max-w-3xl">
            <p class="text-[12px] text-mist">{{ match.tournament?.name }} · round {{ match.round }}</p>
            <div class="mt-2 bg-white py-4 text-center">
                <p class="text-[16px] font-bold">{{ match.home_team?.name }}</p>
                <p class="text-[48px] font-bold leading-none" :class="match.status === 'live' ? 'text-live' : ''">{{ match.home_score }}:{{ match.away_score }}</p>
                <p class="text-[16px] font-bold">{{ match.away_team?.name }}</p>
                <p class="mt-2 text-[13px]" :class="match.status === 'live' ? 'font-bold text-live' : 'text-mist'">{{ match.status_label }}</p>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-3">
                <button
                    v-if="match.status === 'scheduled'"
                    type="button"
                    class="col-span-2 min-h-14 bg-navy text-[16px] font-bold text-white"
                    @click="router.post(route('referee.matches.start', match.id))"
                >
                    Start match
                </button>
                <button
                    v-if="match.status === 'live'"
                    type="button"
                    class="col-span-2 min-h-14 bg-live text-[16px] font-bold text-white"
                    @click="router.post(route('referee.matches.finish', match.id))"
                >
                    Finish match
                </button>
            </div>

            <div v-if="match.status === 'live'" class="mt-3 grid grid-cols-2 gap-2">
                <button
                    v-for="item in eventTypes"
                    :key="item.value"
                    type="button"
                    class="min-h-14 border text-[15px] font-bold"
                    :class="type === item.value ? 'border-live bg-live text-white' : 'border-[#ddd] bg-white'"
                    @click="choose(item.value)"
                >
                    {{ item.label }}
                </button>
            </div>

            <form v-if="type && match.status === 'live'" class="mt-3 space-y-4 bg-white p-4" @submit.prevent="save">
                <label class="block text-[13px]">Minute
                    <input v-model.number="form.minute" type="number" min="1" max="120" class="field text-2xl" />
                </label>
                <div v-for="team in grouped" :key="team.id">
                    <p class="text-[12px] font-bold uppercase text-mist">{{ team.name }}</p>
                    <div class="mt-2 grid grid-cols-2 gap-2">
                        <button
                            v-for="player in team.players"
                            :key="player.id"
                            type="button"
                            class="min-h-12 border px-2 text-left text-[13px]"
                            :disabled="player.suspended"
                            :class="form.player_id === player.id ? 'border-live bg-live text-white' : 'border-[#ddd] bg-white'"
                            @click="form.player_id = player.id"
                        >
                            {{ player.number }} {{ player.name }}
                            <span v-if="player.suspended" class="block text-[10px] uppercase">suspended</span>
                        </button>
                    </div>
                </div>
                <label v-if="type === 'goal' || type === 'penalty'" class="block text-[13px]">Assist
                    <select v-model="form.assist_player_id" class="field">
                        <option value="">No assist</option>
                        <option v-for="player in players.filter((item) => !item.suspended)" :key="player.id" :value="player.id">
                            {{ player.number }} {{ player.name }}
                        </option>
                    </select>
                </label>
                <label v-if="type === 'substitution'" class="block text-[13px]">Player coming on
                    <select v-model="form.secondary_player_id" class="field">
                        <option value="">Choose a player</option>
                        <option v-for="player in players.filter((item) => !item.suspended)" :key="player.id" :value="player.id">
                            {{ player.number }} {{ player.name }}
                        </option>
                    </select>
                </label>
                <p v-if="form.hasErrors" class="text-[13px] text-live">{{ Object.values(form.errors)[0] }}</p>
                <button class="min-h-12 w-full bg-navy font-bold text-white" :disabled="form.processing">Add event</button>
            </form>

            <ul class="mt-4 bg-white">
                <li v-for="event in [...match.events].reverse()" :key="event.id" class="flex items-center justify-between border-b border-[#eee] px-3 py-2 text-[13px]">
                    <span>{{ event.minute }}' {{ event.label }} · {{ event.player }}</span>
                    <button v-if="match.status === 'live'" type="button" class="text-live" @click="remove(event.id)">Undo</button>
                </li>
            </ul>
            <Link :href="route('matches.show', match.id)" class="mt-3 inline-block text-[13px] text-mist hover:underline">Public page</Link>
        </div>
    </AdminLayout>
</template>
