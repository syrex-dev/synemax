import { Head, useForm } from '@inertiajs/react';
import GuestLayout from '../../Layouts/GuestLayout';

export default function ResetPassword({ token, email }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        token: token,
        email: email || '',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post('/reset-password', {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Buat Kata Sandi Baru" />

            <div>
                <h1 className="text-xl sm:text-2xl font-black text-text">Buat kata sandi baru</h1>

                <form onSubmit={submit} className="mt-5 space-y-4">
                    <input type="hidden" name="token" value={data.token} />

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
                            } bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400`}
                        />
                        {errors.email && (
                            <p className="mt-1 text-xs text-rose-500">{errors.email}</p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="password" className="block text-xs font-bold text-text mb-1">
                            Kata sandi baru
                        </label>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            required
                            autoComplete="new-password"
                            className={`min-h-11 w-full rounded-xl border ${
                                errors.password ? 'border-rose-500' : 'border-border'
                            } bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400`}
                        />
                        {errors.password && (
                            <p className="mt-1 text-xs text-rose-500">{errors.password}</p>
                        )}
                    </div>

                    <div>
                        <label htmlFor="password_confirmation" className="block text-xs font-bold text-text mb-1">
                            Ulangi kata sandi baru
                        </label>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            value={data.password_confirmation}
                            onChange={(e) => setData('password_confirmation', e.target.value)}
                            required
                            autoComplete="new-password"
                            className={`min-h-11 w-full rounded-xl border ${
                                errors.password_confirmation ? 'border-rose-500' : 'border-border'
                            } bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400`}
                        />
                        {errors.password_confirmation && (
                            <p className="mt-1 text-xs text-rose-500">{errors.password_confirmation}</p>
                        )}
                    </div>

                    <div className="pt-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="btn-gold-cinema inline-flex min-h-12 w-full items-center justify-center rounded-xl text-sm font-bold shadow-none disabled:opacity-50"
                        >
                            {processing ? 'Menyimpan...' : 'Simpan kata sandi baru'}
                        </button>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
