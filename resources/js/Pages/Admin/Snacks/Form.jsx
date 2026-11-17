import { useState, useRef } from 'react';
import { Link, Head, useForm } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import {
    Coffee,
    ArrowLeft,
    UploadCloud,
    X,
    Eye,
    AlertCircle,
} from 'lucide-react';

export default function Form({
    menuItem,
    categories = [],
}) {
    const isEdit = Boolean(menuItem?.id);
    const photoInputRef = useRef(null);
    const [photoPreview, setPhotoPreview] = useState(
        menuItem?.photo_path ? `/storage/${menuItem.photo_path}` : null
    );

    const form = useForm({
        _method: isEdit ? 'PUT' : 'POST',
        category_id: menuItem?.category_id || (categories[0]?.id ? String(categories[0].id) : ''),
        name: menuItem?.name || '',
        price: menuItem?.price || '',
        cost_price: menuItem?.cost_price || '',
        prep_minutes: menuItem?.prep_minutes || 0,
        sort_order: menuItem?.sort_order || 0,
        stock_mode: menuItem?.stock_mode || 'toggle',
        stock_qty: menuItem?.stock_qty !== null && menuItem?.stock_qty !== undefined ? menuItem.stock_qty : '',
        stock_alert_threshold: menuItem?.stock_alert_threshold !== undefined ? menuItem.stock_alert_threshold : 3,
        is_available: menuItem?.is_available !== undefined ? Boolean(menuItem.is_available) : true,
        is_active: menuItem?.is_active !== undefined ? Boolean(menuItem.is_active) : true,
        photo: null,
    });

    const handlePhotoChange = (e) => {
        const file = e.target.files?.[0];
        if (file) {
            form.setData('photo', file);
            const reader = new FileReader();
            reader.onload = (ev) => setPhotoPreview(ev.target?.result);
            reader.readAsDataURL(file);
        }
    };

    const handleRemovePhoto = () => {
        form.setData('photo', null);
        setPhotoPreview(menuItem?.photo_path ? `/storage/${menuItem.photo_path}` : null);
        if (photoInputRef.current) photoInputRef.current.value = '';
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        const url = isEdit ? `/admin/jajanan/menu/${menuItem.id}` : '/admin/jajanan/menu';
        form.post(url, {
            preserveScroll: true,
        });
    };

    return (
        <AdminLayout title={isEdit ? 'Ubah Menu Jajanan' : 'Tambah Menu Jajanan'}>
            <Head title={`${isEdit ? 'Ubah Menu' : 'Tambah Menu'} | Admin SynemaX`} />

            <div className="max-w-3xl mx-auto space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div className="flex items-center gap-2 text-xs font-semibold text-text-muted mb-1">
                            <Link href="/admin/jajanan" className="hover:text-amber-500 transition-colors">
                                Menu Jajanan
                            </Link>
                            <span>/</span>
                            <span className="text-text">{isEdit ? 'Ubah Menu' : 'Tambah Menu'}</span>
                        </div>
                        <h1 className="text-2xl font-black tracking-tight text-text">
                            {isEdit ? `Ubah Menu: ${menuItem.name}` : 'Tambah Menu Jajanan'}
                        </h1>
                        <p className="text-xs text-text-muted mt-1">
                            Lengkapi informasi harga, stok, waktu persiapan, dan foto jajan.
                        </p>
                    </div>

                    <Link
                        href="/admin/jajanan"
                        className="inline-flex min-h-[42px] items-center gap-1.5 rounded-xl border border-border bg-surface px-4 py-2 text-xs font-bold text-text hover:border-amber-500 hover:text-amber-500 transition-colors shadow-xs"
                    >
                        <ArrowLeft className="size-4" />
                        <span>Kembali ke Menu</span>
                    </Link>
                </div>

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-6">
                    <div className="rounded-2xl border border-border bg-surface p-6 shadow-xs space-y-6 text-xs">
                        {/* Section 1: Menu Info & Price */}
                        <div>
                            <h2 className="text-base font-bold text-text mb-4">Informasi Menu & Harga</h2>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="sm:col-span-2">
                                    <label className="block font-bold text-text mb-1">
                                        Nama Menu <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="text"
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                        placeholder="Contoh: Popcorn Caramel Jumbo"
                                        required
                                        maxLength={120}
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                    />
                                    {form.errors.name && (
                                        <p className="text-rose-500 mt-1 font-semibold">{form.errors.name}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block font-bold text-text mb-1">
                                        Kategori <span className="text-rose-500">*</span>
                                    </label>
                                    <select
                                        value={form.data.category_id}
                                        onChange={(e) => form.setData('category_id', e.target.value)}
                                        required
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-semibold text-text focus:border-amber-500 focus:outline-none"
                                    >
                                        <option value="">-- Pilih Kategori --</option>
                                        {categories.map((cat) => (
                                            <option key={cat.id} value={cat.id}>
                                                {cat.name} {!cat.is_active && '(Nonaktif)'}
                                            </option>
                                        ))}
                                    </select>
                                    {form.errors.category_id && (
                                        <p className="text-rose-500 mt-1 font-semibold">{form.errors.category_id}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block font-bold text-text mb-1">
                                        Harga Jual (Rp) <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        min={500}
                                        max={1000000}
                                        step={500}
                                        value={form.data.price}
                                        onChange={(e) => form.setData('price', e.target.value)}
                                        placeholder="Contoh: 15000"
                                        required
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                    />
                                    {form.errors.price && (
                                        <p className="text-rose-500 mt-1 font-semibold">{form.errors.price}</p>
                                    )}
                                </div>

                                <div>
                                    <label className="block font-bold text-text mb-1">Harga Modal (Opsional)</label>
                                    <input
                                        type="number"
                                        min={0}
                                        max={1000000}
                                        step={500}
                                        value={form.data.cost_price}
                                        onChange={(e) => form.setData('cost_price', e.target.value)}
                                        placeholder="Contoh: 8000"
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Section 2: Prep & Stock */}
                        <div className="pt-4 border-t border-border">
                            <h2 className="text-base font-bold text-text mb-4">Persiapan & Stok</h2>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block font-bold text-text mb-1">
                                        Waktu Persiapan (Menit) <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        min={0}
                                        max={600}
                                        value={form.data.prep_minutes}
                                        onChange={(e) => form.setData('prep_minutes', e.target.value)}
                                        required
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label className="block font-bold text-text mb-1">
                                        Urutan Tampil <span className="text-rose-500">*</span>
                                    </label>
                                    <input
                                        type="number"
                                        min={0}
                                        max={65535}
                                        value={form.data.sort_order}
                                        onChange={(e) => form.setData('sort_order', e.target.value)}
                                        required
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                    />
                                </div>

                                <div>
                                    <label className="block font-bold text-text mb-1">Mode Pengelolaan Stok</label>
                                    <select
                                        value={form.data.stock_mode}
                                        onChange={(e) => form.setData('stock_mode', e.target.value)}
                                        className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs font-semibold text-text focus:border-amber-500 focus:outline-none"
                                    >
                                        <option value="toggle">Toggle (Tersedia / Habis Saja)</option>
                                        <option value="angka">Stok Angka (Hitung Pengurangan)</option>
                                    </select>
                                </div>

                                {form.data.stock_mode === 'angka' && (
                                    <>
                                        <div>
                                            <label className="block font-bold text-text mb-1">
                                                Jumlah Stok Tersedia <span className="text-rose-500">*</span>
                                            </label>
                                            <input
                                                type="number"
                                                min={0}
                                                max={1000000}
                                                value={form.data.stock_qty}
                                                onChange={(e) => form.setData('stock_qty', e.target.value)}
                                                required
                                                className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                            />
                                        </div>

                                        <div>
                                            <label className="block font-bold text-text mb-1">
                                                Ambang Batas Peringatan Menipis
                                            </label>
                                            <input
                                                type="number"
                                                min={0}
                                                max={100000}
                                                value={form.data.stock_alert_threshold}
                                                onChange={(e) => form.setData('stock_alert_threshold', e.target.value)}
                                                required
                                                className="w-full rounded-xl border border-border bg-surface-2 px-3 py-2 text-xs text-text focus:border-amber-500 focus:outline-none"
                                            />
                                        </div>
                                    </>
                                )}
                            </div>
                        </div>

                        {/* Section 3: Foto & Ketersediaan */}
                        <div className="pt-4 border-t border-border space-y-4">
                            <h2 className="text-base font-bold text-text">Foto Menu & Ketersediaan</h2>

                            <div className="flex flex-col sm:flex-row items-start gap-4">
                                {photoPreview ? (
                                    <div className="relative group shrink-0 size-24 rounded-xl overflow-hidden border border-border bg-surface-2">
                                        <img
                                            src={photoPreview}
                                            alt="Preview Foto"
                                            className="w-full h-full object-cover"
                                        />
                                        <button
                                            type="button"
                                            onClick={handleRemovePhoto}
                                            className="absolute top-1 right-1 p-1 rounded-full bg-black/70 text-white hover:bg-rose-600 transition"
                                        >
                                            <X className="size-3" />
                                        </button>
                                    </div>
                                ) : (
                                    <div
                                        onClick={() => photoInputRef.current?.click()}
                                        className="size-24 rounded-xl border-2 border-dashed border-border bg-surface-2 flex flex-col items-center justify-center p-2 cursor-pointer hover:border-amber-500 transition text-center"
                                    >
                                        <UploadCloud className="size-5 text-text-muted mb-1" />
                                        <span className="text-[10px] font-bold text-text">Upload Foto</span>
                                    </div>
                                )}

                                <div className="space-y-2">
                                    <input
                                        ref={photoInputRef}
                                        type="file"
                                        accept="image/jpeg,image/png,image/webp"
                                        onChange={handlePhotoChange}
                                        className="hidden"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => photoInputRef.current?.click()}
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl border border-border bg-surface text-xs font-semibold text-text hover:border-amber-500 transition"
                                    >
                                        <UploadCloud className="size-3.5" />
                                        <span>Pilih Foto Menu</span>
                                    </button>
                                    <p className="text-[11px] text-text-muted">
                                        Format JPG/PNG/WebP, maks 4 MB (otomatis dikonversi ke WebP).
                                    </p>
                                    {form.errors.photo && (
                                        <p className="text-rose-500 text-xs font-semibold">{form.errors.photo}</p>
                                    )}
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center gap-6 pt-3">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={form.data.is_active}
                                        onChange={(e) => form.setData('is_active', e.target.checked)}
                                        className="rounded border-border text-amber-500 focus:ring-amber-500"
                                    />
                                    <span className="font-semibold text-text">Menu Aktif (Tampil di Daftar)</span>
                                </label>

                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={form.data.is_available}
                                        onChange={(e) => form.setData('is_available', e.target.checked)}
                                        className="rounded border-border text-amber-500 focus:ring-amber-500"
                                    />
                                    <span className="font-semibold text-text">Tersedia untuk Dipesan</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {/* Submit Bar */}
                    <div className="flex items-center justify-end gap-3">
                        <Link
                            href="/admin/jajanan"
                            className="rounded-xl border border-border px-5 py-2.5 text-xs font-semibold text-text hover:bg-surface-2"
                        >
                            Batal
                        </Link>
                        <button
                            type="submit"
                            disabled={form.processing}
                            className="rounded-xl bg-amber-500 px-6 py-2.5 text-xs font-bold text-slate-950 hover:bg-amber-400 shadow-xs disabled:opacity-50"
                        >
                            {form.processing ? 'Menyimpan...' : isEdit ? 'Simpan Perubahan' : 'Tambah Menu'}
                        </button>
                    </div>
                </form>
            </div>
        </AdminLayout>
    );
}

