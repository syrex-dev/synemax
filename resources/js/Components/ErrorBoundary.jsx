import React from 'react';

export default class ErrorBoundary extends React.Component {
    constructor(props) {
        super(props);
        this.state = { hasError: false, error: null };
    }

    static getDerivedStateFromError(error) {
        return { hasError: true, error };
    }

    componentDidCatch(error, errorInfo) {
        console.error('Unhandled React Error:', error, errorInfo);
    }

    render() {
        if (this.state.hasError) {
            return (
                <div className="min-h-screen bg-bg text-text flex items-center justify-center p-4">
                    <div className="max-w-md w-full rounded-3xl border border-border bg-surface p-6 sm:p-8 text-center shadow-xl space-y-4">
                        <div className="mx-auto flex size-14 items-center justify-center rounded-2xl bg-amber-500/10 text-amber-500 border border-amber-500/20">
                            <svg className="size-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <h2 className="text-lg font-black text-text">Terjadi Kendala Memuat Tampilan</h2>
                        <p className="text-xs text-text-muted leading-relaxed">
                            Terjadi kendala teknis saat memuat komponen halaman ini. Silakan muat ulang atau kembali ke halaman beranda.
                        </p>
                        <div className="pt-2 flex flex-col sm:flex-row gap-2 justify-center">
                            <button
                                type="button"
                                onClick={() => window.location.reload()}
                                className="inline-flex min-h-10 items-center justify-center rounded-xl bg-amber-500 px-4 text-xs font-bold text-slate-950 hover:bg-amber-400 transition cursor-pointer"
                            >
                                Muat Ulang Halaman
                            </button>
                            <a
                                href="/"
                                className="inline-flex min-h-10 items-center justify-center rounded-xl border border-border bg-surface-2 px-4 text-xs font-bold text-text hover:bg-surface transition cursor-pointer"
                            >
                                Ke Beranda
                            </a>
                        </div>
                    </div>
                </div>
            );
        }

        return this.props.children;
    }
}
