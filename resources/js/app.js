import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    ArcElement,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
} from 'chart.js';

Chart.register(
    ArcElement,
    BarController,
    BarElement,
    CategoryScale,
    DoughnutController,
    Legend,
    LinearScale,
    LineController,
    LineElement,
    PointElement,
    Tooltip,
);

window.Alpine = Alpine;
window.Chart = Chart;

Alpine.plugin(collapse);
Alpine.data('campusClock', (timezone = 'Africa/Casablanca', initialTime = '--:--:--', initialOffset = 'UTC+1') => ({
    timezone,
    time: initialTime,
    offset: initialOffset,
    timer: null,
    init() {
        this.tick();
        this.timer = window.setInterval(() => this.tick(), 1000);
    },
    destroy() {
        if (this.timer) {
            window.clearInterval(this.timer);
        }
    },
    tick() {
        const now = new Date();

        this.time = new Intl.DateTimeFormat('en-GB', {
            timeZone: this.timezone,
            hour: '2-digit',
            minute: '2-digit',
            second: '2-digit',
            hour12: false,
        }).format(now);
        this.offset = this.formattedOffset(now);
    },
    formattedOffset(date) {
        const zoneName = new Intl.DateTimeFormat('en-US', {
            timeZone: this.timezone,
            timeZoneName: 'shortOffset',
        })
            .formatToParts(date)
            .find((part) => part.type === 'timeZoneName')?.value ?? 'GMT';

        return zoneName.replace('GMT', 'UTC');
    },
}));
Alpine.start();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
