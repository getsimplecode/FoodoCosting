import Chart from 'chart.js/auto';

document.querySelectorAll('canvas[data-chart]').forEach((canvas) => {
    const config = JSON.parse(canvas.dataset.chart);

    if (config.type === 'bar') {
        new Chart(canvas, {
            type: 'bar',
            data: {
                labels: config.labels,
                datasets: config.datasets,
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        beginAtZero: true,
                        stacked: true,
                        ticks: {
                            callback(value) {
                                return `₱${Number(value).toLocaleString('en-PH')}`;
                            },
                        },
                    },
                    y: { stacked: true },
                },
                plugins: {
                    legend: { position: 'bottom' },
                    tooltip: {
                        callbacks: {
                            label(context) {
                                const value = Number(context.raw);
                                const format = { minimumFractionDigits: 2, maximumFractionDigits: 2 };
                                const amount = Math.abs(value).toLocaleString('en-PH', format);

                                return ` ${context.dataset.label}: ${value < 0 ? '−' : ''}₱${amount}`;
                            },
                        },
                    },
                },
            },
        });

        return;
    }

    const total = config.values.reduce((sum, value) => sum + value, 0);
    const isGauge = config.type === 'gauge';

    new Chart(canvas, {
        type: 'doughnut',
        data: {
            labels: config.labels,
            datasets: [{
                data: total > 0 ? config.values : [1],
                backgroundColor: total > 0 ? config.colors : ['#e9ecef'],
                borderWidth: 0,
                hoverOffset: 6,
            }],
        },
        options: {
            cutout: isGauge ? '78%' : '72%',
            rotation: isGauge ? -90 : 0,
            circumference: isGauge ? 180 : 360,
            aspectRatio: isGauge ? 2 : 1,
            animation: {
                animateRotate: true,
                animateScale: true,
                duration: 1600,
                easing: 'easeOutQuart',
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    enabled: total > 0,
                    callbacks: {
                        label(context) {
                            const format = { minimumFractionDigits: 2, maximumFractionDigits: 2 };

                            return ` ${context.label}: ${config.prefix}${context.parsed.toLocaleString('en-PH', format)}${config.suffix}`;
                        },
                    },
                },
            },
        },
    });
});
