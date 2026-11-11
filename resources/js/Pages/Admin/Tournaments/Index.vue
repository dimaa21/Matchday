<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineProps({
    tournaments: Object,
});

const page = usePage();

function destroy(slug) {
    if (confirm('Видалити турнір разом із календарем?')) {
        router.delete(route('admin.tournaments.destroy', slug));
    }
}

function generate(slug) {
    router.post(route('admin.tournaments.fixtures', slug));
}
</script>

<template>
    <Head title="Турніри" />
    <AdminLayout>
        <div class="flex items-center justify-between">
            <h1 class="font-display text-5xl uppercase">Турніри</h1>
            <Link :href="route('admin.tournaments.create')" class="rounded-full bg-pitch px-4 py-2 text-sm font-semibold text-ink">Новий</Link>
        </div>
        <p v-if="page.props.errors?.teams" class="mt-4 text-sm text-flare">{{ page.props.errors.teams }}</p>
        <div class="mt-6 overflow-x-auto rounded-3xl border border-line">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="text-[11px] uppercase tracking-[0.16em] text-mist">
                    <tr>
                        <th class="px-4 py-3">Назва</th>
                        <th>Сезон</th>
                        <th>Формат</th>
                        <th>Статус</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="tournament in tournaments.data" :key="tournament.id" class="border-t border-line">
                        <td class="px-4 py-3">
                            <Link :href="route('tournaments.show', tournament.slug)" class="font-medium">{{ tournament.name }}</Link>
                            <p class="text-xs text-mist">{{ tournament.teams_count }} команд · {{ tournament.games_count }} матчів</p>
                        </td>
                        <td>{{ tournament.season }}</td>
                        <td>{{ tournament.format_label }}</td>
                        <td>{{ tournament.status_label }}</td>
                        <td class="space-x-3 px-4 text-right">
                            <button type="button" class="text-pitch" @click="generate(tournament.slug)">Календар</button>
                            <button
                                v-if="tournament.format === 'groups_playoff'"
                                type="button"
                                class="text-pitch"
                                @click="router.post(route('admin.tournaments.playoff', tournament.slug))"
                            >
                                Плей-оф
                            </button>
                            <Link :href="route('admin.tournaments.edit', tournament.slug)">Змінити</Link>
                            <button type="button" class="text-flare" @click="destroy(tournament.slug)">Видалити</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
