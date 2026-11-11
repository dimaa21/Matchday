<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ matches: Array });
</script>

<template>
    <Head title="Referee" />
    <AdminLayout>
        <h1 class="text-xl font-bold">My matches</h1>
        <div class="mt-4 bg-white">
            <Link
                v-for="match in matches"
                :key="match.id"
                :href="match.can_officiate ? route('referee.matches.show', match.id) : route('matches.show', match.id)"
                class="block border-b border-[#eee] px-3 py-3 hover:bg-[#f7f9fa]"
            >
                <p class="text-[12px]" :class="match.status === 'live' ? 'font-bold text-live' : 'text-mist'">
                    {{ match.status_label }} · round {{ match.round }} · {{ match.kickoff_label }}
                </p>
                <p class="text-[16px] font-bold">
                    {{ match.home }} {{ match.home_score }}:{{ match.away_score }} {{ match.away }}
                </p>
                <p class="text-[12px] text-mist">{{ match.tournament }}</p>
            </Link>
        </div>
    </AdminLayout>
</template>
