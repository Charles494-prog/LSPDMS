import Chart from 'chart.js/auto';

window.Chart = Chart;

Chart.register({
    id: 'dashboardPointValues',
    afterDatasetsDraw(chart) {
        if (chart.canvas.id !== 'ot-chart') return;

        const { ctx } = chart;
        const background = getComputedStyle(chart.canvas.closest('.dash'))
            .getPropertyValue('--card').trim() || '#fff';

        ctx.save();
        ctx.font = `700 10px ${Chart.defaults.font.family}`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        chart.data.datasets.forEach((dataset, datasetIndex) => {
            const meta = chart.getDatasetMeta(datasetIndex);
            if (!chart.isDatasetVisible(datasetIndex)) return;

            meta.data.forEach((point, index) => {
                const value = dataset.data[index];
                if (point.skip || value === null || value === undefined) return;

                const labelY = point.y + (datasetIndex === 2 ? 15 : -12);
                ctx.lineWidth = 3;
                ctx.lineJoin = 'round';
                ctx.strokeStyle = background;
                ctx.strokeText(String(value), point.x, labelY);
                ctx.fillStyle = dataset.borderColor;
                ctx.fillText(String(value), point.x, labelY);
            });
        });
        ctx.restore();
    },
});
