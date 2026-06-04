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
Alpine.data('qrBadgeLoginScanner', (messages = {}) => ({
    messages: {
        opening: 'Ouverture camera...',
        active: 'Camera active',
        detected: 'Badge detecte',
        invalid: 'QR badge invalide.',
        permission: 'Camera permission refused.',
        noCamera: 'No camera found.',
        unavailable: 'Camera unavailable. Check browser permission.',
        ...messages,
    },
    scanner: null,
    readerId: `badge-qr-reader-${Math.random().toString(36).slice(2)}`,
    scanning: false,
    starting: false,
    status: '',
    error: '',

    async start() {
        if (this.scanning || this.starting) {
            return;
        }

        this.error = '';
        this.status = this.messages.opening;
        this.starting = true;

        await this.$nextTick();

        try {
            const { Html5Qrcode } = await import('html5-qrcode');

            this.scanner = new Html5Qrcode(this.readerId);
            await this.scanner.start(
                { facingMode: 'environment' },
                {
                    fps: 10,
                    qrbox: (width, height) => {
                        const edge = Math.max(180, Math.min(280, Math.floor(Math.min(width, height) * 0.72)));

                        return { width: edge, height: edge };
                    },
                },
                (decodedText) => this.handleScan(decodedText),
            );

            this.scanning = true;
            this.status = this.messages.active;
        } catch (error) {
            this.error = this.cameraErrorMessage(error);
            this.status = '';
            this.scanner = null;
        } finally {
            this.starting = false;
        }
    },

    async stop() {
        if (!this.scanner) {
            this.scanning = false;
            this.starting = false;

            return;
        }

        try {
            await this.scanner.stop();
            await this.scanner.clear();
        } catch {
            // The scanner may already be stopped after a successful redirect scan.
        }

        this.scanner = null;
        this.scanning = false;
        this.starting = false;
        this.status = '';
    },

    async handleScan(decodedText) {
        const loginUrl = this.loginUrlFrom(decodedText);

        if (!loginUrl) {
            this.error = this.messages.invalid;

            return;
        }

        this.status = this.messages.detected;
        await this.stop();
        window.location.assign(loginUrl);
    },

    loginUrlFrom(value) {
        const text = String(value || '').trim();

        if (!text) {
            return null;
        }

        try {
            const url = new URL(text, window.location.origin);

            if (url.pathname.startsWith('/qr-login/')) {
                return `${window.location.origin}${url.pathname}${url.search}`;
            }
        } catch {
            // Fall through to relative path and raw-token support.
        }

        if (text.startsWith('/qr-login/')) {
            return text;
        }

        if (/^[A-Za-z0-9]{32,96}$/.test(text)) {
            return `/qr-login/${encodeURIComponent(text)}`;
        }

        return null;
    },

    cameraErrorMessage(error) {
        const message = String(error?.message || error || '');

        if (message.includes('NotAllowed') || message.includes('Permission')) {
            return this.messages.permission;
        }

        if (message.includes('NotFound')) {
            return this.messages.noCamera;
        }

        return this.messages.unavailable;
    },

    destroy() {
        this.stop();
    },
}));
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
