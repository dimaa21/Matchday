<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ teams: Object });

function destroy(id) {
    if (confirm('Delete this team?')) router.delete(route('admin.teams.destroy', id));
}
</script>

<template>
    <Head title="Teams" />
    <AdminLayout>
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">Teams</h1>
            <Link :href="route('admin.teams.create')" class="btn">New</Link>
        </div>
        <div class="mt-4 bg-white">
            <article v-for="team in teams.data" :key="team.id" class="flex items-center justify-between border-b border-[#eee] px-3 py-2 text-[13px]">
                <Link :href="route('teams.show', team.slug)" class="flex items-center gap-3 hover:underline">
                    <span class="h-3 w-3 rounded-full" :style="{ background: team.primary_color }" />
                    <span class="font-bold">{{ team.name }}</span>
                    <span class="text-mist">{{ team.city }} · {{ team.players_count }}</span>
                </Link>
                <span class="space-x-3">
                    <Link :href="route('admin.teams.edit', team.id)" class="hover:underline">Edit</Link>
                    <button type="button" class="text-live hover:underline" @click="destroy(team.id)">Delete</button>
                </span>
            </article>
        </div>
    </AdminLayout>
</template>
