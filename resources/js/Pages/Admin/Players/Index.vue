<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({ players: Object });

function destroy(id) {
    if (confirm('Delete this player?')) router.delete(route('admin.players.destroy', id));
}
</script>

<template>
    <Head title="Players" />
    <AdminLayout>
        <div class="flex items-center justify-between">
            <h1 class="text-xl font-bold">Players</h1>
            <Link :href="route('admin.players.create')" class="btn">New</Link>
        </div>
        <div class="mt-4 overflow-x-auto bg-white">
            <table class="w-full min-w-[640px] text-left text-[13px]">
                <thead class="bg-[#f0f2f4] text-[11px] uppercase text-mist">
                    <tr><th class="px-3 py-2">Player</th><th>Team</th><th></th></tr>
                </thead>
                <tbody>
                    <tr v-for="player in players.data" :key="player.id" class="border-t border-[#eee]">
                        <td class="px-3 py-2">
                            <Link :href="route('players.show', player.id)" class="font-bold hover:underline">{{ player.number }} {{ player.name }}</Link>
                            <span class="ml-2 text-mist">{{ player.position }}</span>
                        </td>
                        <td>{{ player.team }}</td>
                        <td class="space-x-3 px-3 text-right">
                            <Link :href="route('admin.players.edit', player.id)" class="hover:underline">Edit</Link>
                            <button type="button" class="text-live hover:underline" @click="destroy(player.id)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="mt-3 flex gap-3 text-[13px]">
            <Link v-if="players.prev_page_url" :href="players.prev_page_url" class="hover:underline">Previous</Link>
            <Link v-if="players.next_page_url" :href="players.next_page_url" class="hover:underline">Next</Link>
        </div>
    </AdminLayout>
</template>
