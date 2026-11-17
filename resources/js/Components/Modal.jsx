import { useEffect } from 'react';
import { X } from 'lucide-react';

export default function Modal({
    show = false,
    onClose,
    title = '',
    children,
    maxWidth = '2xl',
}) {
    useEffect(() => {
        const handleKeyDown = (e) => {
            if (e.key === 'Escape' && show) {
                onClose?.();
            }
        };
        if (show) {
            document.body.style.overflow = 'hidden';
            window.addEventListener('keydown', handleKeyDown);
        } else {
            document.body.style.overflow = 'unset';
        }
        return () => {
            document.body.style.overflow = 'unset';
            window.removeEventListener('keydown', handleKeyDown);
        };
    }, [show, onClose]);

    if (!show) return null;

    const maxWidthClass = {
        sm: 'max-w-sm',
        md: 'max-w-md',
        lg: 'max-w-lg',
        xl: 'max-w-xl',
        '2xl': 'max-w-2xl',
        '3xl': 'max-w-3xl',
        '4xl': 'max-w-4xl',
    }[maxWidth] || 'max-w-2xl';

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6 overflow-y-auto">
            {/* Backdrop */}
            <div
                className="fixed inset-0 bg-black/70 backdrop-blur-xs transition-opacity animate-in fade-in"
                onClick={onClose}
                aria-hidden="true"
            />

            {/* Modal Dialog */}
            <div
                className={`relative w-full ${maxWidthClass} rounded-3xl border border-border/80 dark:border-white/10 bg-surface dark:bg-[#0E131F] p-6 shadow-2xl transition-all animate-in zoom-in-95 duration-200 z-10 max-h-[90vh] flex flex-col`}
                role="dialog"
                aria-modal="true"
            >
                {/* Header */}
                <div className="flex items-center justify-between pb-4 border-b border-border/60 dark:border-white/10 shrink-0">
                    <h3 className="text-lg font-bold text-text truncate">{title}</h3>
                    <button
                        type="button"
                        onClick={onClose}
                        className="p-1.5 rounded-xl border border-border/60 hover:bg-surface-raised transition text-text-muted hover:text-text focus:outline-none"
                        aria-label="Tutup"
                    >
                        <X className="size-5" />
                    </button>
                </div>

                {/* Content */}
                <div className="mt-4 overflow-y-auto flex-1 custom-scrollbar pr-1">
                    {children}
                </div>
            </div>
        </div>
    );
}

