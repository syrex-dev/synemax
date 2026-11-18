import { Head, Link } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import { Award, CheckCircle, Calendar, Film, Download, Printer } from 'lucide-react';

export default function Card({ member, recentVisits = [], freeVisit = 10, discountVisit = 5 }) {
    const displayName = member.nickname ? `${member.name} (${member.nickname})` : member.name;
    const mascotUrl = '/images/member-mascot.jpg';
    const qrUrl = `/qr-code?text=${encodeURIComponent(member.code || member.member_code)}`;
    const downloadSvgUrl = window.location.href + (window.location.href.includes('?') ? '&format=svg' : '?format=svg');

    // Calculate loyalty cycle progress
    const totalVisits = member.total_visits || 0;
    const inCycle = totalVisits % freeVisit;
    const progressPercent = Math.min(100, Math.round((inCycle / freeVisit) * 100));

    const handlePrint = () => {
        window.print();
    };

    return (
        <PublicLayout title={`Kartu Member: ${displayName}`}>
            <Head title={`Kartu Member: ${displayName} · SynemaX`} />

            <div className="mx-auto max-w-lg px-4 py-8 sm:px-6 space-y-6">
                {/* Header */}
                <div className="text-center space-y-1.5 no-print">
                    <h1 className="text-xl font-bold tracking-tight text-text sm:text-2xl">
                        Kartu Member Bioskop
                    </h1>
                    <p className="text-xs text-text-muted">
                        Tunjukkan kode QR ke kasir atau petugas saat menonton untuk mencatat kunjungan & reward.
                    </p>
                </div>

                {/* Digital Card */}
                <div
                    id="member-digital-card"
                    className="rounded-3xl border border-border bg-surface p-5 sm:p-7 shadow-xl space-y-5 text-left border-b-[6px] border-b-amber-500 overflow-hidden relative"
                >
                    {/* Header Kartu */}
                    <div className="flex items-center justify-between border-b border-border/80 pb-3.5">
                        <span className="text-base sm:text-lg font-black tracking-wider uppercase text-text">
                            SynemaX
                        </span>
                        <span className="rounded-md bg-slate-900 border border-amber-500/60 px-2.5 py-1 text-[10px] font-extrabold tracking-wider text-amber-400 uppercase">
                            KARTU MEMBER
                        </span>
                    </div>

                    {/* Member Details & Mascot */}
                    <div className="flex items-center gap-3.5 sm:gap-5">
                        <div className="size-20 sm:size-24 shrink-0 rounded-2xl border-2 border-amber-500/80 overflow-hidden bg-white shadow-sm flex items-center justify-center p-0.5">
                            <img
                                src={mascotUrl}
                                alt="Maskot Member"
                                className="size-full object-cover rounded-xl"
                                onError={(e) => {
                                    e.target.style.display = 'none';
                                }}
                            />
                        </div>

                        <div className="flex-1 min-w-0 space-y-2">
                            <div>
                                <span className="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-text-muted block">
                                    Nama Member
                                </span>
                                <h2 className="text-sm sm:text-base font-extrabold text-text leading-tight truncate">
                                    {displayName}
                                </h2>
                            </div>
                            <div>
                                <span className="text-[9px] sm:text-[10px] font-bold uppercase tracking-wider text-text-muted block">
                                    ID Member
                                </span>
                                <p className="font-mono text-xs sm:text-sm font-extrabold tracking-wider text-amber-600 dark:text-amber-400">
                                    {member.formatted_code || member.code}
                                </p>
                            </div>
                        </div>
                    </div>

                    {/* QR Code */}
                    <div className="flex flex-col items-center justify-center p-4 rounded-2xl bg-white border border-border/60 shadow-inner">
                        <img
                            src={qrUrl}
                            alt="QR Member"
                            className="size-44 sm:size-48 object-contain"
                        />
                        <p className="text-[11px] font-mono font-bold text-slate-800 mt-2">
                            {member.formatted_code || member.code}
                        </p>
                    </div>

                    {/* Progress Loyalitas */}
                    <div className="space-y-2 pt-2 border-t border-border/80">
                        <div className="flex items-center justify-between text-xs">
                            <span className="font-bold text-text flex items-center gap-1.5">
                                <Award className="size-4 text-amber-500" />
                                <span>Kunjungan Siklus Aktif</span>
                            </span>
                            <span className="font-extrabold text-amber-600 dark:text-amber-400">
                                {inCycle} / {freeVisit} Kunjungan
                            </span>
                        </div>

                        <div className="h-2.5 w-full bg-surface-2 rounded-full overflow-hidden border border-border">
                            <div
                                className="h-full bg-gradient-to-r from-amber-500 to-amber-400 transition-all duration-500 rounded-full"
                                style={{ width: `${progressPercent}%` }}
                            />
                        </div>

                        <div className="flex items-center justify-between text-[10px] text-text-muted pt-1">
                            <span>Sesi ke-{discountVisit}: Diskon</span>
                            <span>Sesi ke-{freeVisit}: Tiket Gratis 1x</span>
                        </div>
                    </div>
                </div>

                {/* Action Buttons */}
                <div className="flex items-center justify-center gap-3 no-print">
                    <a
                        href={downloadSvgUrl}
                        className="inline-flex min-h-[42px] items-center gap-2 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:bg-surface-2 transition shadow-xs"
                    >
                        <Download className="size-4 text-text-muted" />
                        <span>Unduh Kartu (SVG)</span>
                    </a>

                    <button
                        type="button"
                        onClick={handlePrint}
                        className="inline-flex min-h-[42px] items-center gap-2 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:bg-surface-2 transition shadow-xs"
                    >
                        <Printer className="size-4 text-text-muted" />
                        <span>Cetak</span>
                    </button>
                </div>

                {/* Riwayat Kunjungan Terakhir */}
                {recentVisits.length > 0 && (
                    <div className="rounded-2xl border border-border bg-surface p-5 shadow-sm space-y-3 no-print">
                        <h3 className="text-xs font-bold uppercase tracking-wider text-text flex items-center gap-2 pb-2 border-b border-border">
                            <Calendar className="size-4 text-amber-500" />
                            <span>Kunjungan Terakhir</span>
                        </h3>

                        <div className="divide-y divide-border">
                            {recentVisits.map((visit) => (
                                <div key={visit.id} className="py-2.5 flex items-center justify-between text-xs">
                                    <div className="min-w-0 pr-2">
                                        <p className="font-bold text-text truncate">
                                            {visit.session?.film?.title || 'Menonton Film'}
                                        </p>
                                        <p className="text-[11px] text-text-muted">
                                            {visit.session?.session_date || visit.created_at}
                                        </p>
                                    </div>
                                    <span className="shrink-0 inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400">
                                        <CheckCircle className="size-3" />
                                        <span>Tercatat</span>
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
