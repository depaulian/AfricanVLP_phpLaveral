import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

Alpine.data('chart', (config = {}) => ({
    chart: null,
    
    init() {
        this.$nextTick(() => {
            this.createChart();
        });
    },

    createChart() {
        const ctx = this.$refs.canvas.getContext('2d');
        
        const defaultConfig = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    mode: 'index',
                    intersect: false,
                }
            },
            scales: {
                x: {
                    display: true,
                    grid: {
                        display: false
                    }
                },
                y: {
                    display: true,
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                }
            }
        };

        this.chart = new Chart(ctx, {
            type: config.type || 'line',
            data: config.data || {},
            options: { ...defaultConfig, ...config.options }
        });
    },

    updateChart(newData) {
        if (this.chart) {
            this.chart.data = newData;
            this.chart.update();
        }
    },

    addData(label, data) {
        if (this.chart) {
            this.chart.data.labels.push(label);
            this.chart.data.datasets.forEach((dataset, index) => {
                dataset.data.push(data[index] || 0);
            });
            this.chart.update();
        }
    },

    removeData() {
        if (this.chart) {
            this.chart.data.labels.pop();
            this.chart.data.datasets.forEach((dataset) => {
                dataset.data.pop();
            });
            this.chart.update();
        }
    },

    destroy() {
        if (this.chart) {
            this.chart.destroy();
            this.chart = null;
        }
    }
}));

Alpine.data('lineChart', (data, options = {}) => ({
    ...Alpine.raw(Alpine.data('chart')({
        type: 'line',
        data: data,
        options: {
            tension: 0.4,
            fill: false,
            borderWidth: 2,
            pointRadius: 4,
            pointHoverRadius: 6,
            ...options
        }
    }))
}));

Alpine.data('barChart', (data, options = {}) => ({
    ...Alpine.raw(Alpine.data('chart')({
        type: 'bar',
        data: data,
        options: {
            borderWidth: 1,
            borderRadius: 4,
            ...options
        }
    }))
}));

Alpine.data('pieChart', (data, options = {}) => ({
    ...Alpine.raw(Alpine.data('chart')({
        type: 'pie',
        data: data,
        options: {
            plugins: {
                legend: {
                    position: 'right',
                }
            },
            ...options
        }
    }))
}));

Alpine.data('doughnutChart', (data, options = {}) => ({
    ...Alpine.raw(Alpine.data('chart')({
        type: 'doughnut',
        data: data,
        options: {
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'right',
                }
            },
            ...options
        }
    }))
}));