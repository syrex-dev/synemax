import { Link, Head } from '@inertiajs/react';
import PublicLayout from '../../../Layouts/PublicLayout';
import Pagination from '../../../Components/Pagination';
import { Newspaper, HelpCircle, DollarSign, Calendar, ArrowRight, CheckCircle2, MessageCircle, Star, Users } from 'lucide-react';

export default function Index({
    articles,
    currentCategory = null,
    activeTab = 'berita',
    weekdayPrice = 5000,
    weekendPrice = 6000,
    holidayPrice = 6000,
    couplePrice = 12000,
    privatePrice = 36000,
    adminWhatsAppUrl = null,
}) {
    const articleItems = articles?.data || (Array.isArray(articles) ? articles : []);

    const formatPrice = (val) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            maximumFractionDigits: 0,
        }).format(val || 0);
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return '';
        try {
            return new Date(dateStr).toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
            });
        } catch (e) {
            return dateStr;
        }
    };

    const faqItems = [
        {
            q: 'Bagaimana cara memesan tiket nonton di SynemaX?',
            a: 'Buka menu Jadwal, pilih hari dan jam sesi penayangan yang diinginkan. Anda dapat memilih film dari katalog, menentukan jumlah kursi (1 hingga 6 orang), dan menyelesaikan pembayaran via QRIS atau transfer.',
        },
        {
            q: 'Berapa kapasitas satu sesi studio privat?',
            a: 'Studio 1 kami didesain privat dan eksklusif dengan kapasitas maksimum 6 kursi empuk sofa sinematik.',
        },
        {
            q: 'Apakah bisa membawa makanan dan minuman dari luar?',
            a: 'Demi kenyamanan dan kebersihan perangkat audio visual studio, penonton dianjurkan menikmati camilan dan minuman higienis yang tersedia di loket jajan SynemaX.',
        },
        {
            q: 'Bagaimana jika ingin sewa seluruh studio (privat rombongan)?',
            a: 'Anda cukup memesan 6 tiket (seluruh kapasitas studio) pada sesi yang masih kosong. Sesi tersebut otomatis terkunci dan tidak dapat diisi oleh penonton lain.',
        },
        {
            q: 'Apakah program kartu member tersedia?',
            a: 'Ya! Setiap kunjungan mendapatkan stempel loyalitas. Kumpulkan 10 stempel untuk memperoleh 1 tiket gratis nonton.',
        },
    ];

    return (
        <PublicLayout title={activeTab === 'harga-faq' ? 'Harga Tiket & FAQ' : 'Kabar & Pengumuman'}>
            <Head>
                <meta
                    name="description"
                    content="Informasi lengkap harga tiket studio mini, FAQ pemesanan, dan pengumuman terkini dari SynemaX."
                />
            </Head>

            <div className="mx-auto max-w-6xl px-4 py-6 sm:px-6 sm:py-10 space-y-8">
                {/* Header Banner */}
                <div className="relative overflow-hidden rounded-3xl border border-border dark:border-white/10 bg-surface dark:bg-gradient-to-br dark:from-surface dark:via-[#0C111D] dark:to-[#07090E] p-6 sm:p-8 shadow-2xl">
                    <div className="absolute -right-16 -top-16 size-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none" />

                    <div className="relative">
                        <span className="inline-flex items-center gap-1.5 rounded-full border border-amber-400/40 bg-amber-500/15 px-3 py-1 text-xs font-black uppercase tracking-wider text-amber-500 dark:text-amber-300">
                            <span className="size-2 rounded-full bg-amber-400 animate-pulse" />
                            Warta studio
                        </span>
                        <h1 className="mt-2 text-2xl font-black text-text sm:text-4xl tracking-tight">
                            {activeTab === 'harga-faq' ? 'Harga Tiket & Tanya Jawab' : 'Kabar & Pengumuman'}
                        </h1>
                        <p className="mt-2 max-w-xl text-xs sm:text-sm text-text-muted leading-relaxed">
                            {activeTab === 'harga-faq'
                                ? 'Transparansi tarif pemutaran film di Studio 1 serta panduan pertanyaan umum bagi penonton dan rombongan.'
                                : 'Temukan warta terkini seputar studio dan informasi kegiatan di SynemaX.'}
                        </p>

                        {/* Tabs Navigation */}
                        <div className="mt-6 flex flex-wrap gap-2.5 border-t border-border dark:border-white/10 pt-5">
                            <Link
                                href="/kabar?tab=berita"
                                className={`inline-flex min-h-11 items-center gap-2 rounded-2xl px-5 text-xs font-black transition ${
                                    activeTab === 'berita'
                                        ? 'btn-gold-cinema shadow-md'
                                        : 'border border-border dark:border-white/10 bg-surface-2 dark:bg-surface text-text-muted hover:border-amber-400/50 hover:text-text'
                                }`}
                            >
                                <Newspaper className="size-4" />
                                <span>Berita</span>
                            </Link>
                            <Link
                                href="/kabar?tab=harga-faq"
                                className={`inline-flex min-h-11 items-center gap-2 rounded-2xl px-5 text-xs font-black transition ${
                                    activeTab === 'harga-faq'
                                        ? 'btn-gold-cinema shadow-md'
                                        : 'border border-border dark:border-white/10 bg-surface-2 dark:bg-surface text-text-muted hover:border-amber-400/50 hover:text-text'
                                }`}
                            >
                                <DollarSign className="size-4" />
                                <span>Harga & FAQ</span>
                            </Link>
                        </div>
                    </div>
                </div>

                {/* TAB 1: Berita */}
                {activeTab === 'berita' && (
                    <div className="space-y-6">
                        {/* Subcategory Pills */}
                        <div className="flex flex-wrap justify-center items-center gap-2">
                            <Link
                                href="/kabar?tab=berita"
                                className={`inline-flex min-h-10 items-center justify-center rounded-xl px-4 py-2 text-xs font-bold transition ${
                                    !currentCategory
                                        ? 'btn-gold-cinema shadow-sm'
                                        : 'border border-border bg-surface text-text-muted hover:text-text'
                                }`}
                            >
                                Semua warta
                            </Link>
                            {['Berita', 'Pengumuman'].map((cat) => (
                                <Link
                                    key={cat}
                                    href={`/kabar?tab=berita&kategori=${cat}`}
                                    className={`inline-flex min-h-10 items-center justify-center rounded-xl px-4 py-2 text-xs font-bold transition ${
                                        currentCategory === cat
                                            ? 'btn-gold-cinema shadow-sm'
                                            : 'border border-border bg-surface text-text-muted hover:text-text'
                                    }`}
                                >
                                    {cat}
                                </Link>
                            ))}
                        </div>

                        {/* Article Grid */}
                        {articleItems.length === 0 ? (
                            <div className="rounded-2xl border border-dashed border-border bg-surface-2/60 p-12 text-center">
                                <span className="inline-flex size-14 items-center justify-center rounded-2xl bg-surface border border-border text-amber-500 mb-3 shadow-xs">
                                    <Newspaper className="size-6" />
                                </span>
                                <h2 className="text-base font-bold text-text">Belum ada artikel</h2>
                                <p className="mt-1 text-xs text-text-muted max-w-md mx-auto">
                                    Kabar untuk kategori ini belum tersedia. Silakan cek kembali nanti.
                                </p>
                            </div>
                        ) : (
                            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {articleItems.map((item) => {
                                    const coverSrc = item.cover_url || (item.cover_path ? `/storage/${item.cover_path}` : null);
                                    return (
                                        <article
                                            key={item.id}
                                            className="flex flex-col justify-between rounded-2xl border border-border bg-surface p-4 sm:p-5 transition-all duration-300 hover:border-amber-400/50 hover:shadow-xl group"
                                        >
                                            <div>
                                                {coverSrc && (
                                                    <div className="relative mb-4 aspect-[16/9] rounded-xl overflow-hidden bg-slate-950">
                                                        <img
                                                            src={coverSrc}
                                                            alt={item.judul}
                                                            loading="lazy"
                                                            className="size-full object-cover transition-transform duration-500 group-hover:scale-105"
                                                        />
                                                        <div className="absolute left-3 top-3">
                                                            <span className="rounded-lg border border-white/20 bg-black/80 backdrop-blur-md px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-white">
                                                                {item.kategori}
                                                            </span>
                                                        </div>
                                                    </div>
                                                )}

                                                <div className="space-y-2">
                                                    {!coverSrc && (
                                                        <span className="text-[10px] font-black uppercase tracking-wider text-amber-500">
                                                            {item.kategori}
                                                        </span>
                                                    )}
                                                    <h2 className="text-base font-black text-text line-clamp-2 group-hover:text-amber-500 transition-colors">
                                                        <Link href={`/kabar/${item.slug || item.id}`}>
                                                            {item.judul}
                                                        </Link>
                                                    </h2>
                                                    {item.ringkasan && (
                                                        <p className="text-xs text-text-muted line-clamp-3 leading-relaxed">
                                                            {item.ringkasan}
                                                        </p>
                                                    )}
                                                </div>
                                            </div>

                                            <div className="mt-5 pt-4 border-t border-border flex items-center justify-between text-xs text-text-muted">
                                                <span>{formatDate(item.tanggal_terbit || item.created_at)}</span>
                                                <Link
                                                    href={`/kabar/${item.slug || item.id}`}
                                                    className="inline-flex items-center gap-1 font-bold text-amber-500 hover:underline"
                                                >
                                                    <span>Baca</span>
                                                    <ArrowRight className="size-3" />
                                                </Link>
                                            </div>
                                        </article>
                                    );
                                })}
                            </div>
                        )}

                        {articles?.links && (
                            <Pagination links={articles.links} />
                        )}
                    </div>
                )}

                {/* TAB 2: Harga & FAQ */}
                {activeTab === 'harga-faq' && (
                    <div className="space-y-10">
                        {/* Price cards grid */}
                        <div className="grid gap-6 md:grid-cols-2">
                            {/* Per person card */}
                            <section className="rounded-3xl border border-border bg-surface p-6 sm:p-7 shadow-xl space-y-5">
                                <div>
                                    <span className="text-xs font-black uppercase tracking-wider text-amber-500">
                                        Transparansi Tarif
                                    </span>
                                    <h2 className="mt-1 text-xl font-black text-text">Harga tiket studio per orang</h2>
                                    <p className="mt-1 text-xs text-text-muted">
                                        Tarif resmi per orang untuk setiap sesi penayangan di Studio 1.
                                    </p>
                                </div>

                                <dl className="divide-y divide-border rounded-2xl border border-border bg-surface-2/60 px-4">
                                    <div className="flex items-center justify-between py-3.5 text-xs sm:text-sm">
                                        <dt className="text-text-muted">Hari biasa (Senin sampai Kamis)</dt>
                                        <dd className="font-mono font-black text-amber-500">{formatPrice(weekdayPrice)}</dd>
                                    </div>
                                    <div className="flex items-center justify-between py-3.5 text-xs sm:text-sm">
                                        <dt className="text-text-muted">Akhir pekan (Jumat sampai Minggu)</dt>
                                        <dd className="font-mono font-black text-amber-500">{formatPrice(weekendPrice)}</dd>
                                    </div>
                                    <div className="flex items-center justify-between py-3.5 text-xs sm:text-sm">
                                        <dt className="text-text-muted">Hari libur nasional</dt>
                                        <dd className="font-mono font-black text-amber-500">{formatPrice(holidayPrice)}</dd>
                                    </div>
                                </dl>

                                <div className="flex items-center gap-2 text-xs text-text-muted font-medium">
                                    <CheckCircle2 className="size-4 text-emerald-500 shrink-0" />
                                    <span>Termasuk pendingin ruangan & audio sinematik privat</span>
                                </div>
                            </section>

                            {/* Full studio buyout card */}
                            <section className="rounded-3xl border border-border bg-surface p-6 sm:p-7 shadow-xl flex flex-col justify-between space-y-5">
                                <div>
                                    <span className="text-xs font-black uppercase tracking-wider text-amber-500">
                                        Eksklusif Rombongan
                                    </span>
                                    <h2 className="mt-1 text-xl font-black text-text">Sewa studio privat (6 kursi)</h2>
                                    <p className="mt-1 text-xs text-text-muted">
                                        Kunci seluruh 6 tempat khusus rombongan keluarga atau sahabat tanpa penonton lain.
                                    </p>

                                    <div className="mt-5 rounded-2xl border border-amber-500/25 bg-amber-500/5 p-4 sm:p-5 space-y-3">
                                        <div className="flex items-baseline justify-between border-b border-amber-500/20 pb-3">
                                            <span className="text-xs font-bold text-text">Biaya sewa penuh 1 sesi:</span>
                                            <span className="font-mono text-xl sm:text-2xl font-black text-amber-500">
                                                {formatPrice(privatePrice)}
                                            </span>
                                        </div>
                                        <div className="flex items-baseline justify-between border-b border-amber-500/20 pb-3">
                                            <span className="text-xs font-bold text-text">Paket Couple (2 orang):</span>
                                            <span className="font-mono text-base sm:text-lg font-black text-amber-500">
                                                {formatPrice(couplePrice)}
                                            </span>
                                        </div>
                                        <ul className="space-y-2 text-xs text-text-muted">
                                            <li className="flex items-center gap-2">
                                                <CheckCircle2 className="size-3.5 text-emerald-500" />
                                                <span>Bebas pilih film dari katalog tersedia</span>
                                            </li>
                                            <li className="flex items-center gap-2">
                                                <CheckCircle2 className="size-3.5 text-emerald-500" />
                                                <span>Privasi total untuk 1 rombongan keluarga</span>
                                            </li>
                                        </ul>
                                    </div>
                                </div>

                                <Link
                                    href="/jadwal"
                                    className="btn-gold-cinema inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl text-xs font-black shadow-md"
                                >
                                    <span>Pilih Jadwal Sewa Privat</span>
                                    <Star className="size-4" />
                                </Link>
                            </section>
                        </div>

                        {/* FAQ Section */}
                        <section className="rounded-3xl border border-border bg-surface p-6 sm:p-8 shadow-xl space-y-6">
                            <div>
                                <span className="text-xs font-black uppercase tracking-wider text-amber-500">
                                    Pertanyaan Umum
                                </span>
                                <h2 className="mt-1 text-xl font-black text-text">Tanya Jawab Seputar SynemaX</h2>
                            </div>

                            <div className="grid gap-4">
                                {faqItems.map((faq, idx) => (
                                    <details
                                        key={idx}
                                        className="group rounded-2xl border border-border bg-surface-2/60 p-4 transition-all duration-200 open:border-amber-500/50"
                                    >
                                        <summary className="font-bold text-sm text-text cursor-pointer list-none flex items-center justify-between gap-4">
                                            <span>{faq.q}</span>
                                            <span className="size-6 rounded-full bg-surface border border-border flex items-center justify-center text-xs group-open:rotate-180 transition-transform">
                                                ▼
                                            </span>
                                        </summary>
                                        <p className="mt-3 text-xs sm:text-sm text-text-muted leading-relaxed border-t border-border pt-3">
                                            {faq.a}
                                        </p>
                                    </details>
                                ))}
                            </div>

                            {adminWhatsAppUrl && (
                                <div className="mt-6 pt-6 border-t border-border flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                                    <div>
                                        <h3 className="text-sm font-bold text-text">Masih ada pertanyaan lain?</h3>
                                        <p className="text-xs text-text-muted">Hubungi langsung admin SynemaX via WhatsApp</p>
                                    </div>
                                    <a
                                        href={adminWhatsAppUrl}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-5 transition shadow-sm"
                                    >
                                        <MessageCircle className="size-4" />
                                        <span>Chat Admin WhatsApp</span>
                                    </a>
                                </div>
                            )}
                        </section>
                    </div>
                )}
            </div>
        </PublicLayout>
    );
}
