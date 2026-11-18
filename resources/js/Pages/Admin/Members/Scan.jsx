import { useState, useEffect, useRef, useCallback } from 'react';
import { Head, Link } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import jsQR from 'jsqr';
import {
    Camera,
    RotateCw,
    Flashlight,
    Volume2,
    VolumeX,
    QrCode,
    Users,
    Search,
    Check,
    X,
    AlertTriangle,
    Gift,
    Undo2,
    Sparkles,
    ArrowLeft,
    Ticket,
} from 'lucide-react';

export default function MemberScan({
    todaySessions = [],
    selectedSession: initialSelectedSession = null,
    selectedSessionId: initialSelectedSessionId = 'none',
    soundEnabled: initialSoundEnabled = true,
}) {
    const [selectedSessionId, setSelectedSessionId] = useState(
        String(initialSelectedSession?.id || initialSelectedSessionId || (todaySessions[0]?.id ?? 'none'))
    );
    const [soundEnabled, setSoundEnabled] = useState(Boolean(initialSoundEnabled));

    // Audio & Haptics
    const audioCtxRef = useRef(null);
    const playBeep = useCallback((success = true) => {
        if (!soundEnabled) return;
        try {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            if (!AudioCtx) return;
            if (!audioCtxRef.current) {
                audioCtxRef.current = new AudioCtx();
            }
            if (audioCtxRef.current.state === 'suspended') {
                audioCtxRef.current.resume();
            }
            const ctx = audioCtxRef.current;
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = 'sine';

            if (success) {
                osc.frequency.setValueAtTime(880, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(1760, ctx.currentTime + 0.12);
            } else {
                osc.frequency.setValueAtTime(440, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(220, ctx.currentTime + 0.18);
            }

            gain.gain.setValueAtTime(0.15, ctx.currentTime);
            gain.gain.linearRampToValueAtTime(0, ctx.currentTime + (success ? 0.12 : 0.18));
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start();
            osc.stop(ctx.currentTime + (success ? 0.12 : 0.18));
        } catch {
            // Audio context not allowed or failed
        }
    }, [soundEnabled]);

    const vibrate = (pattern = [40]) => {
        if (typeof navigator !== 'undefined' && navigator.vibrate) {
            try {
                navigator.vibrate(pattern);
            } catch {
                // Ignore
            }
        }
    };

    // Camera state
    const videoRef = useRef(null);
    const canvasRef = useRef(null);
    const [cameraStatus, setCameraStatus] = useState('idle'); // 'idle' | 'active' | 'denied' | 'not_found' | 'error'
    const [cameraFacingMode, setCameraFacingMode] = useState('environment');
    const [hasTorch, setHasTorch] = useState(false);
    const [isTorchOn, setIsTorchOn] = useState(false);
    const mediaStreamRef = useRef(null);
    const scanIntervalRef = useRef(null);
    const lastScannedCodeRef = useRef('');
    const lastScannedTimeRef = useRef(0);

    // Manual input & lookup
    const [manualInput, setManualInput] = useState('');
    const [isLookingUp, setIsLookingUp] = useState(false);
    const [clientAlert, setClientAlert] = useState('');

    // Member data
    const [activeMember, setActiveMember] = useState(null);
    const [activeSessionBookings, setActiveSessionBookings] = useState([]);
    const [selectedBookingId, setSelectedBookingId] = useState('');
    const [isRecording, setIsRecording] = useState(false);
    const [recordedReward, setRecordedReward] = useState(null);

    // Override & Undo modals
    const [openUndoModal, setOpenUndoModal] = useState(false);
    const [undoReason, setUndoReason] = useState('');
    const [isUndoing, setIsUndoing] = useState(false);
    const [overrideReason, setOverrideReason] = useState('');

    const showAlert = (msg) => {
        setClientAlert(msg);
        setTimeout(() => setClientAlert(''), 3500);
    };

    const formatTime = (timeStr) => {
        if (!timeStr) return '-';
        return timeStr.slice(0, 5);
    };

    // Lookup Code
    const lookupCode = useCallback(async (codeStr) => {
        if (!codeStr || !codeStr.trim()) return;
        setIsLookingUp(true);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/admin/scan/cari', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    code: codeStr.trim(),
                    session_id: selectedSessionId !== 'none' ? selectedSessionId : null,
                }),
            });

            const data = await res.json();
            setIsLookingUp(false);

            if (res.status === 429) {
                showAlert(data.message || 'Terlalu banyak permintaan gagal. Tunggu sebentar.');
                playBeep(false);
                return;
            }

            if (data.type === 'member' && data.status === 'found') {
                setActiveMember({
                    ...data.member,
                    verdict: data.verdict,
                    can_record: data.can_record,
                    requires_override: data.requires_override,
                    message: data.message,
                    current_cycle_visit_count: data.member.current_in_cycle,
                    progress_description: data.member.progress_text,
                });
                setActiveSessionBookings(data.session_bookings || []);
                setSelectedBookingId('');
                setRecordedReward(null);
                setOverrideReason('');
                vibrate([40]);
                if (data.can_record) {
                    playBeep(true);
                } else {
                    playBeep(false);
                }
            } else {
                showAlert(data.message || 'Data member tidak ditemukan untuk kode ini.');
                playBeep(false);
            }
        } catch {
            setIsLookingUp(false);
            showAlert('Gagal menghubungi server. Periksa koneksi internet.');
            playBeep(false);
        }
    }, [selectedSessionId, playBeep]);

    // Handle Scanned Raw Text
    const handleScannedRawText = useCallback((rawText) => {
        if (!rawText) return;
        const trimmed = rawText.trim();

        let parsedCode = '';
        if (trimmed.includes('/member/')) {
            const parts = trimmed.split('/member/');
            parsedCode = (parts[1] || '').split(/[?#]/)[0];
        } else if (trimmed.startsWith('MBR-') || trimmed.startsWith('SYNEMAX-MBR:')) {
            parsedCode = trimmed;
        } else if (/^[A-Z0-9\-]{4,24}$/i.test(trimmed)) {
            parsedCode = trimmed;
        }

        if (!parsedCode) {
            showAlert('QR Code ini bukan kartu member SynemaX yang valid.');
            return;
        }

        const now = Date.now();
        if (parsedCode === lastScannedCodeRef.current && (now - lastScannedTimeRef.current) < 3000) {
            return;
        }

        lastScannedCodeRef.current = parsedCode;
        lastScannedTimeRef.current = now;

        lookupCode(parsedCode);
    }, [lookupCode]);

    // Capture & Scan Frame
    const captureAndScanFrame = useCallback(async () => {
        if (!videoRef.current || !canvasRef.current) return;
        const video = videoRef.current;
        if (video.readyState < 2 || video.videoWidth === 0) return;

        const canvas = canvasRef.current;
        const targetWidth = 360;
        const targetHeight = Math.round((video.videoHeight / video.videoWidth) * targetWidth);
        canvas.width = targetWidth;
        canvas.height = targetHeight;

        const ctx = canvas.getContext('2d', { willReadFrequently: true });
        if (!ctx) return;
        ctx.drawImage(video, 0, 0, targetWidth, targetHeight);

        // 1. BarcodeDetector native
        if ('BarcodeDetector' in window) {
            try {
                const barcodeDetector = new window.BarcodeDetector({ formats: ['qr_code'] });
                const barcodes = await barcodeDetector.detect(canvas);
                if (barcodes && barcodes.length > 0 && barcodes[0].rawValue) {
                    handleScannedRawText(barcodes[0].rawValue);
                    return;
                }
            } catch {
                // Fallback
            }
        }

        // 2. jsQR Fallback
        const resolveJsQR = typeof jsQR === 'function' ? jsQR : (jsQR?.default || window.jsQR);
        if (typeof resolveJsQR === 'function') {
            try {
                const imgData = ctx.getImageData(0, 0, targetWidth, targetHeight);
                const code = resolveJsQR(imgData.data, imgData.width, imgData.height, {
                    inversionAttempts: 'dontInvert',
                });
                if (code && code.data) {
                    handleScannedRawText(code.data);
                }
            } catch {
                // Ignore
            }
        }
    }, [handleScannedRawText]);

    // Stop Camera
    const stopCamera = useCallback(() => {
        if (scanIntervalRef.current) {
            clearInterval(scanIntervalRef.current);
            scanIntervalRef.current = null;
        }
        if (mediaStreamRef.current) {
            mediaStreamRef.current.getTracks().forEach((track) => track.stop());
            mediaStreamRef.current = null;
        }
        if (videoRef.current) {
            videoRef.current.srcObject = null;
        }
    }, []);

    // Start Camera
    const startCamera = useCallback(async () => {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            setCameraStatus('not_found');
            return;
        }

        stopCamera();

        const constraints = {
            audio: false,
            video: {
                facingMode: { ideal: cameraFacingMode },
                width: { ideal: 1280 },
                height: { ideal: 720 },
            },
        };

        try {
            let stream = null;
            try {
                stream = await navigator.mediaDevices.getUserMedia(constraints);
            } catch {
                stream = await navigator.mediaDevices.getUserMedia({ audio: false, video: true });
            }

            mediaStreamRef.current = stream;
            setCameraStatus('active');

            if (videoRef.current) {
                videoRef.current.srcObject = stream;
                await videoRef.current.play();
            }

            const track = stream.getVideoTracks()[0];
            if (track && track.getCapabilities) {
                const caps = track.getCapabilities();
                setHasTorch(Boolean(caps.torch));
            }

            scanIntervalRef.current = setInterval(captureAndScanFrame, 110);
        } catch (err) {
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                setCameraStatus('denied');
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                setCameraStatus('not_found');
            } else {
                setCameraStatus('error');
            }
        }
    }, [cameraFacingMode, stopCamera, captureAndScanFrame]);

    // Flip Camera Facing
    const switchCamera = () => {
        setCameraFacingMode((prev) => (prev === 'environment' ? 'user' : 'environment'));
    };

    // Toggle Torch
    const toggleTorch = async () => {
        if (!mediaStreamRef.current || !hasTorch) return;
        const track = mediaStreamRef.current.getVideoTracks()[0];
        if (track && track.applyConstraints) {
            try {
                const nextState = !isTorchOn;
                await track.applyConstraints({ advanced: [{ torch: nextState }] });
                setIsTorchOn(nextState);
            } catch {
                setIsTorchOn(false);
            }
        }
    };

    // Record Member Visit
    const recordVisit = async () => {
        if (!activeMember || selectedSessionId === 'none') {
            showAlert('Pilih sesi penayangan yang valid terlebih dahulu.');
            return;
        }

        setIsRecording(true);
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/admin/scan/member-hadir', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    member_id: activeMember.id,
                    session_id: selectedSessionId,
                    booking_id: selectedBookingId || null,
                    method: 'qr',
                    allow_override: activeMember.requires_override,
                    override_reason: activeMember.requires_override ? overrideReason : null,
                }),
            });

            const data = await res.json();
            setIsRecording(false);

            if (data.success) {
                playBeep(true);
                vibrate([40]);
                setActiveMember((prev) => ({
                    ...prev,
                    can_record: false,
                    verdict: 'sudah_tercatat_sesi_ini',
                    message: data.message,
                    last_valid_visit_id: data.visit?.id,
                    current_cycle_visit_count: Math.min(10, (prev.current_cycle_visit_count || 0) + 1),
                }));
                setRecordedReward(data.reward);
            } else {
                playBeep(false);
                showAlert(data.message || 'Gagal mencatat kunjungan member.');
            }
        } catch {
            setIsRecording(false);
            showAlert('Gagal mencatat kunjungan. Coba lagi.');
            playBeep(false);
        }
    };

    // Submit Undo Visit
    const submitUndo = async () => {
        if (!activeMember || !activeMember.last_valid_visit_id) return;
        setIsUndoing(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/admin/scan/member-batal-hadir', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    member_id: activeMember.id,
                    visit_id: activeMember.last_valid_visit_id,
                    reason: undoReason || 'Dibatalkan petugas',
                }),
            });

            const data = await res.json();
            setIsUndoing(false);
            setOpenUndoModal(false);
            setUndoReason('');

            if (data.success) {
                playBeep(true);
                setActiveMember((prev) => ({
                    ...prev,
                    can_record: true,
                    verdict: 'siap_dicatat',
                    message: data.message,
                    last_valid_visit_id: null,
                    current_cycle_visit_count: Math.max(0, (prev.current_cycle_visit_count || 1) - 1),
                }));
                setRecordedReward(null);
            } else {
                showAlert(data.message || 'Gagal membatalkan kunjungan.');
                playBeep(false);
            }
        } catch {
            setIsUndoing(false);
            showAlert('Gagal membatalkan kunjungan. Coba lagi.');
            playBeep(false);
        }
    };

    // Settle Member Reward
    const settleReward = async (action, note = '') => {
        if (!recordedReward) return;

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/admin/scan/member-reward-settle', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    reward_id: recordedReward.id,
                    action,
                    note,
                }),
            });

            const data = await res.json();
            if (data.success) {
                setRecordedReward((prev) => ({
                    ...prev,
                    status: action,
                }));
                playBeep(true);
            }
        } catch {
            showAlert('Gagal memperbarui status reward.');
        }
    };

    // Mount camera
    useEffect(() => {
        startCamera();
        return () => {
            stopCamera();
        };
    }, [startCamera, stopCamera]);

    return (
        <AdminLayout title="Scan Member" isFormPage={true}>
            <Head title="Scan Member · Stempel Loyalitas" />

            <div className="space-y-4 max-w-6xl mx-auto pb-12">
                {/* Header Toolbar */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-surface border border-border rounded-2xl p-4 shadow-xs">
                    <div className="flex items-center gap-3">
                        <Link
                            href="/admin/members"
                            className="inline-flex size-10 items-center justify-center rounded-xl border border-border bg-surface text-text hover:bg-surface-2 transition shadow-xs"
                            title="Kembali ke Daftar Member"
                        >
                            <ArrowLeft className="size-4.5" />
                        </Link>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-base font-black text-text tracking-tight">Scan Member</h1>
                                <span className="inline-flex items-center gap-1 rounded-md bg-amber-500/15 px-2 py-0.5 text-[10px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/30">
                                    <Sparkles className="size-3 text-amber-500 animate-pulse" />
                                    <span>Stempel Loyalitas</span>
                                </span>
                            </div>
                            <p className="text-xs text-text-muted mt-0.5">Pindai kartu QR member untuk mencatat kehadiran dan membagikan reward.</p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        {/* Switch to Ticket Scanner */}
                        <Link
                            href="/admin/scan"
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-border bg-surface-2 px-3 text-xs font-semibold text-text-muted hover:text-text hover:bg-surface transition shadow-xs"
                        >
                            <Ticket className="size-3.5 text-text-muted" />
                            <span>Beralih ke Scan Tiket</span>
                        </Link>

                        {/* Sound Toggle */}
                        <button
                            type="button"
                            onClick={() => setSoundEnabled(!soundEnabled)}
                            className={`inline-flex size-10 items-center justify-center rounded-xl border transition shadow-xs ${
                                soundEnabled
                                    ? 'border-amber-500/60 text-amber-600 dark:text-amber-400 bg-amber-500/10'
                                    : 'border-border text-text-muted hover:text-text bg-surface'
                            }`}
                            title={soundEnabled ? 'Suara aktif' : 'Suara bisu'}
                        >
                            {soundEnabled ? <Volume2 className="size-4.5" /> : <VolumeX className="size-4.5" />}
                        </button>
                    </div>
                </div>

                {/* Main 2-Column Responsive Layout */}
                <div className="grid grid-cols-1 lg:grid-cols-2 gap-5 items-start">
                    {/* LEFT COLUMN: CAMERA SCANNER & MANUAL SEARCH */}
                    <div className="rounded-2xl border border-border bg-surface p-4 sm:p-5 shadow-xs space-y-4">
                        {/* Session Selector */}
                        <div>
                            <label htmlFor="member_session_select" className="block text-xs font-bold text-text-muted mb-1.5">
                                Sesi Penayangan yang Dihadiri
                            </label>
                            <select
                                id="member_session_select"
                                value={selectedSessionId}
                                onChange={(e) => setSelectedSessionId(e.target.value)}
                                className="w-full min-h-[42px] rounded-xl border border-border bg-surface-2 px-3 text-xs font-bold text-text focus:border-amber-500 focus:outline-none"
                            >
                                {todaySessions.length === 0 ? (
                                    <option value="none">Tidak ada sesi penayangan hari ini</option>
                                ) : (
                                    todaySessions.map((session) => (
                                        <option key={session.id} value={String(session.id)}>
                                            {formatTime(session.start_time)} · {session.film?.title || 'Sesi Bebas'} ({session.is_private ? 'Privat' : 'Publik'})
                                        </option>
                                    ))
                                )}
                            </select>
                        </div>

                        {/* Viewport Kamera */}
                        <div className="relative w-full aspect-square max-w-md mx-auto rounded-2xl bg-black overflow-hidden border border-border shadow-md flex items-center justify-center">
                            <video
                                ref={videoRef}
                                playsInline
                                muted
                                autoPlay
                                className="absolute inset-0 size-full object-cover"
                            />
                            <canvas ref={canvasRef} className="hidden" />

                            {/* Overlay Reticle */}
                            <div className="relative z-10 size-64 sm:size-72 border-2 border-amber-500/60 rounded-2xl pointer-events-none flex flex-col justify-between p-2">
                                <div className="flex justify-between">
                                    <div className="size-6 border-t-4 border-l-4 border-amber-500 rounded-tl-lg" />
                                    <div className="size-6 border-t-4 border-r-4 border-amber-500 rounded-tr-lg" />
                                </div>
                                <div className="flex justify-between">
                                    <div className="size-6 border-b-4 border-l-4 border-amber-500 rounded-bl-lg" />
                                    <div className="size-6 border-b-4 border-r-4 border-amber-500 rounded-br-lg" />
                                </div>
                                <div className="scan-laser-beam absolute inset-x-2 h-0.5 bg-gradient-to-r from-transparent via-amber-400 to-transparent shadow-[0_0_8px_rgba(245,158,11,0.8)]" />
                            </div>

                            {/* Kamera Toolbar Bawah */}
                            <div className="absolute bottom-3 inset-x-3 z-10 flex items-center justify-between pointer-events-auto">
                                <button
                                    type="button"
                                    onClick={switchCamera}
                                    className="min-h-[38px] inline-flex items-center gap-1.5 rounded-xl bg-black/60 backdrop-blur-md px-3 py-1.5 text-[11px] font-bold text-white border border-white/20 hover:bg-black/80 transition"
                                >
                                    <RotateCw className="size-3.5" />
                                    <span>Putar Kamera</span>
                                </button>

                                {hasTorch && (
                                    <button
                                        type="button"
                                        onClick={toggleTorch}
                                        className="min-h-[38px] inline-flex items-center gap-1.5 rounded-xl bg-black/60 backdrop-blur-md px-3 py-1.5 text-[11px] font-bold text-white border border-white/20 hover:bg-black/80 transition"
                                    >
                                        <Flashlight className="size-3.5" />
                                        <span>{isTorchOn ? 'Matikan Senter' : 'Nyalakan Senter'}</span>
                                    </button>
                                )}
                            </div>

                            {/* Client alert overlay */}
                            {clientAlert && (
                                <div className="absolute top-4 inset-x-4 z-20 p-3 rounded-xl border border-rose-500/50 bg-black/90 text-center text-xs font-semibold text-rose-300 backdrop-blur shadow-xl animate-in fade-in">
                                    {clientAlert}
                                </div>
                            )}
                        </div>

                        {/* Input Manual Kode Member */}
                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                lookupCode(manualInput);
                            }}
                            className="flex gap-2 max-w-md mx-auto"
                        >
                            <div className="relative flex-1">
                                <input
                                    type="text"
                                    value={manualInput}
                                    onChange={(e) => setManualInput(e.target.value)}
                                    placeholder="Ketik kode MBR-XXXX-XXXX-C..."
                                    className="w-full min-h-[44px] rounded-xl border border-border bg-surface-2 px-3.5 text-xs text-text uppercase placeholder:normal-case placeholder:text-text-muted focus:border-amber-500 focus:outline-none transition"
                                />
                            </div>
                            <button
                                type="submit"
                                disabled={!manualInput.trim() || isLookingUp}
                                className="min-h-[44px] inline-flex items-center justify-center rounded-xl bg-amber-500 px-5 text-xs font-bold text-slate-950 hover:bg-amber-400 disabled:opacity-40 transition shadow-xs shrink-0"
                            >
                                {isLookingUp ? 'Mencari...' : 'Cari'}
                            </button>
                        </form>
                        <p className="text-[11px] text-text-muted text-center">
                            Arahkan kamera ke QR kartu fisik / digital member, atau masukkan kode 9 karakter.
                        </p>
                    </div>

                    {/* RIGHT COLUMN: SCAN RESULTS & REWARD PANEL */}
                    <div className="rounded-2xl border border-border bg-surface-2/40 p-4 sm:p-6 min-h-[450px] flex flex-col justify-center">
                        {/* Empty state */}
                        {!activeMember && (
                            <div className="text-center py-12 px-4 max-w-md mx-auto space-y-3">
                                <div className="size-16 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-500 flex items-center justify-center mx-auto shadow-inner">
                                    <Users className="size-8" />
                                </div>
                                <h2 className="text-base font-bold text-text">Belum Ada Member yang Dipindai</h2>
                                <p className="text-xs text-text-muted leading-relaxed">
                                    Pindai kartu QR member atau ketikkan kodenya di sebelah kiri untuk melihat status stempel, siklus kunjungan, dan klaim reward.
                                </p>
                            </div>
                        )}

                        {/* Active Member Profile Card */}
                        {activeMember && (
                            <div className="space-y-4 max-w-xl mx-auto w-full animate-in fade-in">
                                <div className="rounded-2xl border border-border bg-surface p-4 sm:p-5 shadow-sm space-y-4">
                                    {/* Header Member */}
                                    <div className="flex items-start justify-between gap-3">
                                        <div className="flex items-center gap-3">
                                            {activeMember.photo_url ? (
                                                <img
                                                    src={activeMember.photo_url}
                                                    className="size-14 rounded-xl object-cover border border-border shadow-xs"
                                                    alt="Foto Member"
                                                />
                                            ) : (
                                                <div className="size-14 rounded-xl bg-amber-500/20 border border-amber-500/30 text-amber-600 dark:text-amber-400 font-black text-xl flex items-center justify-center">
                                                    {activeMember.name?.substring(0, 2).toUpperCase() || 'MB'}
                                                </div>
                                            )}
                                            <div>
                                                <h2 className="text-base font-black text-text">{activeMember.name}</h2>
                                                <p className="text-xs font-mono font-bold text-amber-600 dark:text-amber-400">{activeMember.code}</p>
                                                {activeMember.nickname && (
                                                    <p className="text-[11px] text-text-muted">Panggilan: {activeMember.nickname}</p>
                                                )}
                                            </div>
                                        </div>

                                        <span
                                            className={`inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-bold border ${
                                                activeMember.status === 'aktif'
                                                    ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border-emerald-500/30'
                                                    : activeMember.status === 'nonaktif'
                                                    ? 'bg-slate-500/15 text-text-muted border-border'
                                                    : 'bg-rose-500/15 text-rose-600 border-rose-500/30'
                                            }`}
                                        >
                                            {activeMember.status ? activeMember.status.charAt(0).toUpperCase() + activeMember.status.slice(1) : ''}
                                        </span>
                                    </div>

                                    {/* 10-Stamp Grid */}
                                    <div className="rounded-xl border border-border bg-surface-2 p-3 sm:p-4 space-y-3">
                                        <div className="flex items-center justify-between text-xs font-bold">
                                            <span className="text-text">Siklus Kunjungan Ke-{activeMember.cycle_number || 1}</span>
                                            <span className="text-amber-600 dark:text-amber-400 font-mono">
                                                {activeMember.current_cycle_visit_count || 0} / 10
                                            </span>
                                        </div>

                                        <div className="grid grid-cols-5 sm:grid-cols-10 gap-2">
                                            {Array.from({ length: 10 }, (_, idx) => {
                                                const i = idx + 1;
                                                const isCompleted = i <= (activeMember.current_cycle_visit_count || 0);
                                                const isNext = i === activeMember.next_position;
                                                return (
                                                    <div
                                                        key={i}
                                                        className={`relative aspect-square rounded-xl flex flex-col items-center justify-center text-xs font-black border transition ${
                                                            isCompleted
                                                                ? 'bg-emerald-500 text-slate-950 border-emerald-400 shadow-xs'
                                                                : isNext
                                                                ? 'bg-amber-500/20 text-amber-500 border-amber-500/40 ring-2 ring-amber-500/40 animate-pulse'
                                                                : 'bg-surface border-border text-text-muted'
                                                        }`}
                                                    >
                                                        <span>{i}</span>
                                                        {i === 5 && (
                                                            <span className="absolute -top-1.5 -right-1 text-[8px] bg-amber-500 text-black px-1 rounded-full font-black">
                                                                2k
                                                            </span>
                                                        )}
                                                        {i === 10 && (
                                                            <span className="absolute -top-1.5 -right-1 text-[8px] bg-emerald-500 text-black px-1 rounded-full font-black">
                                                                Gratis
                                                            </span>
                                                        )}
                                                    </div>
                                                );
                                            })}
                                        </div>

                                        <p className="text-[11px] text-text-muted leading-relaxed">
                                            {activeMember.progress_description}
                                        </p>
                                    </div>

                                    {/* Reward alert banner */}
                                    {activeMember.potential_reward && activeMember.potential_reward !== 'none' && (
                                        <div className="rounded-xl border border-amber-500/40 bg-amber-500/10 p-3.5 space-y-2">
                                            <div className="flex items-center gap-2">
                                                <Gift className="size-5 text-amber-500 shrink-0" />
                                                <h4 className="text-xs font-black text-amber-600 dark:text-amber-400">
                                                    {activeMember.potential_reward === 'discount'
                                                        ? 'Kunjungan Ke-5: Berhak Potongan Rp 2.000!'
                                                        : 'Kunjungan Ke-10: Berhak 1 Tiket Nonton Gratis!'}
                                                </h4>
                                            </div>
                                            <p className="text-[11px] text-text-muted">
                                                Reward langsung diselesaikan di loket/pintu saat kunjungan ini dicatat.
                                            </p>
                                        </div>
                                    )}

                                    {/* Session booking selection */}
                                    {activeSessionBookings.length > 0 && (
                                        <div className="rounded-xl border border-border bg-surface-2 p-3 space-y-1.5">
                                            <label htmlFor="member_booking_select" className="block text-xs font-semibold text-text-muted">
                                                Booking Terkait Sesi Ini (Opsional):
                                            </label>
                                            <select
                                                id="member_booking_select"
                                                value={selectedBookingId}
                                                onChange={(e) => setSelectedBookingId(e.target.value)}
                                                className="w-full min-h-[38px] rounded-xl border border-border bg-surface px-3 text-xs text-text"
                                            >
                                                <option value="">Tanpa Booking (Datang Langsung / Diskon Kasir)</option>
                                                {activeSessionBookings.map((b) => (
                                                    <option key={b.id} value={b.id}>
                                                        {b.code} - {b.customer_name} ({b.pax} pax)
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                    )}

                                    {/* Reward Banner Settlement */}
                                    {recordedReward && (
                                        <div className="rounded-xl border border-emerald-500/40 bg-emerald-500/10 p-3.5 space-y-2">
                                            <div className="flex items-center justify-between">
                                                <span className="text-xs font-black text-emerald-600 dark:text-emerald-400">
                                                    Reward Tercatat: {recordedReward.type === 'free' ? 'Tiket Gratis' : `Diskon Rp ${Number(recordedReward.amount).toLocaleString('id-ID')}`}
                                                </span>
                                                <span className="text-[10px] font-bold uppercase rounded bg-emerald-500/20 px-2 py-0.5 text-emerald-700 dark:text-emerald-300">
                                                    {recordedReward.status}
                                                </span>
                                            </div>
                                            {recordedReward.status === 'pending' && (
                                                <div className="flex gap-2 pt-1">
                                                    <button
                                                        type="button"
                                                        onClick={() => settleReward('given')}
                                                        className="flex-1 min-h-[36px] rounded-lg bg-emerald-600 text-xs font-bold text-white hover:bg-emerald-500 transition"
                                                    >
                                                        Sudah Diberikan
                                                    </button>
                                                    <button
                                                        type="button"
                                                        onClick={() => settleReward('skipped', 'Dilewati petugas')}
                                                        className="min-h-[36px] rounded-lg border border-border px-3 text-xs text-text-muted hover:text-text transition"
                                                    >
                                                        Lewati
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {/* Action Buttons */}
                                    <div className="flex flex-col sm:flex-row gap-2 pt-2">
                                        <button
                                            type="button"
                                            onClick={recordVisit}
                                            disabled={!activeMember.can_record || isRecording}
                                            className="flex-1 min-h-[46px] inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 text-sm font-black text-slate-950 hover:bg-amber-400 disabled:opacity-40 transition shadow-sm"
                                        >
                                            <Check className="size-4.5" />
                                            <span>{isRecording ? 'Menyimpan...' : 'Catat Kunjungan (+1 Stempel)'}</span>
                                        </button>

                                        <button
                                            type="button"
                                            onClick={() => {
                                                setActiveMember(null);
                                                setRecordedReward(null);
                                                setManualInput('');
                                            }}
                                            className="min-h-[46px] inline-flex items-center justify-center rounded-xl border border-border bg-surface px-4 text-xs font-bold text-text hover:bg-surface-2 transition"
                                        >
                                            Tutup / Pindai Baru
                                        </button>
                                    </div>

                                    {/* Undo Button */}
                                    {activeMember.verdict === 'sudah_tercatat_sesi_ini' && activeMember.last_valid_visit_id && (
                                        <div className="pt-2">
                                            <button
                                                type="button"
                                                onClick={() => setOpenUndoModal(true)}
                                                className="w-full min-h-[38px] inline-flex items-center justify-center gap-1.5 rounded-xl border border-rose-500/40 bg-rose-500/10 text-xs font-bold text-rose-600 hover:bg-rose-500 hover:text-white transition"
                                            >
                                                <Undo2 className="size-3.5" />
                                                <span>Batalkan Catatan Kunjungan Ini (Undo)</span>
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* MODAL UNDO KUNJUNGAN MEMBER */}
            {openUndoModal && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
                    <div className="w-full max-w-md rounded-2xl border border-border bg-surface p-5 space-y-4 shadow-2xl">
                        <h3 className="text-base font-black text-rose-600">Batalkan Kunjungan Member</h3>
                        <p className="text-xs text-text-muted leading-relaxed">
                            Hanya kunjungan sah terakhir yang dapat dibatalkan. Stempel kunjungan akan dikurangi 1 dan reward terkait akan disesuaikan.
                        </p>
                        <div>
                            <label htmlFor="undo_reason_input" className="block text-xs font-semibold text-text mb-1.5">
                                Alasan Pembatalan (Wajib):
                            </label>
                            <input
                                type="text"
                                id="undo_reason_input"
                                value={undoReason}
                                onChange={(e) => setUndoReason(e.target.value)}
                                placeholder="Contoh: Salah scan kartu, member batal nonton"
                                className="w-full min-h-[42px] rounded-xl border border-border bg-surface-2 px-3 text-xs text-text focus:border-rose-500 focus:outline-none transition"
                            />
                        </div>
                        <div className="flex justify-end gap-2 pt-2">
                            <button
                                type="button"
                                onClick={() => {
                                    setOpenUndoModal(false);
                                    setUndoReason('');
                                }}
                                className="min-h-[40px] rounded-xl border border-border px-4 text-xs font-semibold text-text hover:bg-surface-2"
                            >
                                Kembali
                            </button>
                            <button
                                type="button"
                                onClick={submitUndo}
                                disabled={!undoReason.trim() || isUndoing}
                                className="min-h-[40px] rounded-xl bg-rose-600 px-4 text-xs font-bold text-white hover:bg-rose-500 disabled:opacity-40"
                            >
                                {isUndoing ? 'Membatalkan...' : 'Ya, Batalkan Kunjungan'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
