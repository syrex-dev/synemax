import { useState, useEffect } from 'react';
import { usePage } from '@inertiajs/react';
import { CheckCircle2, AlertCircle, AlertTriangle, Info, X } from 'lucide-react';

export default function FlashMessage() {
    const { flash } = usePage().props;
    const [visible, setVisible] = useState(false);
    const [currentFlash, setCurrentFlash] = useState(null);

    useEffect(() => {
        if (flash?.success) {
            setCurrentFlash({ type: 'success', text: flash.success });
            setVisible(true);
        } else if (flash?.error) {
            setCurrentFlash({ type: 'error', text: flash.error });
            setVisible(true);
        } else if (flash?.warning) {
            setCurrentFlash({ type: 'warning', text: flash.warning });
            setVisible(true);
        } else if (flash?.info) {
            setCurrentFlash({ type: 'info', text: flash.info });
            setVisible(true);
        }
    }, [flash]);

    useEffect(() => {
        if (visible) {
            const timer = setTimeout(() => {
                setVisible(false);
            }, 5000);
            return () => clearTimeout(timer);
        }
    }, [visible]);

    if (!visible || !currentFlash) return null;

    const config = {
        success: {
            bg: 'bg-emerald-50 dark:bg-emerald-950/80 border-emerald-500/30 text-emerald-900 dark:text-emerald-200',
            icon: <CheckCircle2 className="size-5 text-emerald-600 dark:text-emerald-400 shrink-0" />,
        },
        error: {
            bg: 'bg-red-50 dark:bg-red-950/80 border-red-500/30 text-red-900 dark:text-red-200',
            icon: <AlertCircle className="size-5 text-red-600 dark:text-red-400 shrink-0" />,
        },
        warning: {
            bg: 'bg-amber-50 dark:bg-amber-950/80 border-amber-500/30 text-amber-900 dark:text-amber-200',
            icon: <AlertTriangle className="size-5 text-amber-600 dark:text-amber-400 shrink-0" />,
        },
        info: {
            bg: 'bg-sky-50 dark:bg-sky-950/80 border-sky-500/30 text-sky-900 dark:text-sky-200',
            icon: <Info className="size-5 text-sky-600 dark:text-sky-400 shrink-0" />,
        },
    }[currentFlash.type] || {
        bg: 'bg-surface border-border text-text',
        icon: null,
    };

    return (
        <div className="fixed top-5 right-5 z-50 max-w-md animate-in slide-in-from-top-4 duration-200 shadow-xl">
            <div
                className={`flex items-start gap-3 p-4 rounded-2xl border backdrop-blur-md ${config.bg}`}
                role="alert"
            >
                {config.icon}
                <div className="flex-1 text-sm font-semibold">{currentFlash.text}</div>
                <button
                    type="button"
                    onClick={() => setVisible(false)}
                    className="p-1 rounded-lg hover:opacity-70 transition focus:outline-none"
                    aria-label="Tutup notifikasi"
                >
                    <X className="size-4" />
                </button>
            </div>
        </div>
    );
}
