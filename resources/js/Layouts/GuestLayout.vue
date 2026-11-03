<script setup>
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import { Link } from '@inertiajs/vue3';
</script>

<template>
    <div
        class="relative isolate flex min-h-screen flex-col items-center justify-center overflow-hidden bg-slate-950 px-4 py-12 text-slate-100"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-[linear-gradient(to_right,#1e293b_1px,transparent_1px),linear-gradient(to_bottom,#1e293b_1px,transparent_1px)] bg-[size:48px_48px] opacity-40"
        ></div>
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 bg-gradient-to-b from-cyan-950/30 via-slate-950/40 to-slate-950"
        ></div>

        <div class="relative z-10 w-full max-w-md">
            <div class="mb-8 flex justify-center">
                <Link href="/">
                    <ApplicationLogo class="h-14 w-14 fill-current text-cyan-300" />
                </Link>
            </div>

            <div
                class="w-full overflow-hidden rounded-2xl border border-slate-800 bg-slate-900/50 px-6 py-8 shadow-2xl shadow-black/30 backdrop-blur-xl sm:px-8"
            >
                <slot />
            </div>
        </div>
    </div>
</template>
