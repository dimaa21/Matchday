<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';

defineProps({
    tournaments: Object,
});

const page = usePage();

function destroy(slug) {
    if (confirm('Delete this tournament and its calendar?')) {
        router.delete(route('admin.tournaments.destroy', slug));
    }
}

function generate(slug) {
    router.post(route('admin.tournaments.fixtures', slug));
}
</script>

<template>
    <Head title="Tournaments" />
    <AdminLayout>
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">Tournaments</h1>
            <Link :href="route('admin.tournaments.create')" class="btn">New</Link>
        </div>
        <p v-if="page.props.errors?.teams" class="mt-3 text-[13px] text-live">{{ page.props.errors.teams }}</p>
        <div class="mt-4 overflow-x-auto bg-white">
            <table class="w-full min-w-[720px] text-left text-[13px]">
                <thead class="bg-[#f0f2f4] text-[11px] uppercase text-mist">
                    <tr>
                        <th class="px-3 py-2">Name</th>
                        <th>Season</th>
                        <th>Format</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="tournament in tournaments.data" :key="tournament.id" class="border-t border-[#eee]">
                        <td class="px-3 py-2">
                            <Link :href="route('tournaments.show', tournament.slug)" class="font-bold hover:underline">{{ tournament.name }}</Link>
                            <p class="text-[12px] text-mist">{{ tournament.teams_count }} teams · {{ tournament.games_count }} matches</p>
                        </td>
                        <td>{{ tournament.season }}</td>
                        <td>{{ tournament.format_label }}</td>
                        <td>{{ tournament.status_label }}</td>
                        <td class="space-x-3 px-3 text-right">
                            <button type="button" class="text-[#0b5cab] hover:underline" @click="generate(tournament.slug)">Calendar</button>
                            <button
                                v-if="tournament.format === 'groups_playoff'"
                                type="button"
                                class="text-[#0b5cab] hover:underline"
                                @click="router.post(route('admin.tournaments.playoff', tournament.slug))"
                            >
                                Playoff
                            </button>
                            <Link :href="route('admin.tournaments.edit', tournament.slug)" class="hover:underline">Edit</Link>
                            <button type="button" class="text-live hover:underline" @click="destroy(tournament.slug)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AdminLayout>
</template>
