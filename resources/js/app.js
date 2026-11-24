import './bootstrap';
import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import focus from '@alpinejs/focus';

const rupiah = (n) => 'Rp' + new Intl.NumberFormat('id-ID').format(Math.max(0, Math.floor(Number(n) || 0)));
const digits = (v) => parseInt(String(v ?? '').replace(/\D+/g, ''), 10) || 0;
const reducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;

/* Banner carousel: scroll-snap + dots + gentle autoplay (off for reduced motion). */
Alpine.data('carousel', (count = 1, autoPlay = true, adaptiveHeight = false) => ({
    index: 0,
    count,
    timer: null,
    resizeObserver: null,
    init() {
        if (autoPlay && this.count > 1 && !reducedMotion()) {
            this.timer = setInterval(() => this.go((this.index + 1) % this.count), 5000);
        }
        if (adaptiveHeight) this.$nextTick(() => {
            this.resizeObserver = new ResizeObserver(() => this.syncHeight());
            [...this.$refs.track.children].forEach(slide => this.resizeObserver.observe(slide));
            this.syncHeight();
        });
    },
    onScroll() {
        const el = this.$refs.track;
        this.index = Math.max(0, Math.min(this.count - 1, Math.round(el.scrollLeft / el.clientWidth)));
        this.syncHeight();
    },
    go(i) {
        const target = Math.max(0, Math.min(this.count - 1, i));
        this.$refs.track.scrollTo({ left: target * this.$refs.track.clientWidth, behavior: reducedMotion() ? 'auto' : 'smooth' });
    },
    pause() {
        clearInterval(this.timer);
    },
    syncHeight() {
        if (!adaptiveHeight) return;
        const slide = this.$refs.track.children[this.index];
        if (slide) this.$refs.track.style.height = `${slide.getBoundingClientRect().height}px`;
    },
    destroy() {
        this.pause();
        this.resizeObserver?.disconnect();
    },
}));

/* Quick sedekah widget on home. */
Alpine.data('quickDonate', (initial = 25000, min = 10000) => ({
    amount: initial,
    custom: '',
    min,
    pick(v) {
        this.amount = v;
        this.custom = '';
    },
    onCustom(e) {
        this.amount = digits(e.target.value);
        this.custom = this.amount ? new Intl.NumberFormat('id-ID').format(this.amount) : '';
    },
    get label() {
        return rupiah(this.amount);
    },
    get valid() {
        return this.amount >= this.min;
    },
}));

/* Infinite scroll / load more: server returns partial HTML + X-Next-Page header. */
Alpine.data('loadMore', (nextUrl = null) => ({
    next: nextUrl,
    loading: false,
    error: false,
    observer: null,
    init() {
        if (!('IntersectionObserver' in window) || !this.$refs.sentinel) return;
        this.observer = new IntersectionObserver((entries) => {
            if (entries[0].isIntersecting) this.load();
        }, { rootMargin: '200px' });
        this.observer.observe(this.$refs.sentinel);
    },
    async load() {
        if (!this.next || this.loading) return;
        this.loading = true;
        this.error = false;
        try {
            const res = await fetch(this.next, { headers: { 'X-Partial': '1', Accept: 'text/html' } });
            if (!res.ok) throw new Error(res.status);
            this.$refs.list.insertAdjacentHTML('beforeend', await res.text());
            this.next = res.headers.get('X-Next-Page') || null;
            if (!this.next && this.observer) this.observer.disconnect();
        } catch (e) {
            this.error = true;
        } finally {
            this.loading = false;
        }
    },
}));

/* Native share sheet with WhatsApp / copy fallback. */
Alpine.data('share', (url, text) => ({
    open: false,
    copied: false,
    async share() {
        if (navigator.share) {
            try {
                await navigator.share({ title: document.title, text, url });
                return;
            } catch (e) {
                if (e.name === 'AbortError') return;
            }
        }
        this.open = true;
    },
    get waUrl() {
        return 'https://wa.me/?text=' + encodeURIComponent(text + '\n' + url);
    },
    async copy() {
        try {
            await navigator.clipboard.writeText(url);
            this.copied = true;
            setTimeout(() => (this.copied = false), 2000);
        } catch (e) {
            /* ignore */
        }
    },
}));

/* Donation form → POST JSON → Midtrans Snap popup. */
Alpine.data('donationForm', (config) => ({
    amount: config.initial || 0,
    custom: config.initial && !config.presets.includes(config.initial) ? new Intl.NumberFormat('id-ID').format(config.initial) : '',
    anonymous: false,
    name: '',
    phone: '',
    prayer: '',
    loading: false,
    errors: {},
    message: '',
    min: config.min,
    max: config.max,
    pick(v) {
        this.amount = v;
        this.custom = '';
        delete this.errors.amount;
    },
    onCustom(e) {
        this.amount = digits(e.target.value);
        this.custom = this.amount ? new Intl.NumberFormat('id-ID').format(this.amount) : '';
        delete this.errors.amount;
    },
    get payLabel() {
        return this.amount ? 'Bayar ' + rupiah(this.amount) : 'Pilih nominal dulu';
    },
    get step() {
        if (this.amount < this.min) return 1;
        if (!this.anonymous && !this.name.trim()) return 2;
        if (digits(this.phone).toString().length < 9) return 3;
        return 4;
    },
    validate() {
        const e = {};
        if (this.amount < this.min) e.amount = 'Minimal donasi ' + rupiah(this.min) + '.';
        if (this.amount > this.max) e.amount = 'Maksimal donasi ' + rupiah(this.max) + '.';
        if (!this.anonymous && !this.name.trim()) e.donor_name = 'Isi nama, atau aktifkan "Sembunyikan nama".';
        if (digits(this.phone).toString().length < 9) e.phone = 'Isi nomor WhatsApp yang aktif.';
        this.errors = e;
        return Object.keys(e).length === 0;
    },
    async submit() {
        this.message = '';
        if (!this.validate()) {
            this.$nextTick(() => this.$root.querySelector('[aria-invalid="true"]')?.focus());
            return;
        }
        this.loading = true;
        try {
            const res = await fetch(config.action, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({
                    campaign: config.campaign,
                    amount: this.amount,
                    donor_name: this.name,
                    is_anonymous: this.anonymous,
                    phone: this.phone,
                    prayer: this.prayer,
                    website: this.$refs.hp?.value || '',
                }),
            });
            const data = await res.json().catch(() => ({}));
            if (res.status === 422) {
                this.errors = Object.fromEntries(Object.entries(data.errors || {}).map(([k, v]) => [k, v[0]]));
                this.message = data.message && !Object.keys(this.errors).length ? data.message : '';
                return;
            }
            if (res.status === 429) {
                this.message = 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.';
                return;
            }
            if (!res.ok) {
                this.message = data.message || 'Pembayaran belum bisa diproses. Coba lagi sebentar.';
                return;
            }
            this.pay(data);
        } catch (e) {
            this.message = 'Koneksi terputus. Periksa internet Anda lalu coba lagi.';
        } finally {
            this.loading = false;
        }
    },
    pay(data) {
        const go = () => (window.location.href = data.status_url);
        if (!window.snap) {
            window.location.href = data.redirect_url;
            return;
        }
        window.snap.pay(data.snap_token, { onSuccess: go, onPending: go, onError: go, onClose: go });
    },
}));

/* Pending status page: poll DB-backed status (webhook is the source of truth). */
Alpine.data('statusPoller', (url, status) => ({
    status,
    tries: 0,
    init() {
        if (this.status !== 'pending') return;
        const tick = async () => {
            this.tries++;
            try {
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (data.status !== this.status) {
                    window.location.reload();
                    return;
                }
            } catch (e) {
                /* keep polling */
            }
            if (this.tries < 120) setTimeout(tick, this.tries < 12 ? 5000 : 15000);
        };
        setTimeout(tick, 5000);
    },
}));

/* Resume Snap from status page. */
Alpine.data('snapResume', (token, statusUrl) => ({
    pay() {
        if (!window.snap) return;
        const go = () => (window.location.href = statusUrl);
        window.snap.pay(token, { onSuccess: go, onPending: go, onError: go, onClose: go });
    },
}));

window.Alpine = Alpine;
Alpine.plugin(collapse);
Alpine.plugin(focus);
Alpine.start();
