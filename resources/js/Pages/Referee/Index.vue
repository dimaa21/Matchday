<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({ matches: Array });
</script>

<template>
    <Head title="Суддівська" />
    <AdminLayout>
        <h1 class="font-display text-5xl uppercase">Мої матчі</h1>
        <div class="mt-6 space-y-3">
            <Link
                v-for="match in matches"
                :key="match.id"
                :href="match.can_officiate ? route('referee.matches.show', match.id) : route('matches.show', match.id)"
                class="block rounded-3xl border border-line bg-panel px-5 py-4"
            >
                <p class="text-xs uppercase tracking-[0.16em]" :class="match.status === 'live' ? 'text-flare' : 'text-mist'">
                    {{ match.status_label }} · тур {{ match.round }} · {{ match.kickoff_label }}
                </p>
                <p class="mt-1 font-display text-3xl uppercase">
                    {{ match.home }} {{ match.home_score }}:{{ match.away_score }} {{ match.away }}
                </p>
                <p class="text-sm text-mist">{{ match.tournament }}</p>
            </Link>
        </div>
    </AdminLayout>
</template>
