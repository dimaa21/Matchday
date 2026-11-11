<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ matches: Object });

function destroy(id) {
    if (confirm('Delete this match?')) router.delete(route('admin.matches.destroy', id));
}
</script>

<template>
    <Head title="Matches" />
    <AdminLayout>
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">Matches</h1>
            <Link :href="route('admin.matches.create')" class="btn">New</Link>
        </div>
        <div class="mt-4 bg-white">
            <article v-for="match in matches.data" :key="match.id" class="flex flex-wrap items-center justify-between gap-3 border-b border-[#eee] px-3 py-2 text-[13px]">
                <Link :href="route('matches.show', match.id)" class="hover:underline">
                    <span class="text-mist">Round {{ match.round }} · {{ match.status_label }}</span>
                    <p class="font-bold">{{ match.home_team?.name ?? 'Bye' }} {{ match.home_score }}:{{ match.away_score }} {{ match.away_team?.name }}</p>
                </Link>
                <span class="space-x-3">
                    <Link :href="route('referee.matches.show', match.id)" class="hover:underline">Officiate</Link>
                    <Link :href="route('admin.matches.edit', match.id)" class="hover:underline">Edit</Link>
                    <button type="button" class="text-live hover:underline" @click="destroy(match.id)">Delete</button>
                </span>
            </article>
        </div>
        <div class="mt-3 flex gap-3 text-[13px]">
            <Link v-if="matches.prev_page_url" :href="matches.prev_page_url" class="hover:underline">Previous</Link>
            <Link v-if="matches.next_page_url" :href="matches.next_page_url" class="hover:underline">Next</Link>
        </div>
    </AdminLayout>
</template>
