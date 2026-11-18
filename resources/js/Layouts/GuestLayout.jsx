import { Link } from '@inertiajs/react';
import BrandLogo from '../Components/BrandLogo';
import ThemeToggle from '../Components/ThemeToggle';

export default function GuestLayout({ children }) {
    return (
        <div className="min-h-[100dvh] bg-bg font-sans text-text antialiased flex flex-col justify-between">
            {/* Theme Toggle Top Right */}
            <div
                className="fixed right-3 top-3 z-10"
                style={{
                    paddingTop: 'env(safe-area-inset-top)',
                    paddingRight: 'env(safe-area-inset-right)',
                }}
            >
                <ThemeToggle variant="icon-only" />
            </div>

            {/* Main Center Container */}
            <div
                className="flex flex-1 flex-col items-center justify-center px-4 py-8"
                style={{
                    paddingTop: 'max(env(safe-area-inset-top), 2rem)',
                    paddingBottom: 'max(env(safe-area-inset-bottom), 2rem)',
                }}
            >
                <Link href="/" className="mb-6 inline-flex items-center focus:outline-none hover:opacity-90 transition">
                    <BrandLogo size="default" />
                </Link>

                <div className="w-full max-w-[420px] rounded-2xl border border-border bg-surface p-6 sm:p-8 shadow-sm">
                    {children}
                </div>

                <Link
                    href="/"
                    className="mt-6 text-xs font-semibold text-text-muted hover:text-text transition-colors"
                >
                    ← Kembali ke halaman pelanggan
                </Link>
            </div>
        </div>
    );
}
