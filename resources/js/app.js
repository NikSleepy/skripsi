import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;

const PALETTE = ['#2563eb', '#f59e0b', '#10b981', '#ef4444', '#0ea5e9', '#8b5cf6', '#64748b'];
const GRID = 'rgba(148, 163, 184, 0.25)';
const TICK = '#64748b';

Chart.defaults.font.family = "'Figtree', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.color = TICK;

const baseOptions = (payload) => ({
    responsive: true,
    maintainAspectRatio: false,
    indexAxis: payload.indexAxis ?? 'x',
    layout: { padding: 4 },
    plugins: {
        legend: {
            display: payload.datasets.length > 1 || payload.type === 'doughnut',
            position: payload.type === 'doughnut' ? 'bottom' : 'top',
            labels: { usePointStyle: true, boxWidth: 8, padding: 16, font: { size: 11 } },
        },
        tooltip: {
            backgroundColor: '#0f172a',
            padding: 10,
            cornerRadius: 8,
            displayColors: payload.type === 'doughnut',
            callbacks: payload.suffix ? {
                label: (item) => ` ${item.formattedValue}${payload.suffix}`,
            } : {},
        },
    },
    scales: payload.type === 'doughnut' ? undefined : {
        x: {
            grid: { display: false },
            border: { display: false },
            ticks: { font: { size: 11 } },
        },
        y: {
            beginAtZero: payload.beginAtZero ?? true,
            grid: { color: GRID, drawTicks: false },
            border: { display: false },
            ticks: { font: { size: 11 }, padding: 8 },
        },
    },
});

const datasetDefaults = (type, index) => {
    const color = PALETTE[index % PALETTE.length];

    if (type === 'bar') {
        return { backgroundColor: color, borderRadius: 6, maxBarThickness: 44, borderSkipped: false };
    }

    if (type === 'line') {
        return {
            borderColor: color,
            backgroundColor: `${color}1a`,
            borderWidth: 2,
            tension: 0.35,
            fill: true,
            pointRadius: 3,
            pointBackgroundColor: '#ffffff',
            pointBorderColor: color,
            pointBorderWidth: 2,
        };
    }

    if (type === 'doughnut') {
        return { borderColor: '#ffffff', borderWidth: 2, hoverOffset: 4 };
    }

    return {};
};

const initCharts = () => {
    document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
        // Chart di dalam tab tersembunyi baru digambar saat tabnya dibuka.
        if (canvas.dataset.chartReady || canvas.offsetParent === null) {
            return;
        }

        const payload = JSON.parse(canvas.dataset.chart);

        canvas.dataset.chartReady = '1';

        new Chart(canvas.getContext('2d'), {
            type: payload.type,
            data: {
                labels: payload.labels,
                datasets: payload.datasets.map((dataset, index) => ({
                    ...datasetDefaults(payload.type, index),
                    ...dataset,
                    backgroundColor: dataset.backgroundColor
                        ?? (payload.type === 'doughnut' ? PALETTE : undefined)
                        ?? datasetDefaults(payload.type, index).backgroundColor,
                })),
            },
            options: baseOptions(payload),
        });
    });
};

window.dashboardInitCharts = initCharts;

document.addEventListener('DOMContentLoaded', initCharts);
window.addEventListener('load', initCharts);

Alpine.start();

