<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canResetPassword: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Acesso à plataforma" />

        <div class="mb-7">
            <p class="font-mono text-xs uppercase tracking-[0.25em] text-cyan-400">Steelbras / Telemetria Industrial</p>
            <h1 class="mt-3 text-2xl font-semibold tracking-tight text-slate-100">Acesso à plataforma</h1>
            <p class="mt-2 text-sm text-slate-400">Entre para monitorar a condição operacional.</p>
        </div>

        <div v-if="status" class="mb-5 rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-300">
            {{ status }}
        </div>

        <form class="space-y-5" @submit.prevent="submit">
            <div class="space-y-2">
                <label for="email" class="block font-mono text-xs uppercase tracking-wider text-slate-300">E-mail</label>
                <input
                    id="email"
                    type="email"
                    v-model="form.email"
                    required
                    autofocus
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
                    autocomplete="current-password"
                    :aria-invalid="Boolean(form.errors.password)"
                    class="block w-full rounded-lg border border-slate-700 bg-slate-950/70 px-4 py-3 text-sm text-slate-100 placeholder:text-slate-600 focus:border-cyan-400 focus:outline-none focus:ring-2 focus:ring-cyan-500/20"
                />
                <p v-if="form.errors.password" class="font-mono text-xs text-rose-400">{{ form.errors.password }}</p>
            </div>

            <div class="flex items-center">
                <label class="flex items-center gap-3 text-sm text-slate-400">
                    <input
                        v-model="form.remember"
                        name="remember"
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-700 bg-slate-950 text-emerald-500 focus:ring-emerald-500/30"
                    />
                    <span>Manter conectado</span>
                </label>
            </div>

            <div class="flex flex-col-reverse items-center justify-between gap-4 pt-2 sm:flex-row">
                <Link
                    v-if="canResetPassword"
                    :href="route('password.request')"
                    class="text-sm text-slate-400 underline decoration-slate-700 underline-offset-4 transition hover:text-cyan-300"
                >
                    Esqueceu sua senha?
                </Link>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="inline-flex w-full items-center justify-center rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-5 py-3 font-mono text-xs font-semibold uppercase tracking-widest text-emerald-300 transition hover:bg-emerald-500/20 focus:outline-none focus:ring-2 focus:ring-emerald-500/40 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                >
                    {{ form.processing ? 'Autenticando...' : 'Entrar' }}
                </button>
            </div>
        </form>

        <div class="mt-6 border-t border-slate-800 pt-5 text-center">
            <p class="mb-3 text-sm text-slate-400">Ainda não tem acesso?</p>
            <Link
                :href="route('register')"
                class="inline-flex w-full items-center justify-center rounded-lg border border-cyan-500/30 bg-cyan-500/10 px-5 py-3 font-mono text-xs font-semibold uppercase tracking-widest text-cyan-300 transition hover:bg-cyan-500/20 focus:outline-none focus:ring-2 focus:ring-cyan-500/40"
            >
                Fazer cadastro
            </Link>
        </div>
    </GuestLayout>
</template>
