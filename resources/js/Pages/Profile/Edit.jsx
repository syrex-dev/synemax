import { useState } from 'react';
import { Head, useForm, usePage } from '@inertiajs/react';
import AdminLayout from '../../Layouts/AdminLayout';
import { User, Lock, Check } from 'lucide-react';

export default function Edit({ status }) {
    const user = usePage().props.auth.user;

    // Profile form
    const profileForm = useForm({
        name: user.name || '',
        email: user.email || '',
    });

    const submitProfile = (e) => {
        e.preventDefault();
        profileForm.patch('/profile');
    };

    // Password form
    const passwordForm = useForm({
        current_password: '',
        password: '',
        password_confirmation: '',
    });

    const submitPassword = (e) => {
        e.preventDefault();
        passwordForm.put('/password', {
            onSuccess: () => passwordForm.reset(),
        });
    };

    return (
        <AdminLayout title="Profil Admin">
            <Head title="Profil Admin · Admin" />

            <div className="max-w-4xl mx-auto space-y-6">
                <div>
                    <h1 className="text-xl font-bold tracking-tight text-text sm:text-2xl">Profil Admin</h1>
                    <p className="text-xs text-text-muted mt-1">Kelola data identitas akun dan keamanan kata sandi Anda.</p>
                </div>

                {status === 'profile-updated' && (
                    <div className="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-2">
                        <Check className="size-4" />
                        <span>Informasi profil berhasil diperbarui.</span>
                    </div>
                )}

                {status === 'password-updated' && (
                    <div className="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-xs font-semibold text-emerald-600 dark:text-emerald-400 flex items-center gap-2">
                        <Check className="size-4" />
                        <span>Kata sandi berhasil diubah.</span>
                    </div>
                )}

                {/* Profile Information */}
                <div className="rounded-2xl border border-border bg-surface p-6 shadow-sm">
                    <div className="flex items-center gap-3 mb-4 pb-4 border-b border-border">
                        <div className="flex size-10 items-center justify-center rounded-xl bg-amber-500/15 text-amber-500">
                            <User className="size-5" />
                        </div>
                        <div>
                            <h2 className="text-base font-bold text-text">Informasi Profil</h2>
                            <p className="text-xs text-text-muted">Perbarui nama dan alamat email akun admin.</p>
                        </div>
                    </div>

                    <form onSubmit={submitProfile} className="max-w-xl space-y-4">
                        <div>
                            <label htmlFor="name" className="block text-xs font-bold text-text mb-1">
                                Nama
                            </label>
                            <input
                                id="name"
                                type="text"
                                name="name"
                                value={profileForm.data.name}
                                onChange={(e) => profileForm.setData('name', e.target.value)}
                                required
                                autoComplete="name"
                                className={`min-h-11 w-full rounded-xl border ${
                                    profileForm.errors.name ? 'border-rose-500' : 'border-border'
                                } bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400`}
                            />
                            {profileForm.errors.name && (
                                <p className="mt-1 text-xs text-rose-500">{profileForm.errors.name}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="email" className="block text-xs font-bold text-text mb-1">
                                Email
                            </label>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value={profileForm.data.email}
                                onChange={(e) => profileForm.setData('email', e.target.value)}
                                required
                                autoComplete="username"
                                className={`min-h-11 w-full rounded-xl border ${
                                    profileForm.errors.email ? 'border-rose-500' : 'border-border'
                                } bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400`}
                            />
                            {profileForm.errors.email && (
                                <p className="mt-1 text-xs text-rose-500">{profileForm.errors.email}</p>
                            )}
                        </div>

                        <div className="pt-2">
                            <button
                                type="submit"
                                disabled={profileForm.processing}
                                className="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-5 text-xs font-bold shadow-none disabled:opacity-50"
                            >
                                {profileForm.processing ? 'Menyimpan...' : 'Simpan Profil'}
                            </button>
                        </div>
                    </form>
                </div>

                {/* Password Update */}
                <div className="rounded-2xl border border-border bg-surface p-6 shadow-sm">
                    <div className="flex items-center gap-3 mb-4 pb-4 border-b border-border">
                        <div className="flex size-10 items-center justify-center rounded-xl bg-amber-500/15 text-amber-500">
                            <Lock className="size-5" />
                        </div>
                        <div>
                            <h2 className="text-base font-bold text-text">Ubah Kata Sandi</h2>
                            <p className="text-xs text-text-muted">Gunakan kata sandi yang kuat dan unik untuk melindungi akun admin.</p>
                        </div>
                    </div>

                    <form onSubmit={submitPassword} className="max-w-xl space-y-4">
                        <div>
                            <label htmlFor="current_password" className="block text-xs font-bold text-text mb-1">
                                Kata Sandi Saat Ini
                            </label>
                            <input
                                id="current_password"
                                type="password"
                                name="current_password"
                                value={passwordForm.data.current_password}
                                onChange={(e) => passwordForm.setData('current_password', e.target.value)}
                                required
                                autoComplete="current-password"
                                className={`min-h-11 w-full rounded-xl border ${
                                    passwordForm.errors.current_password ? 'border-rose-500' : 'border-border'
                                } bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400`}
                            />
                            {passwordForm.errors.current_password && (
                                <p className="mt-1 text-xs text-rose-500">{passwordForm.errors.current_password}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password" className="block text-xs font-bold text-text mb-1">
                                Kata Sandi Baru
                            </label>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                value={passwordForm.data.password}
                                onChange={(e) => passwordForm.setData('password', e.target.value)}
                                required
                                autoComplete="new-password"
                                className={`min-h-11 w-full rounded-xl border ${
                                    passwordForm.errors.password ? 'border-rose-500' : 'border-border'
                                } bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400`}
                            />
                            {passwordForm.errors.password && (
                                <p className="mt-1 text-xs text-rose-500">{passwordForm.errors.password}</p>
                            )}
                        </div>

                        <div>
                            <label htmlFor="password_confirmation" className="block text-xs font-bold text-text mb-1">
                                Ulangi Kata Sandi Baru
                            </label>
                            <input
                                id="password_confirmation"
                                type="password"
                                name="password_confirmation"
                                value={passwordForm.data.password_confirmation}
                                onChange={(e) => passwordForm.setData('password_confirmation', e.target.value)}
                                required
                                autoComplete="new-password"
                                className={`min-h-11 w-full rounded-xl border ${
                                    passwordForm.errors.password_confirmation ? 'border-rose-500' : 'border-border'
                                } bg-surface-2 px-3 text-sm text-text focus:border-amber-400 focus:outline-none focus:ring-1 focus:ring-amber-400`}
                            />
                            {passwordForm.errors.password_confirmation && (
                                <p className="mt-1 text-xs text-rose-500">{passwordForm.errors.password_confirmation}</p>
                            )}
                        </div>

                        <div className="pt-2">
                            <button
                                type="submit"
                                disabled={passwordForm.processing}
                                className="btn-gold-cinema inline-flex min-h-10 items-center justify-center rounded-xl px-5 text-xs font-bold shadow-none disabled:opacity-50"
                            >
                                {passwordForm.processing ? 'Menyimpan...' : 'Perbarui Kata Sandi'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}
