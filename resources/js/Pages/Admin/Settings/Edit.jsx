import { useState } from 'react';
import { Head, useForm, router, usePage } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import {
    Settings,
    Building,
    Menu as MenuIcon,
    Palette,
    Clock,
    CreditCard,
    Phone,
    Globe,
    Send,
    Users,
    Save,
    Trash2,
    RefreshCw,
    AlertTriangle,
    Check,
    Upload,
    Plus,
    X,
    ExternalLink,
} from 'lucide-react';

export default function Edit({
    currentTab = 'identitas',
    settings = {},
    enabledMethods = [],
    hasQrisImage = false,
    hasTransferAccounts = false,
    qrisRatioWarning = false,
    libraryImages = [],
    sampleCounts = {},
    activeFilms = [],
    featuredFilmIds = [],
    homeFilmsMode = 'latest',
    telegramSettings = {},
    telegramLogs = [],
}) {
    const { flash } = usePage().props;
    const [activeTab, setActiveTab] = useState(currentTab);

    // Form for Identitas
    const identitasForm = useForm({
        active_tab: 'identitas',
        site_name: settings.site_name || 'SynemaX',
        site_tagline: settings.site_tagline || '',
        navbar_brand_display: settings.navbar_brand_display || 'logo_text',
        site_logo: null,
        site_logo_dark: null,
        site_favicon: null,
        site_og_image: null,
    });

    // Form for Navbar
    const [menuItems, setMenuItems] = useState(
        settings.navbar_menu && settings.navbar_menu.length > 0
            ? settings.navbar_menu
            : [
                  { label: 'Jadwal', url: '/jadwal', active: true },
                  { label: 'Film', url: '/film', active: true },
                  { label: 'Harga & FAQ', url: '/kabar?tab=harga-faq', active: true },
                  { label: 'Tiket Saya', url: '/tiket', active: true },
              ]
    );

    // Form for Tampilan
    const tampilanForm = useForm({
        active_tab: 'tampilan',
        default_theme: settings.default_theme || 'system',
        show_theme_toggle: settings.show_theme_toggle !== false,
        films_sort_order: settings.films_sort_order || 'latest',
        home_films_count: settings.home_films_count || 8,
        home_hero_interval: settings.home_hero_interval || 5,
        home_hero_autoplay: settings.home_hero_autoplay !== false,
        info_nobar_enabled: settings.info_nobar_enabled !== false,
    });

    // Form for Operasional
    const operasionalForm = useForm({
        active_tab: 'operasional',
        capacity_per_session: settings.capacity_per_session || 6,
        max_seats_per_booking: settings.max_seats_per_booking || 6,
        minimum_public_viewers: settings.minimum_public_viewers || 2,
        low_attendance_warning_window: settings.low_attendance_warning_window || 24,
        booking_cutoff_minutes: settings.booking_cutoff_minutes || 60,
        cancellation_cutoff_hours: settings.cancellation_cutoff_hours || 24,
        check_in_open_minutes_before: settings.check_in_open_minutes_before || 30,
        check_in_close_option: settings.check_in_close_option || 'session_end',
        check_in_sound_enabled: settings.check_in_sound_enabled !== false,
        snacks_enabled: settings.snacks_enabled !== false,
        maintenance_mode: settings.maintenance_mode || false,
        maintenance_message: settings.maintenance_message || '',
    });

    // Form for Harga & Pembayaran
    const [transferAccounts, setTransferAccounts] = useState(
        settings.transfer_accounts && settings.transfer_accounts.length > 0
            ? settings.transfer_accounts
            : [{ bank: 'BCA', account_number: '', account_holder: '' }]
    );
    const [selectedMethods, setSelectedMethods] = useState(
        enabledMethods && enabledMethods.length > 0 ? enabledMethods : ['di_tempat', 'qris']
    );

    const hargaForm = useForm({
        active_tab: 'harga',
        weekday_price: settings.weekday_price || 25000,
        weekend_price: settings.weekend_price || 30000,
        holiday_price: settings.holiday_price || 35000,
        couple_price: settings.couple_price || 50000,
        private_price: settings.private_price || 150000,
        payment_time_limit_minutes: settings.payment_time_limit_minutes || 30,
        online_payment_cutoff_minutes: settings.online_payment_cutoff_minutes || 15,
        max_payment_proof_kb: settings.max_payment_proof_kb || 5120,
        qris_merchant_name: settings.qris_merchant_name || 'SynemaX Cinema',
        qris_payment_notes: settings.qris_payment_notes || '',
        transfer_payment_notes: settings.transfer_payment_notes || '',
        qris_image: null,
    });

    // Form for Kontak & Sosial
    const kontakForm = useForm({
        active_tab: 'kontak',
        contact_whatsapp: settings.contact_whatsapp || '',
        contact_phone: settings.contact_phone || '',
        contact_email: settings.contact_email || '',
        contact_address: settings.contact_address || '',
        contact_maps_url: settings.contact_maps_url || '',
        maps_label: settings.maps_label || 'Lihat di Google Maps',
        show_floating_whatsapp: settings.show_floating_whatsapp !== false,
        instagram: settings.social_settings?.instagram?.url || '',
        tiktok: settings.social_settings?.tiktok?.url || '',
        youtube: settings.social_settings?.youtube?.url || '',
    });

    // Form for SEO & Footer
    const seoForm = useForm({
        active_tab: 'seo',
        seo_title_template: settings.seo_title_template || '%s | SynemaX',
        seo_meta_description: settings.seo_meta_description || '',
        seo_keywords: settings.seo_keywords || '',
        formatted_footer_text: settings.formatted_footer_text || '',
    });

    // Form for Telegram
    const telegramForm = useForm({
        active_tab: 'notifikasi',
        telegram_notifications_enabled: telegramSettings.enabled || false,
        telegram_bot_token: '',
        telegram_chat_ids: telegramSettings.chat_ids || '',
        telegram_notify_booking_created: telegramSettings.notify_booking_created !== false,
        telegram_notify_proof_uploaded: telegramSettings.notify_proof_uploaded !== false,
        telegram_notify_payment_confirmed: telegramSettings.notify_payment_confirmed || false,
        telegram_notify_booking_cancelled: telegramSettings.notify_booking_cancelled !== false,
        telegram_notify_booking_expired: telegramSettings.notify_booking_expired || false,
        telegram_notify_session_confirmed: telegramSettings.notify_session_confirmed !== false,
        telegram_notify_session_cancelled: telegramSettings.notify_session_cancelled !== false,
        telegram_hide_customer_whatsapp: telegramSettings.hide_customer_whatsapp || false,
        telegram_include_customer_notes: telegramSettings.include_customer_notes !== false,
    });

    // Form for Member Program
    const memberForm = useForm({
        active_tab: 'member',
        is_member_program_enabled: settings.is_member_program_enabled !== false,
        member_discount_visit: settings.member_discount_visit || 5,
        member_discount_percent: settings.member_discount_percent || 50,
        member_free_visit: settings.member_free_visit || 10,
        member_discount_notes: settings.member_discount_notes || '',
        member_free_notes: settings.member_free_notes || '',
    });

    const tabs = [
        { id: 'identitas', label: 'Identitas', icon: Building },
        { id: 'navbar', label: 'Navbar Menu', icon: MenuIcon },
        { id: 'tampilan', label: 'Tampilan', icon: Palette },
        { id: 'operasional', label: 'Operasional', icon: Clock },
        { id: 'harga', label: 'Harga & Bayar', icon: CreditCard },
        { id: 'kontak', label: 'Kontak & Sosial', icon: Phone },
        { id: 'seo', label: 'SEO & Footer', icon: Globe },
        { id: 'notifikasi', label: 'Telegram', icon: Send },
        { id: 'member', label: 'Member Loyalitas', icon: Users },
    ];

    // Handlers
    const submitIdentitas = (e) => {
        e.preventDefault();
        identitasForm.post('/admin/pengaturan');
    };

    const submitTampilan = (e) => {
        e.preventDefault();
        tampilanForm.post('/admin/pengaturan');
    };

    const submitOperasional = (e) => {
        e.preventDefault();
        operasionalForm.post('/admin/pengaturan');
    };

    const submitHarga = (e) => {
        e.preventDefault();
        hargaForm.transform((data) => ({
            ...data,
            payment_methods: selectedMethods,
            transfer_accounts: transferAccounts,
        }));
        hargaForm.post('/admin/pengaturan');
    };

    const submitKontak = (e) => {
        e.preventDefault();
        kontakForm.post('/admin/pengaturan');
    };

    const submitSeo = (e) => {
        e.preventDefault();
        seoForm.post('/admin/pengaturan');
    };

    const submitTelegram = (e) => {
        e.preventDefault();
        telegramForm.post('/admin/pengaturan');
    };

    const submitMember = (e) => {
        e.preventDefault();
        memberForm.post('/admin/pengaturan');
    };

    const handleClearCache = () => {
        router.post('/admin/pengaturan/cache-clear');
    };

    const handleTestTelegram = () => {
        router.post('/admin/pengaturan/telegram/test');
    };

    return (
        <AdminLayout title="Pengaturan Bioskop" isFormPage={true}>
            <Head title="Pengaturan Bioskop · Admin" />

            <div className="max-w-5xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-xl font-bold tracking-tight text-text sm:text-2xl">
                            Pengaturan Bioskop
                        </h1>
                        <p className="text-xs text-text-muted mt-1">
                            Konfigurasi menyeluruh nama bioskop, identitas visual, operasional, metode pembayaran, dan notifikasi.
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <button
                            type="button"
                            onClick={handleClearCache}
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface px-3 py-2 text-xs font-semibold text-text hover:bg-surface-2 transition"
                            title="Bersihkan cache sistem agar pengaturan langsung diterapkan"
                        >
                            <RefreshCw className="size-3.5 text-text-muted" />
                            <span>Bersihkan Cache</span>
                        </button>
                    </div>
                </div>

                {/* Tab Navigation Pill Bar */}
                <div className="flex overflow-x-auto no-scrollbar gap-1.5 p-1 rounded-2xl border border-border bg-surface">
                    {tabs.map((tab) => {
                        const Icon = tab.icon;
                        const active = activeTab === tab.id;
                        return (
                            <button
                                key={tab.id}
                                type="button"
                                onClick={() => setActiveTab(tab.id)}
                                className={`flex shrink-0 items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-bold transition ${
                                    active
                                        ? 'bg-amber-500 text-slate-950 shadow-sm'
                                        : 'text-text-muted hover:text-text hover:bg-surface-2'
                                }`}
                            >
                                <Icon className={`size-4 ${active ? 'text-slate-950' : 'text-text-muted'}`} />
                                <span>{tab.label}</span>
                            </button>
                        );
                    })}
                </div>

                {/* TAB 1: IDENTITAS */}
                {activeTab === 'identitas' && (
                    <form onSubmit={submitIdentitas} className="space-y-6">
                        <div className="rounded-2xl border border-border bg-surface p-6 shadow-sm space-y-5">
                            <h2 className="text-sm font-bold uppercase tracking-wider text-text pb-2 border-b border-border">
                                Identitas Brand & Nama Bioskop
                            </h2>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="site_name" className="block text-xs font-bold text-text mb-1">
                                        Nama Bioskop / Brand *
                                    </label>
                                    <input
                                        id="site_name"
                                        type="text"
                                        value={identitasForm.data.site_name}
                                        onChange={(e) => identitasForm.setData('site_name', e.target.value)}
                                        required
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                                    />
                                    {identitasForm.errors.site_name && (
                                        <p className="mt-1 text-xs text-rose-500">{identitasForm.errors.site_name}</p>
                                    )}
                                </div>

                                <div>
                                    <label htmlFor="site_tagline" className="block text-xs font-bold text-text mb-1">
                                        Tagline / Slogan
                                    </label>
                                    <input
                                        id="site_tagline"
                                        type="text"
                                        value={identitasForm.data.site_tagline}
                                        onChange={(e) => identitasForm.setData('site_tagline', e.target.value)}
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400"
                                    />
                                </div>
                            </div>

                            <div>
                                <label className="block text-xs font-bold text-text mb-2">
                                    Format Tampilan Brand di Navbar
                                </label>
                                <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                    {[
                                        { value: 'logo_text', label: 'Logo & Nama Teks', desc: 'Menampilkan ikon logo disertai nama bioskop' },
                                        { value: 'logo_only', label: 'Logo Saja', desc: 'Hanya menampilkan gambar logo tanpa teks' },
                                        { value: 'text_only', label: 'Teks Saja', desc: 'Hanya menampilkan teks nama bioskop' },
                                    ].map((opt) => (
                                        <label
                                            key={opt.value}
                                            className={`p-3 rounded-xl border cursor-pointer transition ${
                                                identitasForm.data.navbar_brand_display === opt.value
                                                    ? 'border-amber-500 bg-amber-500/10'
                                                    : 'border-border bg-surface-2 hover:bg-surface'
                                            }`}
                                        >
                                            <input
                                                type="radio"
                                                name="navbar_brand_display"
                                                value={opt.value}
                                                checked={identitasForm.data.navbar_brand_display === opt.value}
                                                onChange={(e) => identitasForm.setData('navbar_brand_display', e.target.value)}
                                                className="sr-only"
                                            />
                                            <p className="text-xs font-bold text-text">{opt.label}</p>
                                            <p className="text-[11px] text-text-muted mt-0.5">{opt.desc}</p>
                                        </label>
                                    ))}
                                </div>
                            </div>

                            {/* Logos Upload */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div>
                                    <label className="block text-xs font-bold text-text mb-1">
                                        Logo Terang (Tema Gelap)
                                    </label>
                                    <input
                                        type="file"
                                        accept="image/png,image/svg+xml,image/webp,image/jpeg"
                                        onChange={(e) => identitasForm.setData('site_logo', e.target.files[0])}
                                        className="text-xs text-text file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-surface-2 file:text-text hover:file:bg-border"
                                    />
                                    {settings.site_logo && (
                                        <div className="mt-2 p-2 rounded-xl bg-slate-900 border border-slate-800 inline-block">
                                            <img src={settings.site_logo} alt="Logo Terang" className="h-8 max-w-[160px] object-contain" />
                                        </div>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-text mb-1">
                                        Logo Gelap (Tema Terang)
                                    </label>
                                    <input
                                        type="file"
                                        accept="image/png,image/svg+xml,image/webp,image/jpeg"
                                        onChange={(e) => identitasForm.setData('site_logo_dark', e.target.files[0])}
                                        className="text-xs text-text file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-surface-2 file:text-text hover:file:bg-border"
                                    />
                                    {settings.site_logo_dark && (
                                        <div className="mt-2 p-2 rounded-xl bg-slate-100 border border-slate-200 inline-block">
                                            <img src={settings.site_logo_dark} alt="Logo Gelap" className="h-8 max-w-[160px] object-contain" />
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Favicon & OG Image */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div>
                                    <label className="block text-xs font-bold text-text mb-1">
                                        Favicon Website (.ico / .png / .svg)
                                    </label>
                                    <input
                                        type="file"
                                        accept="image/x-icon,image/png,image/svg+xml"
                                        onChange={(e) => identitasForm.setData('site_favicon', e.target.files[0])}
                                        className="text-xs text-text file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-surface-2 file:text-text hover:file:bg-border"
                                    />
                                    {settings.site_favicon && (
                                        <div className="mt-2 inline-block">
                                            <img src={settings.site_favicon} alt="Favicon" className="size-8 object-contain" />
                                        </div>
                                    )}
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-text mb-1">
                                        Gambar Thumbnail Share Sosial (OG Image 1200x630)
                                    </label>
                                    <input
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={(e) => identitasForm.setData('site_og_image', e.target.files[0])}
                                        className="text-xs text-text file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-surface-2 file:text-text hover:file:bg-border"
                                    />
                                    {settings.site_og_image && (
                                        <div className="mt-2 inline-block">
                                            <img src={settings.site_og_image} alt="OG Image" className="h-14 rounded-lg object-cover" />
                                        </div>
                                    )}
                                </div>
                            </div>

                            <div className="pt-4 border-t border-border flex justify-end">
                                <button
                                    type="submit"
                                    disabled={identitasForm.processing}
                                    className="btn-gold-cinema inline-flex min-h-[44px] items-center gap-2 rounded-xl px-5 text-xs font-bold shadow-none disabled:opacity-50"
                                >
                                    <Save className="size-4" />
                                    <span>{identitasForm.processing ? 'Menyimpan...' : 'Simpan Identitas'}</span>
                                </button>
                            </div>
                        </div>
                    </form>
                )}

                {/* TAB 2: NAVBAR MENU */}
                {activeTab === 'navbar' && (
                    <div className="rounded-2xl border border-border bg-surface p-6 shadow-sm space-y-5">
                        <h2 className="text-sm font-bold uppercase tracking-wider text-text pb-2 border-b border-border">
                            Susunan Navigasi Header Publik
                        </h2>
                        <p className="text-xs text-text-muted">
                            Atur tautan menu navigasi yang tampil di bagian atas situs bagi pengunjung.
                        </p>

                        <div className="space-y-3">
                            {menuItems.map((item, idx) => (
                                <div key={idx} className="flex items-center gap-3 p-3 rounded-xl border border-border bg-surface-2">
                                    <div className="flex-1 grid grid-cols-1 sm:grid-cols-2 gap-2">
                                        <input
                                            type="text"
                                            value={item.label}
                                            onChange={(e) => {
                                                const updated = [...menuItems];
                                                updated[idx].label = e.target.value;
                                                setMenuItems(updated);
                                            }}
                                            placeholder="Label Menu (misal: Jadwal)"
                                            className="min-h-10 w-full rounded-xl border border-border bg-surface px-3 text-xs text-text"
                                        />
                                        <input
                                            type="text"
                                            value={item.url}
                                            onChange={(e) => {
                                                const updated = [...menuItems];
                                                updated[idx].url = e.target.value;
                                                setMenuItems(updated);
                                            }}
                                            placeholder="URL Tautan (misal: /jadwal)"
                                            className="min-h-10 w-full rounded-xl border border-border bg-surface px-3 text-xs text-text"
                                        />
                                    </div>
                                    <label className="flex items-center gap-1.5 cursor-pointer text-xs font-semibold text-text">
                                        <input
                                            type="checkbox"
                                            checked={item.active !== false}
                                            onChange={(e) => {
                                                const updated = [...menuItems];
                                                updated[idx].active = e.target.checked;
                                                setMenuItems(updated);
                                            }}
                                            className="size-4 rounded border-border bg-surface text-amber-500 focus:ring-amber-400"
                                        />
                                        <span>Aktif</span>
                                    </label>
                                    <button
                                        type="button"
                                        onClick={() => setMenuItems(menuItems.filter((_, i) => i !== idx))}
                                        className="size-9 flex items-center justify-center rounded-lg text-rose-500 hover:bg-rose-500/10 transition"
                                    >
                                        <Trash2 className="size-4" />
                                    </button>
                                </div>
                            ))}

                            <button
                                type="button"
                                onClick={() => setMenuItems([...menuItems, { label: '', url: '/', active: true }])}
                                className="inline-flex items-center gap-2 text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline"
                            >
                                <Plus className="size-4" />
                                <span>Tambah Menu Baru</span>
                            </button>
                        </div>

                        <div className="pt-4 border-t border-border flex justify-end">
                            <button
                                type="button"
                                onClick={() => {
                                    router.post('/admin/pengaturan', {
                                        active_tab: 'navbar',
                                        navbar_menu: menuItems,
                                    });
                                }}
                                className="btn-gold-cinema inline-flex min-h-[44px] items-center gap-2 rounded-xl px-5 text-xs font-bold shadow-none"
                            >
                                <Save className="size-4" />
                                <span>Simpan Menu Navbar</span>
                            </button>
                        </div>
                    </div>
                )}

                {/* TAB 3: TAMPILAN */}
                {activeTab === 'tampilan' && (
                    <form onSubmit={submitTampilan} className="space-y-6">
                        <div className="rounded-2xl border border-border bg-surface p-6 shadow-sm space-y-5">
                            <h2 className="text-sm font-bold uppercase tracking-wider text-text pb-2 border-b border-border">
                                Tampilan & Tema Visual
                            </h2>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="default_theme" className="block text-xs font-bold text-text mb-1">
                                        Tema Default Pengunjung Baru
                                    </label>
                                    <select
                                        id="default_theme"
                                        value={tampilanForm.data.default_theme}
                                        onChange={(e) => tampilanForm.setData('default_theme', e.target.value)}
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    >
                                        <option value="system">Mengikuti Sistem HP/Komputer Pengguna</option>
                                        <option value="dark">Mode Gelap (Cinema Dark)</option>
                                        <option value="light">Mode Terang (Clean Light)</option>
                                    </select>
                                </div>

                                <div className="flex items-center pt-5">
                                    <label className="flex items-center gap-2.5 cursor-pointer text-xs font-semibold text-text">
                                        <input
                                            type="checkbox"
                                            checked={tampilanForm.data.show_theme_toggle}
                                            onChange={(e) => tampilanForm.setData('show_theme_toggle', e.target.checked)}
                                            className="size-4 rounded border-border bg-surface-2 text-amber-500 focus:ring-amber-400"
                                        />
                                        <span>Tampilkan Tombol Ganti Tema di Header Publik</span>
                                    </label>
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                                <div>
                                    <label htmlFor="home_films_count" className="block text-xs font-bold text-text mb-1">
                                        Jumlah Film di Beranda
                                    </label>
                                    <input
                                        id="home_films_count"
                                        type="number"
                                        min="4"
                                        max="24"
                                        value={tampilanForm.data.home_films_count}
                                        onChange={(e) => tampilanForm.setData('home_films_count', e.target.value)}
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label htmlFor="home_hero_interval" className="block text-xs font-bold text-text mb-1">
                                        Interval Slide Banner (Detik)
                                    </label>
                                    <input
                                        id="home_hero_interval"
                                        type="number"
                                        min="2"
                                        max="30"
                                        value={tampilanForm.data.home_hero_interval}
                                        onChange={(e) => tampilanForm.setData('home_hero_interval', e.target.value)}
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>

                                <div className="flex items-center pt-5">
                                    <label className="flex items-center gap-2.5 cursor-pointer text-xs font-semibold text-text">
                                        <input
                                            type="checkbox"
                                            checked={tampilanForm.data.home_hero_autoplay}
                                            onChange={(e) => tampilanForm.setData('home_hero_autoplay', e.target.checked)}
                                            className="size-4 rounded border-border bg-surface-2 text-amber-500 focus:ring-amber-400"
                                        />
                                        <span>Auto-Play Banner Carousel</span>
                                    </label>
                                </div>
                            </div>

                            <div className="pt-2">
                                <label className="flex items-center gap-2.5 cursor-pointer text-xs font-semibold text-text">
                                    <input
                                        type="checkbox"
                                        checked={tampilanForm.data.info_nobar_enabled}
                                        onChange={(e) => tampilanForm.setData('info_nobar_enabled', e.target.checked)}
                                        className="size-4 rounded border-border bg-surface-2 text-amber-500 focus:ring-amber-400"
                                    />
                                    <span>Aktifkan Fitur Informasi Komunitas & Nonton Bareng (Nobar)</span>
                                </label>
                            </div>

                            <div className="pt-4 border-t border-border flex justify-end">
                                <button
                                    type="submit"
                                    disabled={tampilanForm.processing}
                                    className="btn-gold-cinema inline-flex min-h-[44px] items-center gap-2 rounded-xl px-5 text-xs font-bold shadow-none disabled:opacity-50"
                                >
                                    <Save className="size-4" />
                                    <span>{tampilanForm.processing ? 'Menyimpan...' : 'Simpan Tampilan'}</span>
                                </button>
                            </div>
                        </div>
                    </form>
                )}

                {/* TAB 4: OPERASIONAL */}
                {activeTab === 'operasional' && (
                    <form onSubmit={submitOperasional} className="space-y-6">
                        <div className="rounded-2xl border border-border bg-surface p-6 shadow-sm space-y-5">
                            <h2 className="text-sm font-bold uppercase tracking-wider text-text pb-2 border-b border-border">
                                Kapasitas Studio & Aturan Booking
                            </h2>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label htmlFor="capacity_per_session" className="block text-xs font-bold text-text mb-1">
                                        Kapasitas Kursi per Sesi *
                                    </label>
                                    <input
                                        id="capacity_per_session"
                                        type="number"
                                        min="1"
                                        max="20"
                                        value={operasionalForm.data.capacity_per_session}
                                        onChange={(e) => operasionalForm.setData('capacity_per_session', e.target.value)}
                                        required
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                    <p className="text-[11px] text-text-muted mt-1">Default SynemaX: 6 orang per sesi privat.</p>
                                </div>

                                <div>
                                    <label htmlFor="max_seats_per_booking" className="block text-xs font-bold text-text mb-1">
                                        Maksimal Kursi per Pemesan *
                                    </label>
                                    <input
                                        id="max_seats_per_booking"
                                        type="number"
                                        min="1"
                                        max="20"
                                        value={operasionalForm.data.max_seats_per_booking}
                                        onChange={(e) => operasionalForm.setData('max_seats_per_booking', e.target.value)}
                                        required
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label htmlFor="minimum_public_viewers" className="block text-xs font-bold text-text mb-1">
                                        Batas Kuorum Sesi Publik *
                                    </label>
                                    <input
                                        id="minimum_public_viewers"
                                        type="number"
                                        min="1"
                                        max="6"
                                        value={operasionalForm.data.minimum_public_viewers}
                                        onChange={(e) => operasionalForm.setData('minimum_public_viewers', e.target.value)}
                                        required
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                                <div>
                                    <label htmlFor="booking_cutoff_minutes" className="block text-xs font-bold text-text mb-1">
                                        Tutup Booking Sebelum Mulai (Menit)
                                    </label>
                                    <input
                                        id="booking_cutoff_minutes"
                                        type="number"
                                        min="0"
                                        max="1440"
                                        value={operasionalForm.data.booking_cutoff_minutes}
                                        onChange={(e) => operasionalForm.setData('booking_cutoff_minutes', e.target.value)}
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label htmlFor="cancellation_cutoff_hours" className="block text-xs font-bold text-text mb-1">
                                        Batas Pengajuan Batal Tiket (Jam)
                                    </label>
                                    <input
                                        id="cancellation_cutoff_hours"
                                        type="number"
                                        min="0"
                                        max="168"
                                        value={operasionalForm.data.cancellation_cutoff_hours}
                                        onChange={(e) => operasionalForm.setData('cancellation_cutoff_hours', e.target.value)}
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label htmlFor="check_in_open_minutes_before" className="block text-xs font-bold text-text mb-1">
                                        Scan Masuk Dibuka Sebelum Mulai (Menit)
                                    </label>
                                    <input
                                        id="check_in_open_minutes_before"
                                        type="number"
                                        min="0"
                                        max="180"
                                        value={operasionalForm.data.check_in_open_minutes_before}
                                        onChange={(e) => operasionalForm.setData('check_in_open_minutes_before', e.target.value)}
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div className="pt-2 space-y-3">
                                <label className="flex items-center gap-2.5 cursor-pointer text-xs font-semibold text-text">
                                    <input
                                        type="checkbox"
                                        checked={operasionalForm.data.check_in_sound_enabled}
                                        onChange={(e) => operasionalForm.setData('check_in_sound_enabled', e.target.checked)}
                                        className="size-4 rounded border-border bg-surface-2 text-amber-500 focus:ring-amber-400"
                                    />
                                    <span>Aktifkan Suara Beep saat Scan Tiket Masuk Berhasil</span>
                                </label>

                                <label className="flex items-center gap-2.5 cursor-pointer text-xs font-semibold text-text">
                                    <input
                                        type="checkbox"
                                        checked={operasionalForm.data.snacks_enabled}
                                        onChange={(e) => operasionalForm.setData('snacks_enabled', e.target.checked)}
                                        className="size-4 rounded border-border bg-surface-2 text-amber-500 focus:ring-amber-400"
                                    />
                                    <span>Aktifkan Menu Jajan / Concession (Pelanggan dapat memesan makanan & minuman sebelum checkout)</span>
                                </label>
                            </div>

                            {/* Mode Pemeliharaan */}
                            <div className="pt-4 border-t border-border space-y-3">
                                <label className="flex items-center gap-2.5 cursor-pointer text-xs font-bold text-rose-500">
                                    <input
                                        type="checkbox"
                                        checked={operasionalForm.data.maintenance_mode}
                                        onChange={(e) => operasionalForm.setData('maintenance_mode', e.target.checked)}
                                        className="size-4 rounded border-rose-500 text-rose-600 focus:ring-rose-400"
                                    />
                                    <span>Mode Pemeliharaan (Maintenance Mode)</span>
                                </label>
                                {operasionalForm.data.maintenance_mode && (
                                    <input
                                        type="text"
                                        placeholder="Pesan pemeliharaan untuk pengunjung..."
                                        value={operasionalForm.data.maintenance_message}
                                        onChange={(e) => operasionalForm.setData('maintenance_message', e.target.value)}
                                        className="min-h-10 w-full rounded-xl border border-rose-500/50 bg-surface-2 px-3 text-xs text-text focus:outline-none"
                                    />
                                )}
                            </div>

                            <div className="pt-4 border-t border-border flex justify-end">
                                <button
                                    type="submit"
                                    disabled={operasionalForm.processing}
                                    className="btn-gold-cinema inline-flex min-h-[44px] items-center gap-2 rounded-xl px-5 text-xs font-bold shadow-none disabled:opacity-50"
                                >
                                    <Save className="size-4" />
                                    <span>{operasionalForm.processing ? 'Menyimpan...' : 'Simpan Operasional'}</span>
                                </button>
                            </div>
                        </div>
                    </form>
                )}

                {/* TAB 5: HARGA & PEMBAYARAN */}
                {activeTab === 'harga' && (
                    <form onSubmit={submitHarga} className="space-y-6">
                        <div className="rounded-2xl border border-border bg-surface p-6 shadow-sm space-y-5">
                            <h2 className="text-sm font-bold uppercase tracking-wider text-text pb-2 border-b border-border">
                                Tarif Tiket Studio
                            </h2>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label htmlFor="weekday_price" className="block text-xs font-bold text-text mb-1">
                                        Tarif Reguler Senin–Kamis (Rp) *
                                    </label>
                                    <input
                                        id="weekday_price"
                                        type="number"
                                        value={hargaForm.data.weekday_price}
                                        onChange={(e) => hargaForm.setData('weekday_price', e.target.value)}
                                        required
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label htmlFor="weekend_price" className="block text-xs font-bold text-text mb-1">
                                        Tarif Weekend Jumat–Minggu (Rp) *
                                    </label>
                                    <input
                                        id="weekend_price"
                                        type="number"
                                        value={hargaForm.data.weekend_price}
                                        onChange={(e) => hargaForm.setData('weekend_price', e.target.value)}
                                        required
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label htmlFor="holiday_price" className="block text-xs font-bold text-text mb-1">
                                        Tarif Hari Libur Nasional (Rp) *
                                    </label>
                                    <input
                                        id="holiday_price"
                                        type="number"
                                        value={hargaForm.data.holiday_price}
                                        onChange={(e) => hargaForm.setData('holiday_price', e.target.value)}
                                        required
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div>
                                    <label htmlFor="couple_price" className="block text-xs font-bold text-text mb-1">
                                        Paket Couple 2 Orang (Rp) *
                                    </label>
                                    <input
                                        id="couple_price"
                                        type="number"
                                        value={hargaForm.data.couple_price}
                                        onChange={(e) => hargaForm.setData('couple_price', e.target.value)}
                                        required
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label htmlFor="private_price" className="block text-xs font-bold text-text mb-1">
                                        Sewa Studio Privat 6 Orang (Rp) *
                                    </label>
                                    <input
                                        id="private_price"
                                        type="number"
                                        value={hargaForm.data.private_price}
                                        onChange={(e) => hargaForm.setData('private_price', e.target.value)}
                                        required
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>
                            </div>

                            {/* Metode Pembayaran */}
                            <h2 className="text-sm font-bold uppercase tracking-wider text-text pt-4 pb-2 border-b border-border">
                                Metode Pembayaran Diterima
                            </h2>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <label className={`p-4 rounded-xl border cursor-pointer transition ${
                                    selectedMethods.includes('di_tempat') ? 'border-amber-500 bg-amber-500/10' : 'border-border bg-surface-2'
                                }`}>
                                    <div className="flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            checked={selectedMethods.includes('di_tempat')}
                                            onChange={(e) => {
                                                if (e.target.checked) {
                                                    setSelectedMethods([...selectedMethods, 'di_tempat']);
                                                } else {
                                                    setSelectedMethods(selectedMethods.filter(m => m !== 'di_tempat'));
                                                }
                                            }}
                                            className="size-4 rounded border-border bg-surface text-amber-500 focus:ring-amber-400"
                                        />
                                        <div>
                                            <p className="text-xs font-bold text-text">Bayar di Kasir (Walk-in)</p>
                                            <p className="text-[11px] text-text-muted mt-0.5">Penonton bayar tunai / offline di kasir bioskop</p>
                                        </div>
                                    </div>
                                </label>

                                <label className={`p-4 rounded-xl border cursor-pointer transition ${
                                    selectedMethods.includes('qris') ? 'border-amber-500 bg-amber-500/10' : 'border-border bg-surface-2'
                                }`}>
                                    <div className="flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            checked={selectedMethods.includes('qris')}
                                            onChange={(e) => {
                                                if (e.target.checked) {
                                                    setSelectedMethods([...selectedMethods, 'qris']);
                                                } else {
                                                    setSelectedMethods(selectedMethods.filter(m => m !== 'qris'));
                                                }
                                            }}
                                            className="size-4 rounded border-border bg-surface text-amber-500 focus:ring-amber-400"
                                        />
                                        <div>
                                            <p className="text-xs font-bold text-text">QRIS (Semua E-Wallet & M-Banking)</p>
                                            <p className="text-[11px] text-text-muted mt-0.5">Pengunjung scan kode QRIS langsung di layar</p>
                                        </div>
                                    </div>
                                </label>

                                <label className={`p-4 rounded-xl border cursor-pointer transition ${
                                    selectedMethods.includes('transfer') ? 'border-amber-500 bg-amber-500/10' : 'border-border bg-surface-2'
                                }`}>
                                    <div className="flex items-center gap-3">
                                        <input
                                            type="checkbox"
                                            checked={selectedMethods.includes('transfer')}
                                            onChange={(e) => {
                                                if (e.target.checked) {
                                                    setSelectedMethods([...selectedMethods, 'transfer']);
                                                } else {
                                                    setSelectedMethods(selectedMethods.filter(m => m !== 'transfer'));
                                                }
                                            }}
                                            className="size-4 rounded border-border bg-surface text-amber-500 focus:ring-amber-400"
                                        />
                                        <div>
                                            <p className="text-xs font-bold text-text">Transfer Bank Manual</p>
                                            <p className="text-[11px] text-text-muted mt-0.5">Transfer rekening bank & unggah bukti transfer</p>
                                        </div>
                                    </div>
                                </label>
                            </div>

                            {/* QRIS Upload */}
                            {selectedMethods.includes('qris') && (
                                <div className="p-4 rounded-xl border border-border bg-surface-2 space-y-3">
                                    <h3 className="text-xs font-bold text-text">Pengaturan Kode QRIS</h3>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label htmlFor="qris_merchant_name" className="block text-xs font-bold text-text mb-1">
                                                Nama Merchant QRIS
                                            </label>
                                            <input
                                                id="qris_merchant_name"
                                                type="text"
                                                value={hargaForm.data.qris_merchant_name}
                                                onChange={(e) => hargaForm.setData('qris_merchant_name', e.target.value)}
                                                className="min-h-10 w-full rounded-xl border border-border bg-surface px-3 text-xs text-text"
                                            />
                                        </div>

                                        <div>
                                            <label className="block text-xs font-bold text-text mb-1">
                                                Unggah Gambar QRIS Persegi (1:1)
                                            </label>
                                            <input
                                                type="file"
                                                accept="image/png,image/jpeg,image/webp"
                                                onChange={(e) => hargaForm.setData('qris_image', e.target.files[0])}
                                                className="text-xs text-text file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-surface file:text-text hover:file:bg-border"
                                            />
                                        </div>
                                    </div>

                                    {settings.qris_image_url && (
                                        <div className="pt-2 flex items-center gap-4">
                                            <div className="p-2 bg-white rounded-xl border border-border inline-block">
                                                <img src={settings.qris_image_url} alt="QRIS Aktif" className="size-24 object-contain" />
                                            </div>
                                            <div>
                                                <p className="text-xs font-bold text-text">QRIS Aktif Tersimpan</p>
                                                <p className="text-[11px] text-text-muted">Siap dipindai oleh penonton saat checkout.</p>
                                                <button
                                                    type="button"
                                                    onClick={() => router.delete('/admin/pengaturan/qris')}
                                                    className="mt-2 inline-flex items-center gap-1 text-[11px] font-bold text-rose-500 hover:underline"
                                                >
                                                    <Trash2 className="size-3" />
                                                    <span>Hapus QRIS</span>
                                                </button>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Rekening Bank */}
                            {selectedMethods.includes('transfer') && (
                                <div className="p-4 rounded-xl border border-border bg-surface-2 space-y-3">
                                    <div className="flex items-center justify-between">
                                        <h3 className="text-xs font-bold text-text">Daftar Rekening Bank Transfer</h3>
                                        <button
                                            type="button"
                                            onClick={() => setTransferAccounts([...transferAccounts, { bank: '', account_number: '', account_holder: '' }])}
                                            className="inline-flex items-center gap-1 text-xs font-bold text-amber-600 dark:text-amber-400 hover:underline"
                                        >
                                            <Plus className="size-3.5" />
                                            <span>Tambah Rekening</span>
                                        </button>
                                    </div>

                                    <div className="space-y-2">
                                        {transferAccounts.map((acc, idx) => (
                                            <div key={idx} className="grid grid-cols-1 sm:grid-cols-4 gap-2 p-2.5 rounded-xl border border-border bg-surface">
                                                <input
                                                    type="text"
                                                    value={acc.bank}
                                                    onChange={(e) => {
                                                        const updated = [...transferAccounts];
                                                        updated[idx].bank = e.target.value;
                                                        setTransferAccounts(updated);
                                                    }}
                                                    placeholder="Nama Bank (BCA / Mandiri)"
                                                    className="min-h-10 rounded-xl border border-border bg-surface-2 px-3 text-xs text-text"
                                                />
                                                <input
                                                    type="text"
                                                    value={acc.account_number}
                                                    onChange={(e) => {
                                                        const updated = [...transferAccounts];
                                                        updated[idx].account_number = e.target.value;
                                                        setTransferAccounts(updated);
                                                    }}
                                                    placeholder="Nomor Rekening"
                                                    className="min-h-10 rounded-xl border border-border bg-surface-2 px-3 text-xs text-text"
                                                />
                                                <input
                                                    type="text"
                                                    value={acc.account_holder}
                                                    onChange={(e) => {
                                                        const updated = [...transferAccounts];
                                                        updated[idx].account_holder = e.target.value;
                                                        setTransferAccounts(updated);
                                                    }}
                                                    placeholder="Atas Nama Pemilik"
                                                    className="min-h-10 rounded-xl border border-border bg-surface-2 px-3 text-xs text-text"
                                                />
                                                <div className="flex items-center justify-end">
                                                    <button
                                                        type="button"
                                                        onClick={() => setTransferAccounts(transferAccounts.filter((_, i) => i !== idx))}
                                                        className="size-9 flex items-center justify-center rounded-lg text-rose-500 hover:bg-rose-500/10"
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}

                            <div className="pt-4 border-t border-border flex justify-end">
                                <button
                                    type="submit"
                                    disabled={hargaForm.processing}
                                    className="btn-gold-cinema inline-flex min-h-[44px] items-center gap-2 rounded-xl px-5 text-xs font-bold shadow-none disabled:opacity-50"
                                >
                                    <Save className="size-4" />
                                    <span>{hargaForm.processing ? 'Menyimpan...' : 'Simpan Harga & Pembayaran'}</span>
                                </button>
                            </div>
                        </div>
                    </form>
                )}

                {/* TAB 6: KONTAK & SOSIAL */}
                {activeTab === 'kontak' && (
                    <form onSubmit={submitKontak} className="space-y-6">
                        <div className="rounded-2xl border border-border bg-surface p-6 shadow-sm space-y-5">
                            <h2 className="text-sm font-bold uppercase tracking-wider text-text pb-2 border-b border-border">
                                Informasi Kontak & Media Sosial
                            </h2>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label htmlFor="contact_whatsapp" className="block text-xs font-bold text-text mb-1">
                                        Nomor WhatsApp Admin (08xx / 628xx)
                                    </label>
                                    <input
                                        id="contact_whatsapp"
                                        type="text"
                                        value={kontakForm.data.contact_whatsapp}
                                        onChange={(e) => kontakForm.setData('contact_whatsapp', e.target.value)}
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label htmlFor="contact_phone" className="block text-xs font-bold text-text mb-1">
                                        Nomor Telepon Studio
                                    </label>
                                    <input
                                        id="contact_phone"
                                        type="text"
                                        value={kontakForm.data.contact_phone}
                                        onChange={(e) => kontakForm.setData('contact_phone', e.target.value)}
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label htmlFor="contact_email" className="block text-xs font-bold text-text mb-1">
                                        Email Layanan Pelanggan
                                    </label>
                                    <input
                                        id="contact_email"
                                        type="email"
                                        value={kontakForm.data.contact_email}
                                        onChange={(e) => kontakForm.setData('contact_email', e.target.value)}
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div>
                                <label htmlFor="contact_address" className="block text-xs font-bold text-text mb-1">
                                    Alamat Lengkap Studio
                                </label>
                                <textarea
                                    id="contact_address"
                                    rows="2"
                                    value={kontakForm.data.contact_address}
                                    onChange={(e) => kontakForm.setData('contact_address', e.target.value)}
                                    className="w-full rounded-xl border border-border bg-surface-2 p-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                />
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="contact_maps_url" className="block text-xs font-bold text-text mb-1">
                                        Tautan Google Maps Lokasi
                                    </label>
                                    <input
                                        id="contact_maps_url"
                                        type="url"
                                        value={kontakForm.data.contact_maps_url}
                                        onChange={(e) => kontakForm.setData('contact_maps_url', e.target.value)}
                                        placeholder="https://maps.google.com/..."
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>

                                <div className="flex items-center pt-5">
                                    <label className="flex items-center gap-2.5 cursor-pointer text-xs font-semibold text-text">
                                        <input
                                            type="checkbox"
                                            checked={kontakForm.data.show_floating_whatsapp}
                                            onChange={(e) => kontakForm.setData('show_floating_whatsapp', e.target.checked)}
                                            className="size-4 rounded border-border bg-surface-2 text-amber-500 focus:ring-amber-400"
                                        />
                                        <span>Tampilkan Tombol WhatsApp Melayang (Floating Button)</span>
                                    </label>
                                </div>
                            </div>

                            {/* Media Sosial */}
                            <h2 className="text-sm font-bold uppercase tracking-wider text-text pt-4 pb-2 border-b border-border">
                                Akun Media Sosial
                            </h2>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label htmlFor="instagram" className="block text-xs font-bold text-text mb-1">
                                        Instagram URL
                                    </label>
                                    <input
                                        id="instagram"
                                        type="text"
                                        value={kontakForm.data.instagram}
                                        onChange={(e) => kontakForm.setData('instagram', e.target.value)}
                                        placeholder="https://instagram.com/..."
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label htmlFor="tiktok" className="block text-xs font-bold text-text mb-1">
                                        TikTok URL
                                    </label>
                                    <input
                                        id="tiktok"
                                        type="text"
                                        value={kontakForm.data.tiktok}
                                        onChange={(e) => kontakForm.setData('tiktok', e.target.value)}
                                        placeholder="https://tiktok.com/@..."
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label htmlFor="youtube" className="block text-xs font-bold text-text mb-1">
                                        YouTube URL
                                    </label>
                                    <input
                                        id="youtube"
                                        type="text"
                                        value={kontakForm.data.youtube}
                                        onChange={(e) => kontakForm.setData('youtube', e.target.value)}
                                        placeholder="https://youtube.com/@..."
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div className="pt-4 border-t border-border flex justify-end">
                                <button
                                    type="submit"
                                    disabled={kontakForm.processing}
                                    className="btn-gold-cinema inline-flex min-h-[44px] items-center gap-2 rounded-xl px-5 text-xs font-bold shadow-none disabled:opacity-50"
                                >
                                    <Save className="size-4" />
                                    <span>{kontakForm.processing ? 'Menyimpan...' : 'Simpan Kontak'}</span>
                                </button>
                            </div>
                        </div>
                    </form>
                )}

                {/* TAB 7: SEO & FOOTER */}
                {activeTab === 'seo' && (
                    <form onSubmit={submitSeo} className="space-y-6">
                        <div className="rounded-2xl border border-border bg-surface p-6 shadow-sm space-y-5">
                            <h2 className="text-sm font-bold uppercase tracking-wider text-text pb-2 border-b border-border">
                                Optimasi Mesin Pencari (SEO) & Hak Cipta
                            </h2>

                            <div>
                                <label htmlFor="seo_title_template" className="block text-xs font-bold text-text mb-1">
                                    Format Judul Halaman (Template Title)
                                </label>
                                <input
                                    id="seo_title_template"
                                    type="text"
                                    value={seoForm.data.seo_title_template}
                                    onChange={(e) => seoForm.setData('seo_title_template', e.target.value)}
                                    placeholder="%s | SynemaX"
                                    className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                />
                                <p className="text-[11px] text-text-muted mt-1">Gunakan %s sebagai pengganti judul halaman aktif.</p>
                            </div>

                            <div>
                                <label htmlFor="seo_meta_description" className="block text-xs font-bold text-text mb-1">
                                    Deskripsi Meta (Google Search Snippet)
                                </label>
                                <textarea
                                    id="seo_meta_description"
                                    rows="3"
                                    value={seoForm.data.seo_meta_description}
                                    onChange={(e) => seoForm.setData('seo_meta_description', e.target.value)}
                                    className="w-full rounded-xl border border-border bg-surface-2 p-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                />
                            </div>

                            <div>
                                <label htmlFor="formatted_footer_text" className="block text-xs font-bold text-text mb-1">
                                    Teks Hak Cipta Footer
                                </label>
                                <input
                                    id="formatted_footer_text"
                                    type="text"
                                    value={seoForm.data.formatted_footer_text}
                                    onChange={(e) => seoForm.setData('formatted_footer_text', e.target.value)}
                                    className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                />
                            </div>

                            <div className="pt-4 border-t border-border flex justify-end">
                                <button
                                    type="submit"
                                    disabled={seoForm.processing}
                                    className="btn-gold-cinema inline-flex min-h-[44px] items-center gap-2 rounded-xl px-5 text-xs font-bold shadow-none disabled:opacity-50"
                                >
                                    <Save className="size-4" />
                                    <span>{seoForm.processing ? 'Menyimpan...' : 'Simpan SEO & Footer'}</span>
                                </button>
                            </div>
                        </div>
                    </form>
                )}

                {/* TAB 8: NOTIFIKASI TELEGRAM */}
                {activeTab === 'notifikasi' && (
                    <form onSubmit={submitTelegram} className="space-y-6">
                        <div className="rounded-2xl border border-border bg-surface p-6 shadow-sm space-y-5">
                            <div className="flex items-center justify-between pb-2 border-b border-border">
                                <div>
                                    <h2 className="text-sm font-bold uppercase tracking-wider text-text">
                                        Integrasi Notifikasi Bot Telegram
                                    </h2>
                                    <p className="text-xs text-text-muted mt-0.5">
                                        Dapatkan notifikasi instan langsung di Telegram saat pesanan baru masuk atau bukti bayar diunggah.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    onClick={handleTestTelegram}
                                    className="inline-flex min-h-[38px] items-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 py-1.5 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 transition"
                                >
                                    <Send className="size-3.5" />
                                    <span>Tes Kirim Pesan</span>
                                </button>
                            </div>

                            <label className="flex items-center gap-2.5 cursor-pointer text-xs font-bold text-text">
                                <input
                                    type="checkbox"
                                    checked={telegramForm.data.telegram_notifications_enabled}
                                    onChange={(e) => telegramForm.setData('telegram_notifications_enabled', e.target.checked)}
                                    className="size-4 rounded border-border bg-surface-2 text-amber-500 focus:ring-amber-400"
                                />
                                <span>Aktifkan Notifikasi Bot Telegram</span>
                            </label>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label htmlFor="telegram_bot_token" className="block text-xs font-bold text-text mb-1">
                                        HTTP API Bot Token (dari @BotFather)
                                    </label>
                                    <input
                                        id="telegram_bot_token"
                                        type="password"
                                        placeholder={telegramSettings.masked_token ? `Tersimpan: ${telegramSettings.masked_token}` : '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11'}
                                        value={telegramForm.data.telegram_bot_token}
                                        onChange={(e) => telegramForm.setData('telegram_bot_token', e.target.value)}
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label htmlFor="telegram_chat_ids" className="block text-xs font-bold text-text mb-1">
                                        Chat ID Penerima (Pisahkan koma jika banyak)
                                    </label>
                                    <input
                                        id="telegram_chat_ids"
                                        type="text"
                                        placeholder="123456789, -987654321"
                                        value={telegramForm.data.telegram_chat_ids}
                                        onChange={(e) => telegramForm.setData('telegram_chat_ids', e.target.value)}
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>
                            </div>

                            {/* Trigger Event Toggles */}
                            <div className="pt-3 border-t border-border">
                                <p className="text-xs font-bold text-text mb-3">Pilih Kejadian yang Memicu Notifikasi:</p>
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    {[
                                        { key: 'telegram_notify_booking_created', label: 'Booking Tiket Baru Masuk' },
                                        { key: 'telegram_notify_proof_uploaded', label: 'Bukti Pembayaran Diunggah Penonton' },
                                        { key: 'telegram_notify_payment_confirmed', label: 'Pembayaran Dikonfirmasi Kasir' },
                                        { key: 'telegram_notify_booking_cancelled', label: 'Booking Dibatalkan' },
                                        { key: 'telegram_notify_booking_expired', label: 'Booking Kedaluwarsa' },
                                        { key: 'telegram_notify_session_confirmed', label: 'Sesi Tayang Siap & Kuorum Terpenuhi' },
                                    ].map((evt) => (
                                        <label key={evt.key} className="flex items-center gap-2.5 cursor-pointer text-xs font-medium text-text">
                                            <input
                                                type="checkbox"
                                                checked={telegramForm.data[evt.key]}
                                                onChange={(e) => telegramForm.setData(evt.key, e.target.checked)}
                                                className="size-4 rounded border-border bg-surface-2 text-amber-500 focus:ring-amber-400"
                                            />
                                            <span>{evt.label}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>

                            <div className="pt-4 border-t border-border flex justify-end">
                                <button
                                    type="submit"
                                    disabled={telegramForm.processing}
                                    className="btn-gold-cinema inline-flex min-h-[44px] items-center gap-2 rounded-xl px-5 text-xs font-bold shadow-none disabled:opacity-50"
                                >
                                    <Save className="size-4" />
                                    <span>{telegramForm.processing ? 'Menyimpan...' : 'Simpan Pengaturan Telegram'}</span>
                                </button>
                            </div>
                        </div>
                    </form>
                )}

                {/* TAB 9: MEMBER & LOYALITAS */}
                {activeTab === 'member' && (
                    <form onSubmit={submitMember} className="space-y-6">
                        <div className="rounded-2xl border border-border bg-surface p-6 shadow-sm space-y-5">
                            <h2 className="text-sm font-bold uppercase tracking-wider text-text pb-2 border-b border-border">
                                Program Kartu Member & Hadiah Loyalitas
                            </h2>

                            <label className="flex items-center gap-2.5 cursor-pointer text-xs font-bold text-text">
                                <input
                                    type="checkbox"
                                    checked={memberForm.data.is_member_program_enabled}
                                    onChange={(e) => memberForm.setData('is_member_program_enabled', e.target.checked)}
                                    className="size-4 rounded border-border bg-surface-2 text-amber-500 focus:ring-amber-400"
                                />
                                <span>Aktifkan Fitur Kartu Member & Loyalitas Kunjungan</span>
                            </label>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div>
                                    <label htmlFor="member_discount_visit" className="block text-xs font-bold text-text mb-1">
                                        Kunjungan ke- (Reward Diskon) *
                                    </label>
                                    <input
                                        id="member_discount_visit"
                                        type="number"
                                        min="1"
                                        max="50"
                                        value={memberForm.data.member_discount_visit}
                                        onChange={(e) => memberForm.setData('member_discount_visit', e.target.value)}
                                        required
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                    <p className="text-[11px] text-text-muted mt-1">Standar SynemaX: Kunjungan ke-5 dapat diskon.</p>
                                </div>

                                <div>
                                    <label htmlFor="member_discount_percent" className="block text-xs font-bold text-text mb-1">
                                        Besaran Diskon (%) *
                                    </label>
                                    <input
                                        id="member_discount_percent"
                                        type="number"
                                        min="1"
                                        max="100"
                                        value={memberForm.data.member_discount_percent}
                                        onChange={(e) => memberForm.setData('member_discount_percent', e.target.value)}
                                        required
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                                <div>
                                    <label htmlFor="member_free_visit" className="block text-xs font-bold text-text mb-1">
                                        Kunjungan ke- (Reward Tiket Gratis) *
                                    </label>
                                    <input
                                        id="member_free_visit"
                                        type="number"
                                        min="1"
                                        max="50"
                                        value={memberForm.data.member_free_visit}
                                        onChange={(e) => memberForm.setData('member_free_visit', e.target.value)}
                                        required
                                        className="min-h-11 w-full rounded-xl border border-border bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none"
                                    />
                                    <p className="text-[11px] text-text-muted mt-1">Standar SynemaX: Kunjungan ke-10 tiket gratis 1x.</p>
                                </div>
                            </div>

                            <div className="pt-4 border-t border-border flex justify-end">
                                <button
                                    type="submit"
                                    disabled={memberForm.processing}
                                    className="btn-gold-cinema inline-flex min-h-[44px] items-center gap-2 rounded-xl px-5 text-xs font-bold shadow-none disabled:opacity-50"
                                >
                                    <Save className="size-4" />
                                    <span>{memberForm.processing ? 'Menyimpan...' : 'Simpan Pengaturan Member'}</span>
                                </button>
                            </div>
                        </div>
                    </form>
                )}
            </div>
        </AdminLayout>
    );
}
