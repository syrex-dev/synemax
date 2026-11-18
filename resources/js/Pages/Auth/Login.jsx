import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import GuestLayout from '../../Layouts/GuestLayout';
import { Eye, EyeOff } from 'lucide-react';

export default function Login({ status, canResetPassword = true }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const [showPassword, setShowPassword] = useState(false);

    const submit = (e) => {
        e.preventDefault();
        post('/login', {
            onFinish: () => reset('password'),
        });
    };

    const hasErrors = Object.keys(errors).length > 0;
    const generalErrorMessage = errors.email || errors.password || (hasErrors ? 'Email atau kata sandi tidak cocok.' : null);

    return (
        <GuestLayout>
            <Head title="Masuk Admin" />

            <div>
                <h1 className="text-xl sm:text-2xl font-black text-text">Masuk admin</h1>
                <p className="mt-1 text-xs text-text-muted">Kelola jadwal, film, dan booking SynemaX.</p>

                {status && (
                    <div className="mt-3 rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-3 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                        {status}
                    </div>
                )}

                {hasErrors && generalErrorMessage && (
                    <div role="alert" className="mt-3 rounded-xl border border-rose-500/40 bg-rose-500/10 p-3 text-xs font-semibold text-rose-600 dark:text-rose-400">
                        <p>{generalErrorMessage}</p>
                    </div>
                )}

                <form onSubmit={submit} className="mt-5 space-y-4" noValidate>
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
                            inputMode="email"
                            autoComplete="username"
                            autoCapitalize="none"
                            spellCheck="false"
                            enterKeyHint="next"
                            className={`min-h-11 w-full rounded-xl border ${
                                errors.email ? 'border-rose-500' : 'border-border'
                            } bg-surface-2 px-3 text-sm text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400`}
                        />
                    </div>

                    <div>
                        <label htmlFor="password" className="block text-xs font-bold text-text mb-1">
                            Kata sandi
                        </label>
                        <div className="relative">
                            <input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                name="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                                required
                                autoComplete="current-password"
                                autoCapitalize="none"
                                spellCheck="false"
                                enterKeyHint="done"
                                className={`min-h-11 w-full rounded-xl border ${
                                    errors.password || hasErrors ? 'border-rose-500' : 'border-border'
                                } bg-surface-2 pl-3 pr-11 text-sm text-text focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400`}
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword(!showPassword)}
                                aria-label={showPassword ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'}
                                className="absolute right-0 top-0 flex size-11 items-center justify-center text-text-muted hover:text-text focus:outline-none"
                            >
                                {showPassword ? (
                                    <EyeOff className="size-5" />
                                ) : (
                                    <Eye className="size-5" />
                                )}
                            </button>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-2 pt-1">
                        <label className="flex items-center gap-2 cursor-pointer text-xs font-semibold text-text">
                            <input
                                type="checkbox"
                                name="remember"
                                checked={data.remember}
                                onChange={(e) => setData('remember', e.target.checked)}
                                className="size-4 rounded border-border bg-surface-2 text-amber-500 focus:ring-amber-400"
                            />
                            <span>Ingat saya</span>
                        </label>

                        {canResetPassword && (
                            <Link
                                href="/forgot-password"
                                className="text-xs font-semibold text-amber-600 dark:text-amber-400 hover:underline"
                            >
                                Lupa kata sandi?
                            </Link>
                        )}
                    </div>

                    <div className="pt-2">
                        <button
                            type="submit"
                            disabled={processing}
                            className="btn-gold-cinema inline-flex min-h-12 w-full items-center justify-center rounded-xl text-sm font-bold shadow-none disabled:opacity-50"
                        >
                            {processing ? 'Memproses...' : 'Masuk'}
                        </button>
                    </div>
                </form>
            </div>
        </GuestLayout>
    );
}
