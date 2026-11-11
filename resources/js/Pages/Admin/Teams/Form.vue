<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({ team: Object });

const form = useForm({
    name: props.team?.name ?? '',
    slug: props.team?.slug ?? '',
    city: props.team?.city ?? '',
    primary_color: props.team?.primary_color ?? '#1d4ed8',
    secondary_color: props.team?.secondary_color ?? '#ffffff',
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
    <Head title="Team" />
    <AdminLayout>
        <form class="max-w-xl space-y-4" @submit.prevent="submit">
            <h1 class="text-xl font-bold">{{ team ? team.name : 'New team' }}</h1>
            <label class="block text-[13px]">Name<input v-model="form.name" class="field" /></label>
            <label class="block text-[13px]">Slug<input v-model="form.slug" class="field" placeholder="filled in automatically" /></label>
            <label class="block text-[13px]">City<input v-model="form.city" class="field" /></label>
            <div class="grid grid-cols-2 gap-4">
                <label class="text-[13px]">Primary<input v-model="form.primary_color" type="color" class="mt-1 block h-10 w-16 bg-transparent" /></label>
                <label class="text-[13px]">Secondary<input v-model="form.secondary_color" type="color" class="mt-1 block h-10 w-16 bg-transparent" /></label>
            </div>
            <label class="block text-[13px]">Logo
                <input type="file" accept="image/*" class="mt-1 block text-sm" @input="form.logo = $event.target.files[0]" />
            </label>
            <button class="btn">Save</button>
            <Link :href="route('admin.teams.index')" class="ml-3 text-[13px] text-mist">Back</Link>
        </form>
    </AdminLayout>
</template>
