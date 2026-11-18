import { useState, useEffect } from 'react';
import { Sun, Moon } from 'lucide-react';

export default function ThemeToggle({ variant = 'responsive', className = '' }) {
    const [theme, setTheme] = useState('dark');

    useEffect(() => {
        if (typeof window !== 'undefined' && window.synemaXTheme) {
            setTheme(window.synemaXTheme.getMode() || 'dark');

            const handleThemeChange = (e) => {
                if (e.detail?.mode) {
                    setTheme(e.detail.mode);
                }
            };

            window.addEventListener('synemax-theme-changed', handleThemeChange);
            return () => window.removeEventListener('synemax-theme-changed', handleThemeChange);
        }
    }, []);

    const setMode = (mode) => {
        if (window.synemaXTheme) {
            window.synemaXTheme.setMode(mode);
        }
        setTheme(mode);
    };

    const toggle = () => {
        const next = theme === 'dark' ? 'light' : 'dark';
        setMode(next);
    };

    const renderButton = (extraClass = '') => (
        <button
            type="button"
            onClick={toggle}
            className={`size-11 inline-flex items-center justify-center rounded-xl border border-slate-300/80 dark:border-white/15 bg-surface/70 dark:bg-white/5 backdrop-blur-sm text-text hover:bg-surface-2 transition focus-visible:outline-2 focus-visible:outline-amber-500 shadow-xs ${extraClass} ${className}`}
            aria-label="Ganti mode tampilan terang atau gelap"
            title={theme === 'dark' ? 'Ganti ke mode terang' : 'Ganti ke mode gelap'}
        >
            {theme === 'light' ? (
                <Moon className="size-5 text-slate-700" />
            ) : (
                <Sun className="size-5 text-amber-400" />
            )}
        </button>
    );

    const renderPill = (extraClass = '') => (
        <div
            className={`inline-flex items-center rounded-full border border-slate-300 dark:border-white/15 bg-slate-200/80 dark:bg-black/50 p-1 backdrop-blur-md shadow-inner transition-colors duration-200 ${extraClass} ${className}`}
            role="group"
            aria-label="Pilih mode tampilan"
        >
            <span className="sr-only">Pilih tema</span>
            <button
                type="button"
                onClick={() => setMode('light')}
                className={`flex size-8 items-center justify-center rounded-full transition-all duration-200 focus:outline-none ${
                    theme === 'light'
                        ? 'text-slate-950 bg-amber-400 shadow-sm hover:scale-105'
                        : 'text-slate-400 bg-transparent hover:text-slate-200'
                }`}
                title="Aktifkan mode terang"
                aria-label="Mode Terang"
            >
                <Sun className="size-4" />
            </button>
            <button
                type="button"
                onClick={() => setMode('dark')}
                className={`flex size-8 items-center justify-center rounded-full transition-all duration-200 focus:outline-none ${
                    theme === 'dark'
                        ? 'text-amber-300 bg-amber-500/25 shadow-[0_0_12px_rgba(212,175,55,0.3)] hover:scale-105'
                        : 'text-slate-500 bg-transparent hover:text-slate-700 dark:hover:text-slate-300'
                }`}
                title="Aktifkan mode gelap"
                aria-label="Mode Gelap"
            >
                <Moon className="size-4" />
            </button>
        </div>
    );

    if (variant === 'single' || variant === 'icon-only' || variant === 'icon' || variant === 'mobile') {
        return renderButton();
    }

    if (variant === 'desktop' || variant === 'pill') {
        return renderPill();
    }

    return (
        <div className="flex items-center">
            {renderPill('hidden md:inline-flex')}
            {renderButton('inline-flex md:hidden')}
        </div>
    );
}
