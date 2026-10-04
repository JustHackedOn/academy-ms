

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

/**
 * Animated count-up for KPI tiles: <span x-data="countUp(12345)">0</span>
 */
Alpine.data('countUp', (target = 0, duration = 900) => ({
    value: 0,
    init() {
        const end = Number(target) || 0;
        const start = performance.now();
        const step = (now) => {
            const p = Math.min(1, (now - start) / duration);
            // easeOutCubic
            const eased = 1 - Math.pow(1 - p, 3);
            this.value = Math.round(end * eased);
            if (p < 1) {
                requestAnimationFrame(step);
            } else {
                this.value = end;
            }
        };
        requestAnimationFrame(step);
    },
    get formatted() {
        return Number(this.value).toLocaleString('en-PK', { maximumFractionDigits: 0 });
    },
}));

/**
 * Fires immediately if `ready`, otherwise after Alpine + a paint tick.
 * Used by chart cards so Chart.js runs once the canvas has a size.
 */
window.onChartReady = (cb) => {
    if (document.readyState === 'complete') {
        requestAnimationFrame(cb);
    } else {
        window.addEventListener('load', () => requestAnimationFrame(cb), { once: true });
    }
};

Alpine.start();
