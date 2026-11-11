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
    <div class="pitch-grid min-h-screen">
        <header class="border-b border-line/80 bg-ink/80 backdrop-blur">
            <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4">
                <Link :href="route('home')" class="font-display text-3xl tracking-wide text-snow">
                    MATCH<span class="text-pitch">DAY</span>
                </Link>
                <nav class="flex items-center gap-4 text-sm text-mist">
                    <Link v-if="page.props.auth.user" :href="desk()" class="hover:text-snow">
                        {{ page.props.auth.user.role_label }}
                    </Link>
                    <Link
                        v-if="page.props.auth.user"
                        :href="route('logout')"
                        method="post"
                        as="button"
                        class="hover:text-snow"
                    >
                        Вийти
                    </Link>
                    <Link v-else :href="route('login')" class="rounded-full bg-pitch px-4 py-2 font-semibold text-ink">
                        Увійти
                    </Link>
                </nav>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 py-8">
            <slot />
        </main>
    </div>
</template>
