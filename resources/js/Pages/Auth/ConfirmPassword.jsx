import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '../../Layouts/GuestLayout';

export default function ConfirmPassword() {
    const { data, setData, post, processing, errors, reset } = useForm({
        password: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/confirm-password', {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Konfirmasi Kata Sandi" />

            <div>
                <h1 className="text-xl sm:text-2xl font-black text-text">Konfirmasi kata sandi</h1>
                <p className="mt-1 text-xs text-text-muted">
                    Masukkan ulang kata sandi untuk melanjutkan ke bagian yang aman.
                </p>

                <form onSubmit={submit} className="mt-5 space-y-4">
                    <div>
                        <label htmlFor="password" className="block text-xs font-bold text-text mb-1">
                            Kata sandi
                        </label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            required
                            autoFocus
                            autoComplete="current-password"
                            className={`min-h-11 w-full rounded-xl border ${
                                errors.password ? 'border-rose-500' : 'border-border'
                            } bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400`}
                        />
                        {errors.password && (
                            <p className="mt-1 text-xs text-rose-500">{errors.password}</p>
                        )}
                    </div>

                    <div className="pt-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="btn-gold-cinema inline-flex min-h-12 w-full items-center justify-center rounded-xl text-sm font-bold shadow-none disabled:opacity-50"
                        >
                            {processing ? 'Memproses...' : 'Konfirmasi'}
                        </button>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
