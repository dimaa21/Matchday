<script setup>
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    tournaments: Array,
    live: Array,
});
</script>

<template>
    <Head title="Matchday" />

    <PublicLayout>
        <section class="overflow-hidden rounded-[2rem] border border-line bg-panel px-6 py-10 sm:px-10">
            <p class="text-xs font-semibold uppercase tracking-[0.28em] text-pitch">Аматорський футбол</p>
            <h1 class="mt-3 max-w-3xl font-display text-6xl uppercase leading-[0.9] text-snow sm:text-8xl">
                Сьогодні<br />на полі
            </h1>
            <p class="mt-5 max-w-xl text-mist">
                Ліги, кубки і живий рахунок. Суддя веде матч з телефона, таблиця перераховується сама.
            </p>
        </section>

        <section v-if="live.length" class="mt-8">
            <h2 class="font-display text-3xl uppercase tracking-wide">Зараз грають</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <Link
                    v-for="match in live"
                    :key="match.id"
                    :href="route('matches.show', match.id)"
                    class="panel flex items-center justify-between px-5 py-4"
                >
                    <div>
                        <p class="text-xs uppercase tracking-[0.2em] text-flare">Live · {{ match.tournament?.name }}</p>
                        <p class="mt-2 font-display text-3xl uppercase">
                            {{ match.home_team?.name }}
                            <span class="text-pitch">{{ match.home_score }}:{{ match.away_score }}</span>
                            {{ match.away_team?.name }}
                        </p>
                    </div>
                </Link>
            </div>
        </section>

        <section class="mt-10">
            <h2 class="font-display text-3xl uppercase tracking-wide">Турніри</h2>
            <div v-if="tournaments.length === 0" class="mt-4 text-mist">Сезон ще не відкрито.</div>
            <div class="mt-4 grid gap-4 md:grid-cols-2">
                <Link
                    v-for="tournament in tournaments"
                    :key="tournament.slug"
                    :href="route('tournaments.show', tournament.slug)"
                    class="panel block p-5 transition hover:border-pitch/50"
                >
                    <div class="flex items-center justify-between text-xs uppercase tracking-[0.18em] text-mist">
                        <span>{{ tournament.season }}</span>
                        <span>{{ tournament.format }}</span>
                    </div>
                    <h3 class="mt-3 font-display text-4xl uppercase">{{ tournament.name }}</h3>
                    <p class="mt-3 text-sm text-mist">{{ tournament.teams_count }} команд · {{ tournament.status_label }}</p>
                </Link>
            </div>
        </section>
    </PublicLayout>
</template>
