<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ matches: Object });

function destroy(id) {
    if (confirm('Видалити матч?')) router.delete(route('admin.matches.destroy', id));
}
</script>

<template>
    <Head title="Матчі" />
    <AdminLayout>
        <div class="flex items-center justify-between">
            <h1 class="font-display text-5xl uppercase">Матчі</h1>
            <Link :href="route('admin.matches.create')" class="rounded-full bg-pitch px-4 py-2 text-sm font-semibold text-ink">Новий</Link>
        </div>
        <div class="mt-6 space-y-2">
            <article v-for="match in matches.data" :key="match.id" class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-line bg-panel px-4 py-3 text-sm">
                <Link :href="route('matches.show', match.id)">
                    <span class="text-mist">Тур {{ match.round }} · {{ match.status_label }}</span>
                    <p>{{ match.home_team?.name ?? 'Вихідний' }} {{ match.home_score }}:{{ match.away_score }} {{ match.away_team?.name }}</p>
                </Link>
                <span class="space-x-3">
                    <Link :href="route('referee.matches.show', match.id)">Вести</Link>
                    <Link :href="route('admin.matches.edit', match.id)">Змінити</Link>
                    <button type="button" class="text-flare" @click="destroy(match.id)">Видалити</button>
                </span>
            </article>
        </div>
        <div class="mt-4 flex gap-3 text-sm">
            <Link v-if="matches.prev_page_url" :href="matches.prev_page_url">Назад</Link>
            <Link v-if="matches.next_page_url" :href="matches.next_page_url">Далі</Link>
        </div>
    </AdminLayout>
</template>
