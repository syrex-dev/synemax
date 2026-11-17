import { Link, Head } from '@inertiajs/react';
import AdminLayout from '../../../Layouts/AdminLayout';
import Pagination from '../../../Components/Pagination';
import {
    CheckSquare,
    Calendar,
    Clock,
    Film,
    ChevronRight,
} from 'lucide-react';

export default function PreparationIndex({
    sessions,
}) {
    const sessionList = sessions?.data || [];

    return (
        <AdminLayout title="Daftar Siapkan Jajanan">
            <Head title="Daftar Siapkan Jajanan | Admin SynemaX" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-1">
                    <div className="text-xs font-bold uppercase tracking-wider text-amber-500">
                        Dapur & Operasional Studio
                    </div>
                    <h1 className="text-2xl font-black tracking-tight text-text flex items-center gap-2.5">
                        <CheckSquare className="size-6 text-amber-500" />
                        <span>Daftar Siapkan Jajanan</span>
                    </h1>
                    <p className="text-xs text-text-muted">
                        Pilih sesi untuk melihat jumlah jajanan yang perlu disiapkan dan catatan tiap pemesan.
                    </p>
                </div>

                {/* Session List */}
                <div className="space-y-3">
                    {sessionList.length > 0 ? (
                        sessionList.map((session) => (
                            <Link
                                key={session.id}
                                href={`/admin/jajanan/sesi/${session.id}`}
                                className="flex min-h-[80px] items-center justify-between gap-4 rounded-2xl border border-border bg-surface p-4 sm:p-5 hover:border-amber-500 transition-colors shadow-xs"
                            >
                                <div className="space-y-1">
                                    <div className="flex items-center gap-2 text-sm font-bold text-text">
                                        <Calendar className="size-4 text-amber-500" />
                                        <span>{session.session_date}</span>
                                        <span>•</span>
                                        <Clock className="size-4 text-amber-500" />
                                        <span>{session.start_time?.slice(0, 5)} WIB</span>
                                    </div>
                                    <p className="text-xs text-text-muted flex items-center gap-1.5">
                                        <Film className="size-3.5 text-text-muted" />
                                        <span className="font-semibold text-text">
                                            {session.film?.title || 'Film belum dipilih'}
                                        </span>
                                        <span>—</span>
                                        <span className="font-bold text-amber-600 dark:text-amber-400">
                                            {session.snack_booking_count} booking jajan
                                        </span>
                                    </p>
                                </div>

                                <div className="flex items-center gap-1 font-bold text-xs text-amber-500 shrink-0">
                                    <span>Buka Rincian</span>
                                    <ChevronRight className="size-4" />
                                </div>
                            </Link>
                        ))
                    ) : (
                        <div className="rounded-2xl border border-border bg-surface p-12 text-center text-xs text-text-muted">
                            Belum ada pesanan jajan untuk sesi mendatang.
                        </div>
                    )}
                </div>

                {/* Pagination */}
                {sessions?.links && (
                    <div className="pt-2">
                        <Pagination links={sessions.links} />
                    </div>
                )}
            </div>
        </AdminLayout>
    );
}

