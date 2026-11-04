<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Cadastro de acesso" />

        <div class="mb-7">
            <p class="font-mono text-xs uppercase tracking-[0.25em] text-cyan-400">Steelbras / Novo operador</p>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-100">Criar acesso</h1>
            <p class="mt-2 text-sm text-slate-400">Cadastre suas credenciais para acessar o monitoramento.</p>
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div class="space-y-2">
                <label for="name" class="block font-mono text-xs uppercase tracking-wider text-slate-300">Nome</label>
                <input
                    id="name"
                    type="text"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                    :aria-invalid="Boolean(form.errors.name)"
                    class="block w-full rounded-lg border border-slate-700 bg-slate-950/70 px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/20"
                />
                <p v-if="form.errors.name" class="font-mono text-xs text-rose-400">{{ form.errors.name }}</p>
            </div>

            <div class="space-y-2">
                <label for="email" class="block font-mono text-xs uppercase tracking-wider text-slate-300">E-mail</label>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autocomplete="username"
                    :aria-invalid="Boolean(form.errors.email)"
                    class="block w-full rounded-lg border border-slate-700 bg-slate-950/70 px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/20"
                />
                <p v-if="form.errors.email" class="font-mono text-xs text-rose-400">{{ form.errors.email }}</p>
            </div>

            <div class="space-y-2">
                <label for="password" class="block font-mono text-xs uppercase tracking-wider text-slate-300">Senha</label>
                <input
                    id="password"
                    type="password"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                    :aria-invalid="Boolean(form.errors.password)"
                    class="block w-full rounded-lg border border-slate-700 bg-slate-950/70 px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/20"
                />
                <p v-if="form.errors.password" class="font-mono text-xs text-rose-400">{{ form.errors.password }}</p>
            </div>

            <div class="space-y-2">
                <label for="password_confirmation" class="block font-mono text-xs uppercase tracking-wider text-slate-300">Confirmar senha</label>
                <input
                    id="password_confirmation"
                    type="password"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                    :aria-invalid="Boolean(form.errors.password_confirmation)"
                    class="block w-full rounded-lg border border-slate-700 bg-slate-950/70 px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/20"
                />
                <p v-if="form.errors.password_confirmation" class="font-mono text-xs text-rose-400">{{ form.errors.password_confirmation }}</p>
            </div>

            <div class="flex flex-col-reverse items-center justify-between gap-4 pt-2 sm:flex-row">
                <Link
                    :href="route('login')"
                    class="text-sm text-slate-400 underline decoration-slate-700 underline-offset-4 transition hover:text-cyan-300"
                >
                    Já tem cadastro?
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex w-full items-center justify-center rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-5 py-3 font-mono text-xs font-semibold uppercase tracking-widest text-emerald-300 transition hover:bg-emerald-500/20 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                >
                    {{ form.processing ? 'Criando...' : 'Criar acesso' }}
                </button>
            </div>
        </form>
    </GuestLayout>
</template>
