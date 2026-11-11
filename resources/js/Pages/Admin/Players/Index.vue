<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ players: Object });

function destroy(id) {
    if (confirm('Видалити гравця?')) router.delete(route('admin.players.destroy', id));
}
</script>

<template>
    <Head title="Гравці" />
    <AdminLayout>
        <div class="flex items-center justify-between">
            <h1 class="font-display text-5xl uppercase">Гравці</h1>
            <Link :href="route('admin.players.create')" class="rounded-full bg-pitch px-4 py-2 text-sm font-semibold text-ink">Новий</Link>
        </div>
        <div class="mt-6 overflow-x-auto rounded-3xl border border-line">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="text-[11px] uppercase tracking-[0.16em] text-mist">
                    <tr><th class="px-4 py-3">Гравець</th><th>Команда</th><th></th></tr>
                </thead>
                <tbody>
                    <tr v-for="player in players.data" :key="player.id" class="border-t border-line">
                        <td class="px-4 py-3">
                            <Link :href="route('players.show', player.id)">{{ player.number }} {{ player.name }}</Link>
                            <span class="ml-2 text-mist">{{ player.position }}</span>
                        </td>
                        <td>{{ player.team }}</td>
                        <td class="space-x-3 px-4 text-right">
                            <Link :href="route('admin.players.edit', player.id)">Змінити</Link>
                            <button type="button" class="text-flare" @click="destroy(player.id)">Видалити</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-4 flex gap-3 text-sm">
            <Link v-if="players.prev_page_url" :href="players.prev_page_url">Назад</Link>
            <Link v-if="players.next_page_url" :href="players.next_page_url">Далі</Link>
        </div>
    </AdminLayout>
</template>
