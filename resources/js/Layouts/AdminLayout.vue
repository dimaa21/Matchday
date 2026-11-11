<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const links = computed(() => {
    const role = page.props.auth.user?.role;

    if (role === 'referee') {
        return [['My matches', 'referee.matches.index']];
    }

    if (role === 'captain') {
        return [['Squad', 'captain.squad']];
    }

    return [
        ['Dashboard', 'admin.dashboard'],
        ['Tournaments', 'admin.tournaments.index'],
        ['Teams', 'admin.teams.index'],
        ['Players', 'admin.players.index'],
        ['Matches', 'admin.matches.index'],
        ['Referee desk', 'referee.matches.index'],
    ];
});
</script>

<template>
    <div class="min-h-screen bg-paper text-navy lg:grid lg:grid-cols-[220px_1fr]">
        <aside class="bg-navy text-white">
            <div class="flex items-center justify-between px-4 py-4">
                <Link :href="route('home')" class="text-[18px] font-bold tracking-tight">MATCHDAY</Link>
                <Link :href="route('logout')" method="post" as="button" class="text-[13px] lg:hidden">Log out</Link>
            </div>
            <nav class="flex gap-1 overflow-x-auto px-2 pb-3 lg:block lg:space-y-1 lg:px-2">
                <Link
                    v-for="[label, name] in links"
                    :key="name"
                    :href="route(name)"
                    class="block whitespace-nowrap rounded px-3 py-2 text-[13px] text-white/80 hover:bg-white/10 hover:text-white"
                    :class="{ 'bg-white/10 font-bold text-white': route().current(name) || route().current(name.replace('.index', '.*')) }"
                >
                    {{ label }}
                </Link>
            </nav>
            <div class="hidden space-y-2 px-4 py-4 text-[12px] text-white/70 lg:block">
                <p class="text-white">{{ page.props.auth.user?.name }}</p>
                <Link :href="route('home')" class="block hover:text-white">Back to scores</Link>
                <Link :href="route('logout')" method="post" as="button" class="hover:text-white">Log out</Link>
            </div>
        </aside>
        <div>
            <div v-if="page.props.flash?.success" class="bg-[#00a83f] px-6 py-2 text-[13px] font-bold text-white">
                {{ page.props.flash.success }}
            </div>
            <div class="px-4 py-5 sm:px-6">
                <slot />
            </div>
        </div>
    </div>
</template>
