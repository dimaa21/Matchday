<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ teams: Object });

function destroy(id) {
    if (confirm('Видалити команду?')) router.delete(route('admin.teams.destroy', id));
}
</script>

<template>
    <Head title="Команди" />
    <AdminLayout>
        <div class="flex items-center justify-between">
            <h1 class="font-display text-5xl uppercase">Команди</h1>
            <Link :href="route('admin.teams.create')" class="rounded-full bg-pitch px-4 py-2 text-sm font-semibold text-ink">Нова</Link>
        </div>
        <div class="mt-6 grid gap-3">
            <article v-for="team in teams.data" :key="team.id" class="flex items-center justify-between rounded-2xl border border-line bg-panel px-4 py-3">
                <Link :href="route('teams.show', team.slug)" class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl font-display" :style="{ background: team.primary_color, color: team.secondary_color }">{{ team.initials }}</span>
                    <span>{{ team.name }} <span class="text-mist">· {{ team.city }} · {{ team.players_count }}</span></span>
                </Link>
                <span class="space-x-3 text-sm">
                    <Link :href="route('admin.teams.edit', team.id)">Змінити</Link>
                    <button type="button" class="text-flare" @click="destroy(team.id)">Видалити</button>
                </span>
            </article>
        </div>
    </AdminLayout>
</template>
