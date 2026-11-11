<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const links = computed(() => {
    const role = page.props.auth.user?.role;

    if (role === 'referee') {
        return [['Мої матчі', 'referee.matches.index']];
    }

    if (role === 'captain') {
        return [['Склад', 'captain.squad']];
    }

    return [
        ['Дашборд', 'admin.dashboard'],
        ['Турніри', 'admin.tournaments.index'],
        ['Команди', 'admin.teams.index'],
        ['Гравці', 'admin.players.index'],
        ['Матчі', 'admin.matches.index'],
        ['Суддівська', 'referee.matches.index'],
    ];
});
</script>

<template>
    <div class="min-h-screen bg-ink text-snow lg:grid lg:grid-cols-[240px_1fr]">
        <aside class="border-b border-line bg-panel lg:border-b-0 lg:border-r">
            <div class="flex items-center justify-between px-5 py-5">
                <Link :href="route('home')" class="font-display text-3xl tracking-wide">
                    MATCH<span class="text-pitch">DAY</span>
                </Link>
                <Link :href="route('logout')" method="post" as="button" class="text-sm text-mist lg:hidden">Вийти</Link>
            </div>
            <nav class="flex gap-2 overflow-x-auto px-3 pb-4 lg:block lg:space-y-1 lg:px-3">
                <Link
                    v-for="[label, name] in links"
                    :key="name"
                    :href="route(name)"
                    class="block whitespace-nowrap rounded-xl px-3 py-2 text-sm text-mist hover:bg-raised hover:text-snow"
                    :class="{ 'bg-raised text-pitch': route().current(name) || route().current(name.replace('.index', '.*')) }"
                >
                    {{ label }}
                </Link>
            </nav>
            <div class="hidden space-y-2 px-5 py-4 text-xs text-mist lg:block">
                <p>{{ page.props.auth.user?.name }}</p>
                <Link :href="route('home')" class="block hover:text-snow">На сайт</Link>
                <Link :href="route('logout')" method="post" as="button" class="hover:text-snow">Вийти</Link>
            </div>
        </aside>
        <div>
            <div v-if="page.props.flash?.success" class="bg-pitch px-6 py-3 text-sm font-semibold text-ink">
                {{ page.props.flash.success }}
            </div>
            <div class="px-4 py-6 sm:px-8">
                <slot />
            </div>
        </div>
    </div>
</template>
