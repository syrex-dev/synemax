{{-- Universal In-App Notification (Toast) & Confirmation Modal Component --}}
<div x-data="inAppDialogsComponent()" class="relative z-[9999]">
    {{-- ============================================================== --}}
    {{-- 1. TOAST NOTIFICATION CONTAINER                                 --}}
    {{-- Positioned top-right on desktop, bottom above nav on mobile    --}}
    {{-- ============================================================== --}}
    <div
        class="fixed z-[9999] flex flex-col gap-2.5 pointer-events-none transition-all duration-300
               top-4 right-4 max-w-sm w-[calc(100vw-2rem)] sm:w-96
               max-sm:top-auto max-sm:bottom-20 max-sm:inset-x-4 max-sm:max-w-none"
        aria-live="polite"
    >
        <template x-for="toast in toasts" :key="toast.id">
            <div
                class="pointer-events-auto flex items-start gap-3 rounded-2xl border p-4 shadow-xl backdrop-blur-md transition-all duration-200 transform"
                :class="{
                    'bg-emerald-950/90 dark:bg-emerald-950/95 border-emerald-500/40 text-emerald-100': toast.type === 'success',
                    'bg-red-950/90 dark:bg-red-950/95 border-red-500/40 text-red-100': toast.type === 'error',
                    'bg-amber-950/90 dark:bg-amber-950/95 border-amber-500/40 text-amber-100': toast.type === 'warning',
                    'bg-slate-900/90 dark:bg-slate-900/95 border-slate-600/40 text-slate-100': toast.type === 'info'
                }"
                :role="toast.type === 'error' ? 'alert' : 'status'"
            >
                {{-- Type Icon --}}
                <div class="shrink-0 mt-0.5">
                    <template x-if="toast.type === 'success'">
                        <svg class="size-5 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                    <template x-if="toast.type === 'error'">
                        <svg class="size-5 text-red-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="8" x2="12" y2="12" />
                            <line x1="12" y1="16" x2="12.01" y2="16" />
                        </svg>
                    </template>
                    <template x-if="toast.type === 'warning'">
                        <svg class="size-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </template>
                    <template x-if="toast.type === 'info'">
                        <svg class="size-5 text-sky-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                            <circle cx="12" cy="12" r="10" />
                            <line x1="12" y1="16" x2="12" y2="12" />
                            <line x1="12" y1="8" x2="12.01" y2="8" />
                        </svg>
                    </template>
                </div>

                {{-- Toast Message Text --}}
                <div class="min-w-0 flex-1 text-xs sm:text-sm font-semibold leading-snug" x-text="toast.message"></div>

                {{-- Close Button --}}
                <button
                    type="button"
                    @click="removeToast(toast.id)"
                    class="shrink-0 -mr-1 -mt-1 p-1 rounded-lg text-white/60 hover:text-white hover:bg-white/10 transition"
                    aria-label="Tutup pemberitahuan"
                >
                    <svg class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </template>
    </div>

    {{-- ============================================================== --}}
    {{-- 2. UNIVERSAL IN-APP CONFIRMATION MODAL                          --}}
    {{-- ============================================================== --}}
    <div
        x-show="modalOpen"
        x-cloak
        class="fixed inset-0 z-[10000] flex items-center justify-center p-4 sm:p-6"
        role="dialog"
        aria-modal="true"
        :aria-label="modalTitle"
        @keydown.escape.window="closeConfirm()"
    >
        {{-- Backdrop --}}
        <div
            x-show="modalOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="closeConfirm()"
            class="fixed inset-0 bg-black/75 backdrop-blur-sm"
        ></div>

        {{-- Modal Content Card --}}
        <div
            x-show="modalOpen"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95"
            @click.stop
            class="relative w-full max-w-md rounded-3xl border border-border bg-surface p-6 shadow-2xl space-y-4"
        >
            <div class="flex items-start gap-3">
                <div class="grid size-10 shrink-0 place-items-center rounded-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
                    <svg class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <h3 class="text-base sm:text-lg font-black text-text tracking-tight" x-text="modalTitle"></h3>
                    <p class="mt-1 text-xs sm:text-sm text-text-muted leading-relaxed" x-text="modalMessage"></p>
                </div>
            </div>

            <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-border">
                <button
                    type="button"
                    x-ref="modalCancelBtn"
                    @click="closeConfirm()"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-xl border border-slate-300 dark:border-white/15 bg-surface px-4 py-2 text-xs sm:text-sm font-bold text-text hover:bg-surface-2 transition"
                    x-text="modalCancelText"
                >
                    Batal
                </button>
                <button
                    type="button"
                    @click="confirmAction()"
                    class="inline-flex min-h-[44px] items-center justify-center rounded-xl px-4 py-2 text-xs sm:text-sm font-black shadow-md transition"
                    :class="modalConfirmClass"
                    x-text="modalConfirmText"
                >
                    Konfirmasi
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================== --}}
    {{-- 3. SESSION EXPIRED (419) MODAL                                  --}}
    {{-- ============================================================== --}}
    <div
        x-show="sessionExpiredOpen"
        x-cloak
        class="fixed inset-0 z-[10001] flex items-center justify-center p-4"
        role="alertdialog"
        aria-modal="true"
        aria-label="Sesi Habis"
    >
        <div class="fixed inset-0 bg-black/80 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-sm rounded-3xl border border-border bg-surface p-6 shadow-2xl text-center space-y-4">
            <div class="mx-auto grid size-12 place-items-center rounded-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
                <svg class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <h3 class="text-lg font-black text-text">Sesi Telah Berakhir</h3>
                <p class="mt-1 text-xs text-text-muted leading-relaxed">Sesi keamanan Anda telah habis (419). Silakan muat ulang halaman untuk memperbarui token keamanan.</p>
            </div>
            <button
                type="button"
                @click="window.location.reload()"
                class="w-full inline-flex min-h-[44px] items-center justify-center rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-sm shadow-md transition"
            >
                Muat Ulang Halaman
            </button>
        </div>
    </div>
</div>

<script>
function inAppDialogsComponent() {
    return {
        toasts: [],
        addToast(type, message, duration) {
            const id = Date.now() + Math.random().toString(36).substr(2, 4);
            const autoDismiss = duration !== undefined ? duration : (type === 'error' ? 8000 : 4000);
            
            if (this.toasts.length >= 3) {
                this.toasts.shift();
            }

            const toast = { id, type, message };
            this.toasts.push(toast);

            if (autoDismiss > 0) {
                setTimeout(() => {
                    this.removeToast(id);
                }, autoDismiss);
            }
        },
        removeToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },

        modalOpen: false,
        modalTitle: '',
        modalMessage: '',
        modalConfirmText: 'Lanjutkan',
        modalConfirmClass: 'bg-danger text-white hover:bg-danger/90',
        modalCancelText: 'Batal',
        modalCallback: null,

        openConfirm(config) {
            this.modalTitle = config.title || 'Konfirmasi Tindakan';
            this.modalMessage = config.message || 'Apakah Anda yakin ingin melanjutkan tindakan ini?';
            this.modalConfirmText = config.confirmText || 'Lanjutkan';
            this.modalConfirmClass = config.confirmClass || 'bg-danger text-white hover:bg-danger/90';
            this.modalCancelText = config.cancelText || 'Batal';
            this.modalCallback = typeof config.onConfirm === 'function' ? config.onConfirm : null;
            this.modalOpen = true;

            this.$nextTick(() => {
                const cancelBtn = this.$refs.modalCancelBtn;
                if (cancelBtn) cancelBtn.focus();
            });
        },
        closeConfirm() {
            this.modalOpen = false;
            this.modalCallback = null;
        },
        confirmAction() {
            const cb = this.modalCallback;
            this.closeConfirm();
            if (cb) {
                cb();
            }
        },

        sessionExpiredOpen: false,

        init() {
            window.showToast = (config) => {
                const type = typeof config === 'string' ? 'info' : (config.type || 'info');
                const message = typeof config === 'string' ? config : (config.message || '');
                const duration = config.duration;
                this.addToast(type, message, duration);
            };

            window.openConfirmModal = (config) => {
                this.openConfirm(config);
            };

            window.copyToClipboard = async (text, message = 'Tersalin ke papan klip') => {
                try {
                    await navigator.clipboard.writeText(text);
                    this.addToast('success', message, 3000);
                    return true;
                } catch (e) {
                    this.addToast('error', 'Gagal menyalin secara otomatis.', 4000);
                    return false;
                }
            };

            window.addEventListener('session-expired', () => {
                this.sessionExpiredOpen = true;
            });

            @if (session('status'))
                this.addToast('success', @js((string) session('status')));
            @endif
            @if (session('error'))
                this.addToast('error', @js((string) session('error')));
            @endif
            @if (session('warning'))
                this.addToast('warning', @js((string) session('warning')));
            @endif
            @if (session('info'))
                this.addToast('info', @js((string) session('info')));
            @endif

            document.addEventListener('submit', (e) => {
                const form = e.target;
                if (!form || !form.hasAttribute('data-confirm')) return;
                if (form.dataset.confirmed === 'true') return;

                e.preventDefault();
                const title = form.getAttribute('data-confirm-title') || 'Konfirmasi';
                const message = form.getAttribute('data-confirm');
                const confirmText = form.getAttribute('data-confirm-action') || 'Lanjutkan';
                const confirmClass = form.getAttribute('data-confirm-class') || 'bg-danger text-white hover:bg-danger/90';

                this.openConfirm({
                    title,
                    message,
                    confirmText,
                    confirmClass,
                    onConfirm: () => {
                        form.dataset.confirmed = 'true';
                        form.submit();
                    }
                });
            });

            document.addEventListener('click', (e) => {
                const link = e.target.closest('a');
                if (!link || !link.href || link.href.startsWith('#') || link.href.startsWith('javascript:')) return;
                if (link.target === '_blank') return;

                const dirtyForm = document.querySelector('form[data-track-dirty="true"].is-dirty');
                if (dirtyForm) {
                    e.preventDefault();
                    this.openConfirm({
                        title: 'Perubahan Belum Disimpan',
                        message: 'Ada perubahan pada formulir yang belum disimpan. Yakin ingin meninggalkan halaman ini?',
                        confirmText: 'Tinggalkan Halaman',
                        confirmClass: 'bg-danger text-white hover:bg-danger/90',
                        cancelText: 'Tetap di Sini',
                        onConfirm: () => {
                            dirtyForm.classList.remove('is-dirty');
                            window.location.href = link.href;
                        }
                    });
                }
            });

            document.addEventListener('input', (e) => {
                const form = e.target.closest('form[data-track-dirty="true"]');
                if (form) {
                    form.classList.add('is-dirty');
                }
            });

            const originalFetch = window.fetch;
            window.fetch = async (...args) => {
                try {
                    const response = await originalFetch(...args);
                    if (response.status === 419) {
                        this.sessionExpiredOpen = true;
                    }
                    return response;
                } catch (err) {
                    this.addToast('error', 'Koneksi jaringan bermasalah. Periksa koneksi internet Anda.', 5000);
                    throw err;
                }
            };
        }
    };
}
</script>
