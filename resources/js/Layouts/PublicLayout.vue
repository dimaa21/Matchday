<script setup>
import { Link, usePage } from '@inertiajs/vue3';

const page = usePage();

function desk() {
    const role = page.props.auth.user?.role;

    if (role === 'admin') return route('admin.dashboard');
    if (role === 'referee') return route('referee.matches.index');
    if (role === 'captain') return route('captain.squad');

    return route('home');
}
</script>

<template>
    <div class="min-h-screen bg-paper text-navy">
        <header class="bg-navy text-white">
            <div class="mx-auto flex h-14 max-w-[1100px] items-center justify-between px-3">
                <Link :href="route('home')" class="flex items-center gap-2">
                    <span class="flex h-8 w-8 items-center justify-center rounded-full border-2 border-white text-[13px] font-bold">M</span>
                    <span class="text-[22px] font-bold tracking-tight">MATCHDAY</span>
                </Link>
                <nav class="flex items-center gap-4 text-[13px]">
                    <Link v-if="page.props.auth.user" :href="desk()" class="hover:underline">
                        {{ page.props.auth.user.role_label }}
                    </Link>
                    <Link
                        v-if="page.props.auth.user"
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="hover:underline"
                    >
                        Log out
                    </Link>
                    <Link v-else :href="route('login')" class="rounded bg-live px-3 py-1.5 text-[13px] font-bold">
                        Log in
                    </Link>
                </nav>
            </div>
            <div class="mx-auto flex max-w-[1100px] px-3 text-[13px] font-bold uppercase tracking-wide">
                <span class="border-b-[3px] border-live py-2">Football</span>
            </div>
        </header>

        <div class="mx-auto grid max-w-[1100px] gap-3 px-3 py-3 lg:grid-cols-[230px_minmax(0,1fr)]">
            <aside>
                <div class="flex gap-2 overflow-x-auto lg:hidden">
                    <Link
                        v-for="competition in page.props.competitions"
                        :key="competition.slug"
                        :href="route('tournaments.show', competition.slug)"
                        class="shrink-0 bg-white px-3 py-2 text-[12px] font-bold"
                    >
                        {{ competition.name }}
                    </Link>
                </div>
                <div class="hidden overflow-hidden bg-white lg:block">
                    <p class="bg-navy px-3 py-2 text-[12px] font-bold uppercase tracking-wide text-white">Competitions</p>
                    <Link
                        v-for="competition in page.props.competitions"
                        :key="competition.slug"
                        :href="route('tournaments.show', competition.slug)"
                        class="block border-t border-[#eee] px-3 py-2 text-[13px] hover:bg-[#f7f9fa]"
                        :class="page.url.startsWith('/tournaments/' + competition.slug) ? 'bg-[#f4f7f8] font-bold' : ''"
                    >
                        {{ competition.name }}
                        <span class="block text-[11px] font-normal text-mist">{{ competition.season }}</span>
                    </Link>
                    <p v-if="!page.props.competitions?.length" class="px-3 py-3 text-[13px] text-mist">No competitions yet.</p>
                </div>
            </aside>
            <main class="min-w-0">
                <slot />
            </main>
        </div>
    </div>
</template>
