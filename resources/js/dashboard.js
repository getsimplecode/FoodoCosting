import Chart from 'chart.js/auto';

document.querySelectorAll('canvas[data-dashboard-chart]').forEach((canvas) => {
    const config = JSON.parse(canvas.dataset.chart);

    if (canvas.dataset.dashboardChart === 'line') {
        new Chart(canvas, {
            type: 'line',
            data: {
                labels: config.labels,
                datasets: config.datasets.map((dataset) => ({
                    ...dataset,
                    borderWidth: 2,
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    tension: 0.35,
                    fill: false,
                })),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                return ` ${context.dataset.label}: ${context.raw} movements`;
                            },
                        },
                    },
                },
                scales: {
                    x: { grid: { display: false } },
                    y: {
                        beginAtZero: true,
                        ticks: { precision: 0, stepSize: 1 },
                        title: { display: true, text: 'Movements' },
                    },
                },
            },
        });

        return;
    }

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: config.labels,
            datasets: [{
                data: config.values,
                backgroundColor: config.colors,
                borderWidth: 0,
                hoverOffset: config.empty ? 0 : 5,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '72%',
            plugins: {
                legend: { display: !config.empty, position: 'bottom' },
                tooltip: {
                    enabled: !config.empty,
                    callbacks: {
                        label(context) {
                            const total = context.dataset.data.reduce((sum, value) => sum + value, 0);
                            const percentage = total > 0 ? (context.parsed / total) * 100 : 0;

                            return ` ${context.label}: ${context.parsed} (${percentage.toFixed(1)}%)`;
                        },
                    },
                },
            },
        },
    });
});
