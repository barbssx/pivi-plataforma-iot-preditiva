<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head } from '@inertiajs/vue3';
import VueApexCharts from 'vue3-apexcharts';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    readings: Array
});

const readingsList = ref((props.readings || []).slice(-20));

const latestReading = computed(() => {
    if (!readingsList.value || readingsList.value.length === 0) {
        return {
            temperature: null,
            vibracao_x: null,
            vibracao_y: null,
            vibracao_z: null,
            nivel_fluido: null,
            rpm: null,
            status_operacional: 'AGUARDANDO DADOS',
        };
    }

    return readingsList.value[readingsList.value.length - 1];
});

const currentStatus = computed(() => latestReading.value.status_operacional ?? latestReading.value.status ?? 'AGUARDANDO DADOS');

const statusClasses = computed(() => ({
    'border-emerald-500/30 bg-emerald-500/10 text-emerald-400': currentStatus.value === 'NORMAL',
    'border-amber-500/30 bg-amber-500/10 text-amber-400': currentStatus.value === 'ATENCAO',
    'border-rose-500/30 bg-rose-500/10 text-rose-400 animate-pulse': currentStatus.value === 'CRITICO',
    'border-slate-700 bg-slate-800/50 text-slate-400': !['NORMAL', 'ATENCAO', 'CRITICO'].includes(currentStatus.value),
}));

const timeCategories = computed(() => {
    if (!readingsList.value || readingsList.value.length === 0) return [];
    return readingsList.value.map(r => {
        if (!r.created_at) return '';
        const d = new Date(r.created_at);
        return isNaN(d.getTime()) ? '' : d.toLocaleTimeString('pt-BR');
    });
});

const tempChartOptions = computed(() => ({
    chart: { id: 'temp-chart', toolbar: { show: false }, animations: { enabled: true }, background: 'transparent' },
    theme: { mode: 'dark' },
    xaxis: { categories: timeCategories.value, labels: { style: { colors: '#64748b' } }, axisBorder: { color: '#334155' }, axisTicks: { color: '#334155' } },
    yaxis: { labels: { style: { colors: '#64748b' } } },
    grid: { borderColor: '#1e293b' },
    tooltip: { theme: 'dark' },
    colors: ['#fb7185'],
    stroke: { curve: 'smooth', width: 3 },
    dataLabels: { enabled: false },
}));

const tempSeries = computed(() => [{
    name: 'Temperatura (°C)',
    data: readingsList.value.map(r => Number(r.temperature) || 0),
}]);

const vibChartOptions = computed(() => ({
    chart: { id: 'vib-chart', toolbar: { show: false }, animations: { enabled: true }, background: 'transparent' },
    theme: { mode: 'dark' },
    xaxis: { categories: timeCategories.value, labels: { style: { colors: '#64748b' } }, axisBorder: { color: '#334155' }, axisTicks: { color: '#334155' } },
    yaxis: { labels: { style: { colors: '#64748b' } } },
    grid: { borderColor: '#1e293b' },
    tooltip: { theme: 'dark' },
    legend: { labels: { colors: '#cbd5e1' } },
    colors: ['#38bdf8', '#34d399', '#fbbf24'],
    stroke: { curve: 'smooth', width: 2 },
    dataLabels: { enabled: false },
}));

const vibSeries = computed(() => [
    { name: 'Eixo X', data: readingsList.value.map(r => Number(r.vibracao_x ?? r.vibration_x) || 0) },
    { name: 'Eixo Y', data: readingsList.value.map(r => Number(r.vibracao_y ?? r.vibration_y) || 0) },
    { name: 'Eixo Z', data: readingsList.value.map(r => Number(r.vibracao_z ?? r.vibration_z) || 0) },
]);

let pollingInterval = null;
let activeRequest = null;
let isMounted = false;
let isFetching = false;

const fetchLatestData = async () => {
    if (!isMounted || isFetching) return;

    isFetching = true;
    const request = new AbortController();
    activeRequest = request;

    try {
        const response = await fetch('/api/sensor-readings/latest', { signal: request.signal });
        if (response.ok) {
            const readings = await response.json();
            if (isMounted && Array.isArray(readings)) {
                readingsList.value = readings.slice(-20);
            }
        }
    } catch (error) {
        if (error.name !== 'AbortError') console.error(error);
    } finally {
        if (activeRequest === request) activeRequest = null;
        isFetching = false;
    }
};

onMounted(() => {
    isMounted = true;
    fetchLatestData();
    pollingInterval = window.setInterval(fetchLatestData, 2000);
});

onUnmounted(() => {
    isMounted = false;
    window.clearInterval(pollingInterval);
    activeRequest?.abort();
});
</script>

<template>
    <Head title="Dashboard - Monitoramento de Condição" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col justify-between gap-3 sm:flex-row sm:items-center">
                <div>
                    <p class="font-mono text-xs uppercase tracking-[0.25em] text-cyan-400">Steelbras / AMA TR4</p>
                    <h2 class="mt-2 text-xl font-semibold leading-tight text-slate-100">Monitoramento operacional</h2>
                </div>
                <span class="inline-flex w-fit items-center gap-2 rounded-full border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5 font-mono text-xs text-emerald-400">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    TELEMETRIA ATIVA / 2 S
                </span>
            </div>
        </template>

        <div class="min-h-[calc(100vh-8rem)] bg-slate-950 px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-7">
                    <div class="rounded-xl border bg-slate-900/50 p-5 backdrop-blur-xl" :class="statusClasses">
                        <p class="font-mono text-xs uppercase tracking-wider text-slate-400">Estado operacional</p>
                        <p class="mt-3 font-mono text-lg font-bold" :class="{ 'animate-pulse': currentStatus === 'CRITICO' }">
                            {{ currentStatus }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-5 backdrop-blur-xl">
                        <p class="font-mono text-xs uppercase tracking-wider text-slate-400">Temperatura</p>
                        <p class="mt-3 font-mono text-xl font-semibold text-slate-100">
                            {{ latestReading.temperature ?? '—' }} <span class="text-sm text-slate-500">°C</span>
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-5 backdrop-blur-xl">
                        <p class="font-mono text-xs uppercase tracking-wider text-slate-400">Vibração eixo X</p>
                        <p class="mt-3 font-mono text-xl font-semibold text-slate-100">
                            {{ latestReading.vibracao_x ?? latestReading.vibration_x ?? '—' }} <span class="text-sm text-slate-500">m/s²</span>
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-5 backdrop-blur-xl">
                        <p class="font-mono text-xs uppercase tracking-wider text-slate-400">Vibração eixo Y</p>
                        <p class="mt-3 font-mono text-xl font-semibold text-slate-100">
                            {{ latestReading.vibracao_y ?? latestReading.vibration_y ?? '—' }} <span class="text-sm text-slate-500">m/s²</span>
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-5 backdrop-blur-xl">
                        <p class="font-mono text-xs uppercase tracking-wider text-slate-400">Vibração eixo Z</p>
                        <p class="mt-3 font-mono text-xl font-semibold text-slate-100">
                            {{ latestReading.vibracao_z ?? latestReading.vibration_z ?? '—' }} <span class="text-sm text-slate-500">m/s²</span>
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-5 backdrop-blur-xl">
                        <p class="font-mono text-xs uppercase tracking-wider text-slate-400">Nível de fluido</p>
                        <p class="mt-3 font-mono text-xl font-semibold" :class="{
                            'text-emerald-400': (latestReading.nivel_fluido ?? latestReading.fluid_level) === 'OK',
                            'text-rose-400': (latestReading.nivel_fluido ?? latestReading.fluid_level) === 'FALHA',
                            'text-slate-500': !(latestReading.nivel_fluido ?? latestReading.fluid_level),
                        }">
                            {{ latestReading.nivel_fluido ?? latestReading.fluid_level ?? '—' }}
                        </p>
                    </div>

                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-5 backdrop-blur-xl">
                        <p class="font-mono text-xs uppercase tracking-wider text-slate-400">Rotação</p>
                        <p class="mt-3 font-mono text-xl font-semibold text-slate-100">
                            {{ latestReading.rpm ?? '—' }} <span class="text-sm text-slate-500">RPM</span>
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-5 backdrop-blur-xl sm:p-6">
                        <h3 class="mb-5 font-mono text-sm font-semibold uppercase tracking-wider text-slate-200">Tendência térmica <span class="text-slate-500">/ °C</span></h3>
                        <VueApexCharts
                            type="line"
                            height="300"
                            :options="tempChartOptions"
                            :series="tempSeries"
                        ></VueApexCharts>
                    </div>

                    <div class="rounded-xl border border-slate-800 bg-slate-900/50 p-5 backdrop-blur-xl sm:p-6">
                        <h3 class="mb-5 font-mono text-sm font-semibold uppercase tracking-wider text-slate-200">Análise triaxial <span class="text-slate-500">/ X · Y · Z · m/s²</span></h3>
                        <VueApexCharts
                            type="line"
                            height="300"
                            :options="vibChartOptions"
                            :series="vibSeries"
                        ></VueApexCharts>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>
