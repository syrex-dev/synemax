import { useState, useEffect, useRef } from 'react';
import {
    MessageSquare,
    MessageCircle,
    X,
    Send,
    RotateCcw,
    ExternalLink,
    ShieldCheck,
    Headphones,
    PhoneCall,
    Search,
    AlertCircle,
    ArrowLeft,
    ChevronRight,
    Utensils,
    Film,
} from 'lucide-react';

function getQuickReplyIcon(label, action, url) {
    if (url) {
        return <ExternalLink className="size-3 text-amber-500 shrink-0" />;
    }
    const act = (action || '').toLowerCase();
    const lbl = (label || '').toLowerCase();

    if (act.includes('check_booking') || lbl.includes('cek') || lbl.includes('booking') || lbl.includes('tiket')) {
        return <Search className="size-3 text-amber-500 shrink-0" />;
    }
    if (act.includes('snacks') || lbl.includes('jajan') || lbl.includes('menu')) {
        return <Utensils className="size-3 text-amber-500 shrink-0" />;
    }
    if (act.includes('issue') || act.includes('report') || lbl.includes('lapor') || lbl.includes('kendala') || lbl.includes('keluhan')) {
        return <AlertCircle className="size-3 text-rose-500 shrink-0" />;
    }
    if (act.includes('info') || lbl.includes('lokasi') || lbl.includes('studio') || lbl.includes('fasilitas')) {
        return <Film className="size-3 text-sky-500 shrink-0" />;
    }
    if (act.includes('feedback') || lbl.includes('pesan') || lbl.includes('admin') || lbl.includes('masukan') || lbl.includes('usulan')) {
        return <MessageSquare className="size-3 text-amber-500 shrink-0" />;
    }
    if (act.includes('main') || lbl.includes('kembali')) {
        return <ArrowLeft className="size-3 text-text-muted shrink-0" />;
    }
    return <ChevronRight className="size-3 text-text-muted shrink-0" />;
}

export default function FloatingChatWidget({ cinemaSettings = {} }) {
    const [isOpen, setIsOpen] = useState(false);
    const [inputMessage, setInputMessage] = useState('');
    const [isTyping, setIsTyping] = useState(false);
    const [hasUnread, setHasUnread] = useState(false);
    const [sessionId, setSessionId] = useState('');
    const [isBubbleDismissed, setIsBubbleDismissed] = useState(false);

    const messagesEndRef = useRef(null);
    const inputRef = useRef(null);

    const cinemaName = cinemaSettings?.siteName || 'SynemaX';
    const cleanBot = (cinemaSettings?.telegramCsBotUsername || 'SynemaXChat_bot').replace(/^@/, '').trim();

    let cleanPhone = cinemaSettings?.contactWhatsapp ? cinemaSettings.contactWhatsapp.replace(/\D/g, '') : '';
    if (cleanPhone.startsWith('0')) {
        cleanPhone = '62' + cleanPhone.slice(1);
    }
    const hasPhone = Boolean(cleanPhone);

    const initialGreeting = {
        id: 'msg-welcome',
        sender: 'bot',
        text: `<b>Selamat datang di Layanan Bantuan ${cinemaName}</b><br><br>Kami siap membantu Anda untuk pengecekan status booking tiket, jadwal sesi bioskop, info jajanan, atau mencatat keluhan nonton Anda secara instan 24 jam.<br><br>Silakan pilih menu bantuan di bawah atau tuliskan kode booking tiket Anda:`,
        quickReplies: [
            { label: 'Cek Status Booking Tiket', action: 'menu_check_booking' },
            { label: 'Menu Jajanan Bioskop', action: 'menu_check_snacks' },
            { label: 'Lapor Kendala & Keluhan', action: 'menu_report_issue' },
            { label: 'Info Lokasi & Studio', action: 'menu_info' },
            { label: 'Kirim Masukan / Usulan', action: 'menu_feedback' },
        ],
        timestamp: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
    };

    const [messages, setMessages] = useState(() => {
        try {
            const saved = localStorage.getItem('synemax_inapp_chat_v2');
            if (saved) {
                const parsed = JSON.parse(saved);
                if (Array.isArray(parsed) && parsed.length > 0) {
                    return parsed;
                }
            }
        } catch {
            // fallback
        }
        return [initialGreeting];
    });

    // Generate or retrieve persistent anonymous session ID
    useEffect(() => {
        try {
            let sid = localStorage.getItem('synemax_chat_session_id');
            if (!sid) {
                sid = 'WEB-' + Math.random().toString(36).substring(2, 10).toUpperCase();
                localStorage.setItem('synemax_chat_session_id', sid);
            }
            setSessionId(sid);
        } catch {
            setSessionId('WEB-GUEST');
        }
    }, []);

    // Listen to custom event to open chat
    useEffect(() => {
        const handleOpenChat = () => {
            setIsOpen(true);
            setHasUnread(false);
        };
        window.addEventListener('open-synemax-chat', handleOpenChat);
        return () => window.removeEventListener('open-synemax-chat', handleOpenChat);
    }, []);

    // Save chat history to localStorage
    useEffect(() => {
        try {
            localStorage.setItem('synemax_inapp_chat_v2', JSON.stringify(messages));
        } catch {
            // ignore storage quota
        }
    }, [messages]);

    // Auto-scroll to bottom of messages
    useEffect(() => {
        if (isOpen) {
            messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
            inputRef.current?.focus();
        }
    }, [isOpen, messages, isTyping]);

    const handleSendMessage = async (textToSend, actionCode) => {
        const text = (textToSend ?? inputMessage).trim();
        if (!text && !actionCode) {
            return;
        }

        setInputMessage('');

        if (text) {
            const userMsg = {
                id: 'msg-u-' + Date.now(),
                sender: 'user',
                text: text,
                timestamp: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
            };
            setMessages((prev) => [...prev, userMsg]);
        }

        setIsTyping(true);

        try {
            const res = await fetch('/api/chat-bot/message', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    message: text || undefined,
                    action: actionCode || undefined,
                    session_id: sessionId,
                    sender_name: 'Penonton Web',
                }),
            });

            if (!res.ok) {
                throw new Error('Gagal menghubungi layanan bantuan');
            }

            const data = await res.json();

            setTimeout(() => {
                const botMsg = {
                    id: 'msg-b-' + Date.now(),
                    sender: 'bot',
                    text: data.reply || 'Pesan Anda telah diterima.',
                    quickReplies: data.quick_replies,
                    timestamp: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
                };
                setMessages((prev) => [...prev, botMsg]);
                setIsTyping(false);
                if (!isOpen) {
                    setHasUnread(true);
                }
            }, 300);
        } catch {
            setTimeout(() => {
                const errorMsg = {
                    id: 'msg-err-' + Date.now(),
                    sender: 'bot',
                    text: 'Terjadi kendala jaringan saat menghubungi bot bantuan. Silakan coba kembali beberapa saat lagi.',
                    quickReplies: [
                        { label: 'Coba Lagi', action: 'menu_main' },
                    ],
                    timestamp: new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }),
                };
                setMessages((prev) => [...prev, errorMsg]);
                setIsTyping(false);
            }, 300);
        }
    };

    const handleClearChat = () => {
        if (window.confirm('Mulai ulang riwayat percakapan bantuan?')) {
            setMessages([initialGreeting]);
            try {
                localStorage.removeItem('synemax_inapp_chat_v2');
            } catch {
                // ignore
            }
        }
    };

    const botUrl = `https://t.me/${cleanBot}?start=web_cs`;
    const waUrl = hasPhone
        ? `https://wa.me/${cleanPhone}?text=${encodeURIComponent('Halo Admin SynemaX, saya butuh bantuan terkait pemesanan bioskop.')}`
        : '';

    if (cinemaSettings?.showFloatingWhatsapp === false && !isOpen) {
        return null;
    }

    return (
        <>
            {/* 1. Floating Action Pill with Mascot */}
            {!isOpen && !isBubbleDismissed && (
                <div className="fixed bottom-24 md:bottom-8 right-3 sm:right-6 z-40 select-none animate-in fade-in zoom-in-95 duration-200">
                    <div className="relative flex flex-col items-center">
                        {/* Dismiss Button */}
                        <button
                            type="button"
                            onClick={() => setIsBubbleDismissed(true)}
                            className="absolute -top-1.5 -right-1 z-20 flex size-5 items-center justify-center rounded-full bg-slate-900/80 hover:bg-slate-950 text-white/80 hover:text-white border border-white/20 shadow-md backdrop-blur-xs transition hover:scale-110"
                            title="Tutup chat Tanya Bioskop"
                            aria-label="Tutup bubble Tanya Bioskop"
                        >
                            <X className="size-3 stroke-[2.5]" />
                        </button>

                        <button
                            type="button"
                            onClick={() => {
                                setIsOpen(true);
                                setHasUnread(false);
                            }}
                            className="group flex flex-col items-center transition-all duration-300 hover:scale-105 cursor-pointer"
                            aria-label="Buka Layanan Bantuan Tanya Bioskop"
                        >
                            {/* Mascot Character on Top */}
                            <div className="relative -mb-2 z-10 transition-transform duration-300 group-hover:-translate-y-1.5 pointer-events-none">
                                <img
                                    src="/images/tanya-bioskop-mascot.png"
                                    alt="Maskot Customer Service SynemaX"
                                    className="w-14 h-14 sm:w-16 sm:h-16 object-contain drop-shadow-xl"
                                />
                                {/* Live active status indicator */}
                                <span className="absolute bottom-2 right-1 flex size-3">
                                    <span className="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75" />
                                    <span className="relative inline-flex rounded-full size-3 bg-emerald-500 border-2 border-white dark:border-[#07090E]" />
                                </span>
                            </div>

                            {/* Green / Gold Pill */}
                            <div className="relative flex items-center gap-1.5 px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-full bg-emerald-600 group-hover:bg-emerald-500 text-white font-black text-xs sm:text-sm shadow-xl shadow-emerald-950/40 border border-emerald-400/40 transition-colors">
                                <MessageCircle className="size-3.5 sm:size-4 fill-white/20 shrink-0" />
                                <span className="whitespace-nowrap tracking-wide">Tanya Bioskop</span>
                                {hasUnread && (
                                    <span className="absolute -top-1 -right-1 size-4 bg-rose-600 text-white text-[10px] font-bold rounded-full flex items-center justify-center shadow-xs">
                                        1
                                    </span>
                                )}
                            </div>
                        </button>
                    </div>
                </div>
            )}

            {/* 2. In-App Interactive Chat Window */}
            {isOpen && (
                <div className="fixed z-50 inset-x-0 bottom-0 h-[86vh] max-h-[620px] rounded-t-3xl rounded-b-none border-t border-border sm:inset-auto sm:right-6 sm:bottom-6 sm:w-[395px] sm:h-[590px] sm:rounded-2xl sm:border flex flex-col bg-surface/98 backdrop-blur-xl shadow-2xl overflow-hidden animate-in fade-in slide-in-from-bottom-4 duration-200">
                    {/* Mobile Sheet Handle */}
                    <div className="pt-2 pb-1 bg-surface-2 sm:hidden flex justify-center">
                        <div className="w-10 h-1 rounded-full bg-border" />
                    </div>

                    {/* Header */}
                    <div className="flex items-center justify-between px-4 py-3 bg-surface-2 border-b border-border select-none">
                        <div className="flex items-center gap-2.5 min-w-0">
                            <div className="relative flex items-center justify-center size-8 rounded-full bg-amber-500/15 text-amber-500 shrink-0 border border-amber-500/30">
                                <Headphones className="size-4" />
                                <span className="absolute bottom-0 right-0 size-2.5 rounded-full bg-emerald-500 ring-2 ring-surface-2" />
                            </div>
                            <div className="min-w-0">
                                <div className="flex items-center gap-1.5">
                                    <span className="text-xs font-bold text-text truncate">
                                        Layanan Bantuan Bioskop
                                    </span>
                                    <span className="text-[9px] px-2 py-0.5 rounded-full bg-amber-500/15 text-amber-600 dark:text-amber-400 font-semibold shrink-0">
                                        Online 24 Jam
                                    </span>
                                </div>
                                <p className="text-[10px] text-text-muted truncate">
                                    Pengecekan booking, menu jajan & bantuan
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-1">
                            {/* Clear conversation */}
                            <button
                                type="button"
                                onClick={handleClearChat}
                                title="Mulai Ulang Percakapan"
                                className="p-1.5 rounded-lg text-text-muted hover:text-text hover:bg-surface transition-colors cursor-pointer"
                            >
                                <RotateCcw className="size-3.5" />
                            </button>

                            {/* Close */}
                            <button
                                type="button"
                                onClick={() => setIsOpen(false)}
                                title="Tutup"
                                className="p-1.5 rounded-lg text-text-muted hover:text-text hover:bg-surface transition-colors cursor-pointer"
                            >
                                <X className="size-4" />
                            </button>
                        </div>
                    </div>

                    {/* Messages Body */}
                    <div className="flex-1 overflow-y-auto p-4 space-y-3.5 text-xs bg-bg/60">
                        <div className="text-center py-1">
                            <span className="inline-flex items-center gap-1.5 text-[10px] text-text-muted bg-surface-2 px-3 py-1 rounded-full border border-border font-medium shadow-2xs">
                                <ShieldCheck className="size-3 text-amber-500 shrink-0" />
                                <span>Layanan Mandiri SynemaX &bull; Terhubung ke Kasir</span>
                            </span>
                        </div>

                        {messages.map((msg) => (
                            <div
                                key={msg.id}
                                className={`flex flex-col ${
                                    msg.sender === 'user' ? 'items-end' : 'items-start'
                                } space-y-1.5`}
                            >
                                <div
                                    className={`max-w-[86%] rounded-2xl px-4 py-3 leading-relaxed text-xs break-words ${
                                        msg.sender === 'user'
                                            ? 'bg-amber-500 text-black font-semibold rounded-tr-xs shadow-xs'
                                            : 'bg-surface border border-border text-text rounded-tl-xs shadow-xs'
                                    }`}
                                >
                                    <div
                                        dangerouslySetInnerHTML={{ __html: msg.text }}
                                        className="[&>b]:font-semibold [&>b]:text-text [&>i]:italic [&>i]:text-text-muted [&>code]:bg-surface-2 [&>code]:px-1.5 [&>code]:py-0.5 [&>code]:rounded-md [&>code]:text-[11px] [&>code]:font-mono [&>code]:border [&>code]:border-border [&>code]:text-amber-500 [&>a]:underline [&>a]:text-amber-500 [&>a]:font-semibold hover:[&>a]:opacity-80"
                                    />
                                </div>

                                {/* Quick Reply Pills with Contextual SVG Icons */}
                                {msg.sender === 'bot' && msg.quickReplies && msg.quickReplies.length > 0 && (
                                    <div className="flex flex-wrap gap-1.5 pt-1 max-w-[96%]">
                                        {msg.quickReplies.map((qr, idx) => {
                                            if (qr.url) {
                                                return (
                                                    <a
                                                        key={idx}
                                                        href={qr.url}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-medium bg-surface hover:bg-surface-2 border border-amber-500/40 text-amber-600 dark:text-amber-400 transition-colors shadow-2xs"
                                                    >
                                                        {getQuickReplyIcon(qr.label, qr.action, qr.url)}
                                                        <span>{qr.label}</span>
                                                    </a>
                                                );
                                            }
                                            return (
                                                <button
                                                    key={idx}
                                                    type="button"
                                                    onClick={() => handleSendMessage(undefined, qr.action)}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-medium bg-surface hover:bg-surface-2 border border-border hover:border-amber-400 text-text hover:text-amber-500 transition-all duration-150 cursor-pointer shadow-2xs active:scale-95"
                                                >
                                                    {getQuickReplyIcon(qr.label, qr.action)}
                                                    <span>{qr.label}</span>
                                                </button>
                                            );
                                        })}
                                    </div>
                                )}

                                <span className="text-[9px] text-text-muted px-1.5">
                                    {msg.timestamp}
                                </span>
                            </div>
                        ))}

                        {/* Typing Wave Indicator */}
                        {isTyping && (
                            <div className="flex items-center gap-2.5 bg-surface border border-border text-text-muted px-4 py-2.5 rounded-2xl rounded-tl-xs w-fit text-[11px] shadow-2xs">
                                <div className="flex items-center gap-1">
                                    <span className="size-1.5 rounded-full bg-amber-500 animate-bounce [animation-delay:-0.3s]" />
                                    <span className="size-1.5 rounded-full bg-amber-500 animate-bounce [animation-delay:-0.15s]" />
                                    <span className="size-1.5 rounded-full bg-amber-500 animate-bounce" />
                                </div>
                                <span>Menyiapkan informasi...</span>
                            </div>
                        )}

                        <div ref={messagesEndRef} />
                    </div>

                    {/* Footer Input Area */}
                    <div className="p-3 bg-surface border-t border-border space-y-2">
                        {/* Quick Prompts Bar */}
                        <div className="flex items-center gap-1.5 overflow-x-auto pb-0.5 text-[11px] [scrollbar-width:none] [-ms-overflow-style:none] [&::-webkit-scrollbar]:hidden">
                            <button
                                type="button"
                                onClick={() => handleSendMessage(undefined, 'menu_check_booking')}
                                className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-surface-2 hover:bg-surface border border-border text-text-muted hover:text-text shrink-0 transition-colors cursor-pointer text-[10px]"
                            >
                                <Search className="size-2.5 text-amber-500" />
                                <span>Cek Booking</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => handleSendMessage(undefined, 'menu_check_snacks')}
                                className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-surface-2 hover:bg-surface border border-border text-text-muted hover:text-text shrink-0 transition-colors cursor-pointer text-[10px]"
                            >
                                <Utensils className="size-2.5 text-amber-500" />
                                <span>Menu Jajan</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => handleSendMessage(undefined, 'menu_report_issue')}
                                className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-surface-2 hover:bg-surface border border-border text-text-muted hover:text-text shrink-0 transition-colors cursor-pointer text-[10px]"
                            >
                                <AlertCircle className="size-2.5 text-rose-500" />
                                <span>Lapor Kendala</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => handleSendMessage(undefined, 'menu_info')}
                                className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-surface-2 hover:bg-surface border border-border text-text-muted hover:text-text shrink-0 transition-colors cursor-pointer text-[10px]"
                            >
                                <Film className="size-2.5 text-sky-500" />
                                <span>Studio</span>
                            </button>
                            <button
                                type="button"
                                onClick={() => handleSendMessage(undefined, 'menu_feedback')}
                                className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-surface-2 hover:bg-surface border border-border text-text-muted hover:text-text shrink-0 transition-colors cursor-pointer text-[10px]"
                            >
                                <MessageSquare className="size-2.5 text-amber-500" />
                                <span>Kirim Pesan</span>
                            </button>
                        </div>

                        {/* Form Input */}
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                handleSendMessage();
                            }}
                            className="flex items-center gap-2 bg-surface-2 border border-border focus-within:border-amber-400 focus-within:ring-2 focus-within:ring-amber-400/20 rounded-xl px-2 py-1.5 transition-all"
                        >
                            <input
                                ref={inputRef}
                                type="text"
                                value={inputMessage}
                                onChange={(e) => setInputMessage(e.target.value)}
                                placeholder="Ketik kode booking (HC-...) atau pesan..."
                                className="flex-1 bg-transparent px-2 text-xs text-text placeholder:text-text-muted focus:outline-none"
                            />
                            <button
                                type="submit"
                                disabled={!inputMessage.trim() || isTyping}
                                className="p-2 rounded-lg bg-amber-500 text-black font-bold disabled:opacity-30 disabled:cursor-not-allowed hover:bg-amber-400 transition-all shrink-0 cursor-pointer active:scale-95"
                                title="Kirim Pesan"
                            >
                                <Send className="size-3.5" />
                            </button>
                        </form>

                        {/* Secondary Channels */}
                        <div className="flex items-center justify-between text-[10px] text-text-muted px-1 pt-0.5">
                            <span className="truncate">Layanan Bantuan {cinemaName}</span>
                            <div className="flex items-center gap-3 shrink-0">
                                {hasPhone && (
                                    <a
                                        href={waUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="hover:text-text flex items-center gap-1 transition-colors font-medium"
                                    >
                                        <PhoneCall className="size-3 text-emerald-500" />
                                        <span>WhatsApp</span>
                                    </a>
                                )}
                                <a
                                    href={botUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="hover:text-text flex items-center gap-1 transition-colors font-medium"
                                >
                                    <ExternalLink className="size-3 text-sky-500" />
                                    <span>Telegram</span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}
