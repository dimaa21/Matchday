<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ team: Object });

const form = useForm({
    name: props.team?.name ?? '',
    slug: props.team?.slug ?? '',
    city: props.team?.city ?? '',
    primary_color: props.team?.primary_color ?? '#d6ff3f',
    secondary_color: props.team?.secondary_color ?? '#101a24',
    logo: null,
});

function submit() {
    const options = { forceFormData: true };

    if (props.team) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(route('admin.teams.update', props.team.id), options);
        return;
    }

    form.post(route('admin.teams.store'), options);
}
</script>

<template>
    <Head title="Команда" />
    <AdminLayout>
        <form class="max-w-xl space-y-4" @submit.prevent="submit">
            <h1 class="font-display text-5xl uppercase">{{ team ? team.name : 'Нова команда' }}</h1>
            <label class="block text-sm">Назва<input v-model="form.name" class="mt-1 w-full rounded-xl border-line bg-raised" /></label>
            <label class="block text-sm">Slug<input v-model="form.slug" class="mt-1 w-full rounded-xl border-line bg-raised" placeholder="заповниться само" /></label>
            <label class="block text-sm">Місто<input v-model="form.city" class="mt-1 w-full rounded-xl border-line bg-raised" /></label>
            <div class="flex gap-4">
                <label class="text-sm">Основний<input v-model="form.primary_color" type="color" class="mt-1 block h-12 w-20 bg-transparent" /></label>
                <label class="text-sm">Додатковий<input v-model="form.secondary_color" type="color" class="mt-1 block h-12 w-20 bg-transparent" /></label>
            </div>
            <label class="block text-sm">Лого
                <input type="file" accept="image/*" class="mt-1 block text-sm" @input="form.logo = $event.target.files[0]" />
            </label>
            <button class="rounded-full bg-pitch px-5 py-2 text-sm font-semibold text-ink">Зберегти</button>
            <Link :href="route('admin.teams.index')" class="ml-3 text-sm text-mist">Назад</Link>
        </form>
    </AdminLayout>
</template>
