import { useState, useRef, useEffect } from 'react';
import { Search, ChevronDown, Check, X } from 'lucide-react';

export default function SearchableSelect({
    options = [], // [{ value: 1, label: 'Film Title', subtitle: '120 min', ... }]
    value = null, // single value or array if multiple
    onChange,
    placeholder = 'Pilih opsi...',
    searchPlaceholder = 'Cari...',
    multiple = false,
    disabled = false,
    className = '',
}) {
    const [isOpen, setIsOpen] = useState(false);
    const [search, setSearch] = useState('');
    const containerRef = useRef(null);
    const searchInputRef = useRef(null);

    // Close on click outside
    useEffect(() => {
        const handleClickOutside = (e) => {
            if (containerRef.current && !containerRef.current.contains(e.target)) {
                setIsOpen(false);
            }
        };
        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    // Focus input when opened
    useEffect(() => {
        if (isOpen && searchInputRef.current) {
            searchInputRef.current.focus();
        }
    }, [isOpen]);

    const filteredOptions = options.filter((opt) => {
        const label = String(opt.label || '').toLowerCase();
        const subtitle = String(opt.subtitle || '').toLowerCase();
        const q = search.toLowerCase();
        return label.includes(q) || subtitle.includes(q);
    });

    const isSelected = (val) => {
        if (multiple) {
            return Array.isArray(value) && value.includes(val);
        }
        return value === val;
    };

    const handleSelect = (optVal) => {
        if (multiple) {
            const current = Array.isArray(value) ? [...value] : [];
            const idx = current.indexOf(optVal);
            if (idx > -1) {
                current.splice(idx, 1);
            } else {
                current.push(optVal);
            }
            onChange(current);
        } else {
            onChange(optVal);
            setIsOpen(false);
            setSearch('');
        }
    };

    const handleRemoveTag = (e, val) => {
        e.stopPropagation();
        if (multiple && Array.isArray(value)) {
            onChange(value.filter((v) => v !== val));
        }
    };

    // Label resolution
    const selectedOption = !multiple ? options.find((o) => o.value === value) : null;
    const selectedOptions = multiple && Array.isArray(value) ? options.filter((o) => value.includes(o.value)) : [];

    return (
        <div ref={containerRef} className={`relative w-full ${isOpen ? 'z-30' : ''} ${className}`}>
            <div
                role="combobox"
                aria-expanded={isOpen}
                aria-haspopup="listbox"
                tabIndex={disabled ? -1 : 0}
                onClick={() => !disabled && setIsOpen(!isOpen)}
                onKeyDown={(e) => {
                    if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        if (!disabled) setIsOpen(!isOpen);
                    } else if (e.key === 'Escape') {
                        setIsOpen(false);
                    }
                }}
                className={`w-full min-h-[44px] flex items-center justify-between gap-2 px-3.5 py-2 rounded-xl border bg-surface text-text cursor-pointer transition select-none ${
                    disabled
                        ? 'opacity-60 cursor-not-allowed bg-surface-raised border-border/60'
                        : isOpen
                        ? 'border-amber-500 ring-2 ring-amber-500/20'
                        : 'border-border hover:border-amber-500/60'
                }`}
            >
                <div className="flex flex-wrap items-center gap-1.5 flex-1 min-w-0">
                    {multiple ? (
                        selectedOptions.length > 0 ? (
                            selectedOptions.map((opt) => (
                                <span
                                    key={opt.value}
                                    className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-lg bg-amber-500/15 border border-amber-500/30 text-amber-900 dark:text-amber-300 text-xs font-bold"
                                >
                                    <span className="truncate max-w-[140px]">{opt.label}</span>
                                    <button
                                        type="button"
                                        onClick={(e) => handleRemoveTag(e, opt.value)}
                                        className="hover:text-amber-600 focus:outline-none"
                                    >
                                        <X className="size-3" />
                                    </button>
                                </span>
                            ))
                        ) : (
                            <span className="text-text-muted text-sm">{placeholder}</span>
                        )
                    ) : selectedOption ? (
                        <div className="flex flex-col truncate">
                            <span className="text-sm font-semibold truncate">{selectedOption.label}</span>
                            {selectedOption.subtitle && (
                                <span className="text-xs text-text-muted truncate">{selectedOption.subtitle}</span>
                            )}
                        </div>
                    ) : (
                        <span className="text-text-muted text-sm">{placeholder}</span>
                    )}
                </div>

                <div className="flex items-center gap-1 shrink-0 text-text-muted">
                    {!multiple && value && !disabled && (
                        <button
                            type="button"
                            onClick={(e) => {
                                e.stopPropagation();
                                onChange(null);
                            }}
                            className="p-1 hover:text-text rounded-md focus:outline-none"
                        >
                            <X className="size-3.5" />
                        </button>
                    )}
                    <ChevronDown className={`size-4 transition-transform ${isOpen ? 'rotate-180 text-amber-500' : ''}`} />
                </div>
            </div>

            {/* Dropdown Menu */}
            {isOpen && (
                <div className="absolute z-50 mt-1.5 w-full rounded-2xl border border-border/80 dark:border-white/10 bg-surface dark:bg-[#0E131F] shadow-[0_10px_38px_rgba(0,0,0,0.35),0_10px_20px_rgba(0,0,0,0.2)] p-2 animate-in fade-in zoom-in-95 duration-100">
                    {/* Live Search Input */}
                    <div className="relative mb-2">
                        <Search className="absolute left-3 top-1/2 -translate-y-1/2 size-4 text-text-muted" />
                        <input
                            ref={searchInputRef}
                            type="text"
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder={searchPlaceholder}
                            className="w-full h-10 pl-9 pr-3 rounded-xl border border-border/80 bg-surface-raised dark:bg-black/30 text-text text-sm focus:outline-none focus:border-amber-500 focus:ring-1 focus:ring-amber-500 transition"
                            onClick={(e) => e.stopPropagation()}
                            onKeyDown={(e) => {
                                if (e.key === 'Escape') {
                                    setIsOpen(false);
                                }
                            }}
                        />
                        {search && (
                            <button
                                type="button"
                                onClick={() => setSearch('')}
                                className="absolute right-2.5 top-1/2 -translate-y-1/2 p-1 text-text-muted hover:text-text"
                            >
                                <X className="size-3.5" />
                            </button>
                        )}
                    </div>

                    {/* Options list */}
                    <ul
                        role="listbox"
                        className="max-h-60 overflow-y-auto space-y-1 pr-1 custom-scrollbar text-sm"
                    >
                        {filteredOptions.length > 0 ? (
                            filteredOptions.map((opt) => {
                                const active = isSelected(opt.value);
                                return (
                                    <li
                                        key={opt.value}
                                        role="option"
                                        aria-selected={active}
                                        onClick={() => handleSelect(opt.value)}
                                        className={`flex items-center justify-between gap-2 px-3 py-2.5 rounded-xl cursor-pointer transition select-none ${
                                            active
                                                ? 'bg-amber-500/15 text-amber-900 dark:text-amber-300 font-bold border border-amber-500/30'
                                                : 'text-text hover:bg-surface-raised dark:hover:bg-white/5'
                                        }`}
                                    >
                                        <div className="flex flex-col min-w-0">
                                            <span className="truncate font-medium">{opt.label}</span>
                                            {opt.subtitle && (
                                                <span className="text-xs text-text-muted truncate">{opt.subtitle}</span>
                                            )}
                                        </div>
                                        {active && <Check className="size-4 shrink-0 text-amber-500" />}
                                    </li>
                                );
                            })
                        ) : (
                            <li className="px-3 py-6 text-center text-text-muted text-xs">
                                Tidak ada opsi yang cocok dengan "{search}"
                            </li>
                        )}
                    </ul>
                </div>
            )}
        </div>
    );
}

