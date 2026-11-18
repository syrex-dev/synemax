import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '../../Layouts/GuestLayout';

export default function ForgotPassword({ status }) {
    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/forgot-password');
    };

    return (
        <GuestLayout>
            <Head title="Atur Ulang Kata Sandi" />

            <div>
                <h1 className="text-xl sm:text-2xl font-black text-text">Atur ulang kata sandi</h1>
                <p className="mt-1 text-xs text-text-muted">
                    Masukkan email akun admin. Kami akan mengirimkan tautan untuk membuat kata sandi baru.
                </p>

                {status && (
                    <div className="mt-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        {status}
                    </div>
                )}

                <form onSubmit={submit} className="mt-5 space-y-4">
                    <div>
                        <label htmlFor="email" className="block text-xs font-bold text-text mb-1">
                            Email admin
                        </label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                            autoFocus
                            autoComplete="username"
                            className={`min-h-11 w-full rounded-xl border ${
                                errors.email ? 'border-rose-500' : 'border-border'
                            } bg-surface-2 px-3 text-sm text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400`}
                        />
                        {errors.email && (
                            <p className="mt-1 text-xs text-rose-500">{errors.email}</p>
                        )}
                    </div>

                    <div className="pt-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="btn-gold-cinema inline-flex min-h-12 w-full items-center justify-center rounded-xl text-sm font-bold shadow-none disabled:opacity-50"
                        >
                            {processing ? 'Mengirim...' : 'Kirim tautan reset'}
                        </button>
                    </div>

                    <div className="text-center pt-2">
                        <Link
                            href="/login"
                            className="text-xs font-semibold text-text-muted hover:text-text transition-colors"
                        >
                            ← Kembali ke halaman masuk
                        </Link>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
