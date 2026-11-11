<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    match: Object,
});

function leading(side) {
    if (props.match.status !== 'finished') {
        return false;
    }

    const home = props.match.home_score;
    const away = props.match.away_score;

    return side === 'home' ? home > away : away > home;
}
</script>

<template>
    <Link
        :href="route('matches.show', match.id)"
        class="grid grid-cols-[52px_minmax(0,1fr)_54px_minmax(0,1fr)] items-center border-b border-[#eee] bg-white px-2 py-[7px] text-[13px] hover:bg-[#f7f9fa]"
    >
        <span class="text-[12px]" :class="match.status === 'live' ? 'font-bold text-live' : 'text-mist'">
            {{ match.status === 'live' ? 'LIVE' : (match.kickoff_time || '–') }}
        </span>
        <span class="truncate text-right" :class="leading('home') ? 'font-bold' : ''">
            {{ match.home_team?.name || 'Bye' }}
        </span>
        <span class="text-center text-[13px] font-bold" :class="match.status === 'live' ? 'text-live' : 'text-navy'">
            <template v-if="match.is_bye || match.status === 'scheduled'">–</template>
            <template v-else>{{ match.home_score }}:{{ match.away_score }}</template>
        </span>
        <span class="truncate" :class="leading('away') ? 'font-bold' : ''">
            {{ match.away_team?.name || 'Bye' }}
        </span>
    </Link>
</template>
