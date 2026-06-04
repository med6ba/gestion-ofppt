import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { initNotificationsRealtime } from './realtime/notifications';
import { initAttendanceRealtime } from './realtime/attendance';
import { initTimetableRealtime } from './realtime/timetable';

window.Realtime = {
    initNotifications: initNotificationsRealtime,
    initAttendance: initAttendanceRealtime,
    initTimetable: initTimetableRealtime,
};
import {
    BarController,
    BarElement,
    CategoryScale,
    Chart,
    DoughnutController,
    ArcElement,
    Filler,
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
    Filler,
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
Alpine.data('visibleBadgeDownloader', (options = {}) => ({
    downloading: false,
    error: '',

    async download() {
        if (this.downloading) {
            return;
        }

        const badge = this.$refs.badge;

        if (!badge) {
            this.error = options.error || 'Download unavailable.';

            return;
        }

        this.downloading = true;
        this.error = '';

        try {
            if (document.fonts?.ready) {
                await document.fonts.ready;
            }

            const svg = await this.renderVisibleSvg(badge);

            try {
                const canvas = await this.renderSvgToCanvas(svg);
                this.downloadUrl(canvas.toDataURL('image/png'), options.filename || 'badge.png');
            } catch {
                this.downloadBlob(svg.blob, this.svgFilename(options.filename || 'badge.png'));
            }
        } catch {
            this.error = options.error || 'Download unavailable.';
        } finally {
            this.downloading = false;
        }
    },

    async renderVisibleSvg(element) {
        const rect = element.getBoundingClientRect();
        const width = Math.ceil(rect.width);
        const height = Math.ceil(rect.height);
        const scale = Math.max(2, window.devicePixelRatio || 1);
        const clone = element.cloneNode(true);

        clone.setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
        clone.style.margin = '0';
        clone.style.transform = 'none';
        clone.style.width = `${width}px`;
        clone.style.height = `${height}px`;
        clone.style.maxWidth = 'none';

        await this.inlineImages(element, clone);
        this.inlineComputedStyles(element, clone);

        const html = new XMLSerializer().serializeToString(clone);
        const svg = `
            <svg xmlns="http://www.w3.org/2000/svg" width="${width * scale}" height="${height * scale}" viewBox="0 0 ${width} ${height}">
                <foreignObject width="100%" height="100%">${html}</foreignObject>
            </svg>
        `;

        return {
            blob: new Blob([svg], { type: 'image/svg+xml;charset=utf-8' }),
            height,
            scale,
            width,
        };
    },

    async renderSvgToCanvas(svg) {
        const url = URL.createObjectURL(svg.blob);

        try {
            const image = await this.loadImage(url);
            const canvas = document.createElement('canvas');
            canvas.width = svg.width * svg.scale;
            canvas.height = svg.height * svg.scale;

            const context = canvas.getContext('2d');
            context.scale(svg.scale, svg.scale);
            context.drawImage(image, 0, 0, svg.width, svg.height);

            return canvas;
        } finally {
            URL.revokeObjectURL(url);
        }
    },

    inlineComputedStyles(source, target) {
        const sourceElements = [source, ...source.querySelectorAll('*')];
        const targetElements = [target, ...target.querySelectorAll('*')];

        sourceElements.forEach((sourceElement, index) => {
            const targetElement = targetElements[index];

            if (!targetElement) {
                return;
            }

            const computed = window.getComputedStyle(sourceElement);
            const style = Array.from(computed)
                .map((property) => `${property}:${computed.getPropertyValue(property)};`)
                .join('');

            targetElement.setAttribute('style', `${style}${targetElement.getAttribute('style') || ''}`);
        });
    },

    async inlineImages(source, target) {
        const sourceImages = Array.from(source.querySelectorAll('img'));
        const targetImages = Array.from(target.querySelectorAll('img'));

        await Promise.all(
            sourceImages.map(async (image, index) => {
                const targetImage = targetImages[index];

                if (!targetImage) {
                    return;
                }

                await this.waitForImage(image);

                try {
                    targetImage.src = await this.imageToDataUrl(image);
                    targetImage.removeAttribute('srcset');
                } catch {
                    targetImage.src = image.currentSrc || image.src;
                }
            }),
        );
    },

    waitForImage(image) {
        if (image.complete) {
            return Promise.resolve();
        }

        return new Promise((resolve) => {
            image.addEventListener('load', resolve, { once: true });
            image.addEventListener('error', resolve, { once: true });
        });
    },

    async imageToDataUrl(image) {
        if (image.src.startsWith('data:')) {
            return image.src;
        }

        const response = await fetch(image.currentSrc || image.src);
        const blob = await response.blob();

        return await new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onloadend = () => resolve(reader.result);
            reader.onerror = reject;
            reader.readAsDataURL(blob);
        });
    },

    loadImage(src) {
        return new Promise((resolve, reject) => {
            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = reject;
            image.src = src;
        });
    },

    downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);

        this.downloadUrl(url, filename);
        window.setTimeout(() => URL.revokeObjectURL(url), 1000);
    },

    downloadUrl(url, filename) {
        const link = document.createElement('a');
        link.download = filename;
        link.href = url;
        document.body.append(link);
        link.click();
        link.remove();
    },

    svgFilename(filename) {
        return filename.replace(/\.[^.]+$/, '.svg');
    },
}));
Alpine.start();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {});
    });
}
