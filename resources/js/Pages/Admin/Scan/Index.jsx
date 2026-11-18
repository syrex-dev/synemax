import { useState, useEffect, useRef, useCallback } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import jsQR from 'jsqr';
import {
    Camera,
    Hash,
    ListFilter,
    Volume2,
    VolumeX,
    RotateCw,
    Flashlight,
    Pause,
    Play,
    Search,
    Clipboard,
    Check,
    X,
    AlertTriangle,
    QrCode,
    Users,
    Clock,
    Phone,
    Ticket,
    CheckCircle2,
    XCircle,
    ArrowRight,
    RefreshCw,
    ExternalLink,
} from 'lucide-react';

export default function TicketScanner({
    todaySessions = [],
    selectedSession: initialSelectedSession = null,
    selectedSessionId: initialSelectedSessionId = 'all',
    soundEnabled: initialSoundEnabled = true,
}) {
    // Tab & Session state
    const [activeTab, setActiveTab] = useState('kamera'); // 'kamera' | 'kode' | 'daftar'
    const [selectedSessionId, setSelectedSessionId] = useState(String(initialSelectedSessionId || 'all'));
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

    // Camera State
    const videoRef = useRef(null);
    const canvasRef = useRef(null);
    const [cameraStatus, setCameraStatus] = useState('idle'); // 'idle' | 'active' | 'denied' | 'not_found' | 'unsupported_protocol' | 'error'
    const [cameraErrorMessage, setCameraErrorMessage] = useState('');
    const [videoDevices, setVideoDevices] = useState([]);
    const [selectedCameraId, setSelectedCameraId] = useState('');
    const [cameraFacingMode, setCameraFacingMode] = useState('environment');
    const [hasTorch, setHasTorch] = useState(false);
    const [isTorchOn, setIsTorchOn] = useState(false);
    const [isCameraPaused, setIsCameraPaused] = useState(false);
    const [clientAlertMessage, setClientAlertMessage] = useState('');
    const mediaStreamRef = useRef(null);
    const scanIntervalRef = useRef(null);
    const lastScannedCodeRef = useRef('');
    const lastScannedTimeRef = useRef(0);

    // Manual Code & Live Search State
    const [manualCodeInput, setManualCodeInput] = useState('');
    const [liveSearchInput, setLiveSearchInput] = useState('');
    const [liveSearchResults, setLiveSearchResults] = useState([]);
    const [ambiguousMatches, setAmbiguousMatches] = useState([]);
    const [isSearching, setIsSearching] = useState(false);

    // Session List State (Tab "Daftar")
    const [sessionListItems, setSessionListItems] = useState([]);
    const [daftarFilter, setDaftarFilter] = useState('all'); // 'all' | 'belum' | 'hadir'
    const [daftarSearch, setDaftarSearch] = useState('');
    const [isLoadingDaftar, setIsLoadingDaftar] = useState(false);
    const [sessionSummary, setSessionSummary] = useState({ hadir: 0, belum: 0, text: 'Memuat...' });

    // Sheet Preview State
    const [sheetOpen, setSheetOpen] = useState(false);
    const [previewType, setPreviewType] = useState('booking'); // 'booking' | 'member'
    const [currentPreview, setCurrentPreview] = useState(null);
    const [memberData, setMemberData] = useState(null);
    const [memberRecordedReward, setMemberRecordedReward] = useState(null);
    const [selectedMemberBookingId, setSelectedMemberBookingId] = useState('');
    const [showFullPhone, setShowFullPhone] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Modals
    const [overrideModalOpen, setOverrideModalOpen] = useState(false);
    const [overrideReason, setOverrideReason] = useState('');
    const [memberOverrideReason, setMemberOverrideReason] = useState('');
    const [memberVoidModalOpen, setMemberVoidModalOpen] = useState(false);
    const [memberVoidReason, setMemberVoidReason] = useState('');

    const autoCloseTimerRef = useRef(null);
    const liveSearchTimerRef = useRef(null);

    // Show temporary client alert
    const showClientAlert = (msg) => {
        setClientAlertMessage(msg);
        setTimeout(() => setClientAlertMessage(''), 3500);
    };

    // Helper: format time
    const formatTime = (timeStr) => {
        if (!timeStr) return '-';
        return timeStr.slice(0, 5);
    };

    // Load Session Bookings
    const loadSessionBookings = useCallback(async () => {
        setIsLoadingDaftar(true);
        try {
            const url = new URL('/admin/scan/sesi-bookings', window.location.origin);
            url.searchParams.set('session_id', selectedSessionId);
            const res = await fetch(url.toString(), {
                headers: { Accept: 'application/json' },
            });
            if (res.ok) {
                const data = await res.json();
                setSessionListItems(data.items || data.bookings || []);
                if (data.summary) {
                    setSessionSummary(data.summary);
                }
            }
        } catch {
            // Network issue
        } finally {
            setIsLoadingDaftar(false);
        }
    }, [selectedSessionId]);

    // Lookup Code (Booking or Member)
    const performLookup = useCallback(async (codeStr, method = 'qr') => {
        setIsSearching(true);
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
                    code: codeStr,
                    session_id: selectedSessionId,
                }),
            });

            const data = await res.json();
            setIsSearching(false);

            if (res.status === 429) {
                showClientAlert(data.message || 'Terlalu banyak permintaan. Mohon tunggu sejenak.');
                playBeep(false);
                return;
            }

            if (data.type === 'member') {
                setPreviewType('member');
                setMemberData(data);
                setMemberRecordedReward(null);
                setSelectedMemberBookingId('');
                setSheetOpen(true);
                vibrate([40]);
                if (data.can_record) {
                    playBeep(true);
                } else {
                    playBeep(false);
                }
                return;
            }

            if (data.status === 'ambiguous') {
                setAmbiguousMatches(data.matches || []);
                setActiveTab('kode');
                return;
            }

            if (data.status === 'not_found') {
                setPreviewType('booking');
                setCurrentPreview({
                    verdict: 'tidak_ditemukan',
                    verdict_badge: { label: 'Tidak Ditemukan', token: 'danger' },
                    message: data.message || 'Kode tiket tidak ditemukan di sistem.',
                    code: codeStr,
                });
                setSheetOpen(true);
                playBeep(false);
                vibrate([40, 60, 40]);
                return;
            }

            if (data.preview) {
                data.preview.method = method;
                setPreviewType('booking');
                setCurrentPreview(data.preview);
                setSheetOpen(true);
                setShowFullPhone(false);
                vibrate([40]);
                if (data.preview.verdict === 'siap_hadir' || data.preview.verdict === 'sudah_hadir') {
                    playBeep(true);
                } else {
                    playBeep(false);
                }
            }
        } catch {
            setIsSearching(false);
            showClientAlert('Gagal menghubungi server. Periksa koneksi internet Anda.');
            playBeep(false);
        }
    }, [selectedSessionId, playBeep]);

    // Handle Scanned Raw Text
    const handleScannedRawText = useCallback((rawText) => {
        if (!rawText) return;
        const trimmed = rawText.trim();

        let parsedCode = '';
        if (trimmed.includes('/tiket/')) {
            const parts = trimmed.split('/tiket/');
            parsedCode = (parts[1] || '').split(/[?#]/)[0];
        } else if (trimmed.includes('/member/')) {
            const parts = trimmed.split('/member/');
            parsedCode = (parts[1] || '').split(/[?#]/)[0];
        } else if (trimmed.startsWith('MBR-') || trimmed.startsWith('SYNEMAX-MBR:')) {
            parsedCode = trimmed;
        } else if (trimmed.startsWith('HC-') || /^[A-Z0-9\-]{4,24}$/i.test(trimmed)) {
            parsedCode = trimmed;
        }

        if (!parsedCode) {
            showClientAlert('QR Code ini bukan tiket atau kartu member SynemaX.');
            return;
        }

        const now = Date.now();
        if (parsedCode === lastScannedCodeRef.current && (now - lastScannedTimeRef.current) < 3000) {
            return;
        }

        lastScannedCodeRef.current = parsedCode;
        lastScannedTimeRef.current = now;

        performLookup(parsedCode, 'qr');
    }, [performLookup]);

    // Capture & Scan Frame
    const captureAndScanFrame = useCallback(async () => {
        if (sheetOpen || isCameraPaused || !videoRef.current || !canvasRef.current) return;
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

        // 1. BarcodeDetector native API
        if ('BarcodeDetector' in window) {
            try {
                const barcodeDetector = new window.BarcodeDetector({ formats: ['qr_code'] });
                const barcodes = await barcodeDetector.detect(canvas);
                if (barcodes && barcodes.length > 0 && barcodes[0].rawValue) {
                    handleScannedRawText(barcodes[0].rawValue);
                    return;
                }
            } catch {
                // Fallback to jsQR
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
                // Ignore frame parse error
            }
        }
    }, [sheetOpen, isCameraPaused, handleScannedRawText]);

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
            setActiveTab('kode');
            return;
        }

        stopCamera();

        const constraints = {
            audio: false,
            video: {
                facingMode: { ideal: cameraFacingMode },
                width: { ideal: 1280 },
                height: { ideal: 720 },
                deviceId: selectedCameraId ? { exact: selectedCameraId } : undefined,
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

            // Enumerate devices
            try {
                const devices = await navigator.mediaDevices.enumerateDevices();
                const vDevices = devices.filter((d) => d.kind === 'videoinput');
                setVideoDevices(vDevices);
                if (!selectedCameraId && vDevices.length > 0) {
                    setSelectedCameraId(vDevices[0].deviceId);
                }
            } catch {
                // Ignore device enumeration error
            }

            // Start scanning loop (~110ms interval)
            scanIntervalRef.current = setInterval(captureAndScanFrame, 110);
        } catch (err) {
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                setCameraStatus('denied');
                setActiveTab('kode');
            } else if (err.name === 'NotFoundError' || err.name === 'DevicesNotFoundError') {
                setCameraStatus('not_found');
                setActiveTab('kode');
            } else {
                setCameraStatus('error');
                setCameraErrorMessage(err.message || 'Kamera tidak dapat diakses.');
            }
        }
    }, [cameraFacingMode, selectedCameraId, stopCamera, captureAndScanFrame]);

    // Flip Camera Facing
    const flipCameraFacing = () => {
        setCameraFacingMode((prev) => (prev === 'environment' ? 'user' : 'environment'));
        setSelectedCameraId('');
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

    // Toggle Camera Pause
    const toggleCameraPause = () => {
        setIsCameraPaused((prev) => {
            const next = !prev;
            if (next) {
                if (scanIntervalRef.current) clearInterval(scanIntervalRef.current);
            } else {
                scanIntervalRef.current = setInterval(captureAndScanFrame, 110);
            }
            return next;
        });
    };

    // Handle Tab Switch
    const switchTab = (tab) => {
        setActiveTab(tab);
        if (tab === 'kamera') {
            if (cameraStatus === 'active' && !isCameraPaused) {
                if (scanIntervalRef.current) clearInterval(scanIntervalRef.current);
                scanIntervalRef.current = setInterval(captureAndScanFrame, 110);
            } else if (cameraStatus === 'idle') {
                startCamera();
            }
        } else {
            if (scanIntervalRef.current) {
                clearInterval(scanIntervalRef.current);
                scanIntervalRef.current = null;
            }
            if (tab === 'daftar') {
                loadSessionBookings();
            }
        }
    };

    // Close Verdict Sheet
    const closeSheet = () => {
        setSheetOpen(false);
        setCurrentPreview(null);
        setMemberData(null);
        if (autoCloseTimerRef.current) {
            clearTimeout(autoCloseTimerRef.current);
            autoCloseTimerRef.current = null;
        }
    };

    // Execute Ticket Check-in
    const executeCheckIn = async (isOverride = false) => {
        if (!currentPreview || !currentPreview.booking_id) return;
        setIsSubmitting(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const res = await fetch('/admin/scan/hadir', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    booking_id: currentPreview.booking_id,
                    method: currentPreview.method || 'qr',
                    allow_override: isOverride,
                    reason: isOverride ? overrideReason : null,
                    session_id: selectedSessionId,
                }),
            });

            const data = await res.json();
            setIsSubmitting(false);
            setOverrideModalOpen(false);
            setOverrideReason('');

            if (data.preview) {
                setCurrentPreview(data.preview);
            } else {
                setCurrentPreview((prev) => ({
                    ...prev,
                    message: data.message,
                    can_check_in: false,
                }));
            }

            if (data.success) {
                playBeep(true);
                vibrate([40]);
                loadSessionBookings();

                autoCloseTimerRef.current = setTimeout(() => {
                    closeSheet();
                }, 2200);
            } else {
                playBeep(false);
            }
        } catch {
            setIsSubmitting(false);
            showClientAlert('Gagal memproses tanda hadir. Periksa koneksi internet.');
            playBeep(false);
        }
    };

    // Execute Member Check-in
    const executeMemberCheckIn = async (isOverride = false) => {
        if (!memberData || !memberData.member) return;
        setIsSubmitting(true);

        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            const targetSessionId = selectedSessionId !== 'all' ? selectedSessionId : (memberData.session?.id || 'none');
            const res = await fetch('/admin/scan/member-hadir', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    member_id: memberData.member.id,
                    session_id: targetSessionId,
                    booking_id: selectedMemberBookingId || null,
                    method: activeTab === 'kamera' ? 'qr' : 'kode',
                    allow_override: isOverride,
                    override_reason: isOverride ? memberOverrideReason : null,
                }),
            });

            const data = await res.json();
            setIsSubmitting(false);
            setMemberOverrideReason('');

            if (data.success) {
                setMemberData((prev) => ({
                    ...prev,
                    can_record: false,
                    verdict: 'sudah_tercatat_sesi_ini',
                    message: data.message,
                    member: {
                        ...prev.member,
                        last_valid_visit_id: data.visit?.id,
                    },
                }));
                setMemberRecordedReward(data.reward);
                playBeep(true);
                vibrate([40]);
            } else {
                setMemberData((prev) => ({
                    ...prev,
                    message: data.message,
                }));
                playBeep(false);
            }
        } catch {
            setIsSubmitting(false);
            showClientAlert('Gagal mencatat kunjungan member. Coba lagi.');
            playBeep(false);
        }
    };

    // Execute Member Undo
    const executeMemberUndo = async () => {
        if (!memberData || !memberData.member?.last_valid_visit_id) return;
        setIsSubmitting(true);

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
                    member_id: memberData.member.id,
                    visit_id: memberData.member.last_valid_visit_id,
                    reason: memberVoidReason || 'Dibatalkan oleh petugas',
                }),
            });

            const data = await res.json();
            setIsSubmitting(false);
            setMemberVoidModalOpen(false);
            setMemberVoidReason('');

            if (data.success) {
                setMemberData((prev) => ({
                    ...prev,
                    can_record: true,
                    verdict: 'siap_dicatat',
                    message: data.message,
                    member: {
                        ...prev.member,
                        last_valid_visit_id: null,
                    },
                }));
                setMemberRecordedReward(null);
                playBeep(true);
            } else {
                showClientAlert(data.message || 'Gagal membatalkan kunjungan.');
                playBeep(false);
            }
        } catch {
            setIsSubmitting(false);
            showClientAlert('Gagal membatalkan kunjungan. Coba lagi.');
            playBeep(false);
        }
    };

    // Settle Member Reward
    const settleMemberReward = async (action, note = '') => {
        if (!memberRecordedReward) return;
        setIsSubmitting(true);

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
                    reward_id: memberRecordedReward.id,
                    action,
                    note,
                }),
            });

            const data = await res.json();
            setIsSubmitting(false);

            if (data.success) {
                setMemberRecordedReward((prev) => ({
                    ...prev,
                    status: action,
                }));
                playBeep(true);
            }
        } catch {
            setIsSubmitting(false);
            showClientAlert('Gagal memperbarui status reward.');
        }
    };

    // Live search debounced
    const handleLiveSearchChange = (val) => {
        setLiveSearchInput(val);
        if (liveSearchTimerRef.current) clearTimeout(liveSearchTimerRef.current);
        const q = val.trim();
        if (q.length < 2) {
            setLiveSearchResults([]);
            return;
        }

        liveSearchTimerRef.current = setTimeout(async () => {
            try {
                const url = new URL('/admin/booking/search', window.location.origin);
                url.searchParams.set('q', q);
                const res = await fetch(url.toString(), {
                    headers: { Accept: 'application/json' },
                });
                if (res.ok) {
                    const data = await res.json();
                    setLiveSearchResults(Array.isArray(data) ? data : (data.bookings || []));
                }
            } catch {
                // Ignore
            }
        }, 250);
    };

    // Paste from clipboard
    const pasteFromClipboard = async () => {
        if (navigator.clipboard && navigator.clipboard.readText) {
            try {
                const text = await navigator.clipboard.readText();
                if (text) {
                    setManualCodeInput(text.trim());
                }
            } catch {
                // Clipboard blocked
            }
        }
    };

    // Submit manual code
    const handleManualCodeSubmit = (e) => {
        e?.preventDefault();
        const code = manualCodeInput.trim();
        if (!code) return;
        setAmbiguousMatches([]);
        performLookup(code, 'kode');
    };

    // Setup & teardown
    useEffect(() => {
        // Check secure context
        if (typeof window !== 'undefined') {
            if (!window.isSecureContext && location.protocol !== 'https:' && location.hostname !== 'localhost' && location.hostname !== '127.0.0.1' && !location.hostname.endsWith('.test')) {
                setCameraStatus('unsupported_protocol');
                setActiveTab('kode');
            }
        }

        loadSessionBookings();

        // 20s polling for session bookings
        const pollingId = setInterval(() => {
            if (!document.hidden && activeTab === 'daftar') {
                loadSessionBookings();
            }
        }, 20000);

        return () => {
            clearInterval(pollingId);
            stopCamera();
            if (autoCloseTimerRef.current) clearTimeout(autoCloseTimerRef.current);
            if (liveSearchTimerRef.current) clearTimeout(liveSearchTimerRef.current);
        };
    }, [loadSessionBookings, activeTab, stopCamera]);

    // Re-start camera when cameraFacingMode or selectedCameraId changes
    useEffect(() => {
        if (activeTab === 'kamera' && cameraStatus === 'active') {
            startCamera();
        }
    }, [cameraFacingMode, selectedCameraId, activeTab, cameraStatus, startCamera]);

    // Filtered session list items
    const filteredSessionItems = sessionListItems.filter((item) => {
        if (daftarFilter === 'belum' && item.is_hadir) return false;
        if (daftarFilter === 'hadir' && !item.is_hadir) return false;
        if (daftarSearch.trim()) {
            const q = daftarSearch.trim().toLowerCase();
            const name = (item.customer_name || '').toLowerCase();
            const code = (item.code || '').toLowerCase();
            return name.includes(q) || code.includes(q);
        }
        return true;
    });

    const sessionListHadirCount = sessionListItems.filter((i) => i.is_hadir).length;
    const sessionListBelumCount = sessionListItems.filter((i) => !i.is_hadir).length;
    const totalSessionBookings = sessionListItems.length;
    const sessionProgressPct = totalSessionBookings > 0 ? Math.round((sessionListHadirCount / totalSessionBookings) * 100) : 0;

    // Selected session label
    const selectedSessionObj = todaySessions.find((s) => String(s.id) === String(selectedSessionId));
    const currentFilmTitle = selectedSessionId === 'all' ? 'Semua Sesi' : (selectedSessionObj?.film?.title || 'Sesi Hari Ini');

    return (
        <AdminLayout title="Scan Tiket" isFormPage={true}>
            <Head title="Scan Tiket · Pintu Masuk Bioskop" />

            <div className="space-y-4 max-w-4xl mx-auto pb-12">
                {/* 1. Header Toolbar */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-surface border border-border rounded-2xl p-4 shadow-xs">
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-xl bg-amber-500/15 border border-amber-500/30 text-amber-500 font-bold">
                            <QrCode className="size-5" />
                        </div>
                        <div>
                            <div className="flex items-center gap-2">
                                <h1 className="text-base font-black text-text tracking-tight">Scan Tiket</h1>
                                <span className="inline-flex items-center gap-1 rounded-md bg-surface-2 px-2 py-0.5 text-[10px] font-bold text-text-muted border border-border">
                                    <span className={`size-1.5 rounded-full ${cameraStatus === 'active' ? 'bg-emerald-500 animate-pulse' : 'bg-amber-500'}`} />
                                    <span>Pintu Studio</span>
                                </span>
                            </div>
                            <p className="text-xs text-text-muted mt-0.5">Pindai QR e-tiket penonton untuk validasi kehadiran langsung.</p>
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        {/* Switch to Member Scanner */}
                        <Link
                            href="/admin/members/scan"
                            className="inline-flex min-h-[40px] items-center gap-1.5 rounded-xl border border-amber-500/40 bg-amber-500/10 px-3 text-xs font-bold text-amber-600 dark:text-amber-400 hover:bg-amber-500/20 transition shadow-xs"
                            title="Buka Scan Khusus Kartu Member"
                        >
                            <Users className="size-4 text-amber-500" />
                            <span>Scan Member</span>
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
                            title={soundEnabled ? 'Bunyi aktif (klik untuk membisukan)' : 'Bunyi mati (klik untuk aktifkan)'}
                            aria-label="Toggle Suara"
                        >
                            {soundEnabled ? <Volume2 className="size-4.5" /> : <VolumeX className="size-4.5" />}
                        </button>
                    </div>
                </div>

                {/* 2. Context Bar: Session Selector & Attendance Metrics */}
                <div className="rounded-2xl border border-border bg-surface p-4 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div className="flex-1 max-w-md">
                        <label htmlFor="session-select" className="sr-only">Pilih Sesi</label>
                        <select
                            id="session-select"
                            value={selectedSessionId}
                            onChange={(e) => {
                                setSelectedSessionId(e.target.value);
                            }}
                            className="w-full min-h-[42px] rounded-xl border border-border bg-surface-2 px-3 text-xs font-bold text-text focus:border-amber-500 focus:outline-none"
                        >
                            <option value="all">Semua Sesi Hari Ini</option>
                            {todaySessions.map((s) => (
                                <option key={s.id} value={String(s.id)}>
                                    {formatTime(s.start_time)} · {s.film?.title || 'Sesi Film'}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div className="flex items-center justify-between sm:justify-end gap-3 text-xs shrink-0">
                        <div className="flex items-center gap-2">
                            <span className="font-bold text-text font-tabular tracking-tight">
                                Hadir {sessionListHadirCount} dari {totalSessionBookings} orang
                            </span>
                            <div className="w-16 h-1.5 rounded-full bg-border overflow-hidden hidden sm:block">
                                <div
                                    className="h-full bg-emerald-500 transition-all duration-300"
                                    style={{ width: `${sessionProgressPct}%` }}
                                />
                            </div>
                        </div>
                        <span className="rounded-md bg-amber-500/15 px-2.5 py-1 text-[11px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/20 truncate max-w-[160px]">
                            {currentFilmTitle}
                        </span>
                    </div>
                </div>

                {/* 3. Segmented Tab Bar */}
                <div className="grid grid-cols-3 p-1 rounded-2xl bg-surface-2 border border-border/80 gap-1 text-center shadow-xs">
                    <button
                        type="button"
                        onClick={() => switchTab('kamera')}
                        className={`min-h-[44px] flex items-center justify-center gap-1.5 py-2 px-3 text-xs font-bold rounded-xl transition ${
                            activeTab === 'kamera'
                                ? 'bg-surface text-amber-600 dark:text-amber-400 shadow-xs border border-border/60'
                                : 'text-text-muted hover:text-text'
                        }`}
                    >
                        <Camera className="size-4 shrink-0" />
                        <span>Kamera</span>
                    </button>

                    <button
                        type="button"
                        onClick={() => switchTab('kode')}
                        className={`min-h-[44px] flex items-center justify-center gap-1.5 py-2 px-3 text-xs font-bold rounded-xl transition ${
                            activeTab === 'kode'
                                ? 'bg-surface text-amber-600 dark:text-amber-400 shadow-xs border border-border/60'
                                : 'text-text-muted hover:text-text'
                        }`}
                    >
                        <Hash className="size-4 shrink-0" />
                        <span>Kode</span>
                        {ambiguousMatches.length > 0 && (
                            <span className="size-2 rounded-full bg-amber-500 animate-ping" />
                        )}
                    </button>

                    <button
                        type="button"
                        onClick={() => switchTab('daftar')}
                        className={`min-h-[44px] flex items-center justify-center gap-1.5 py-2 px-3 text-xs font-bold rounded-xl transition ${
                            activeTab === 'daftar'
                                ? 'bg-surface text-amber-600 dark:text-amber-400 shadow-xs border border-border/60'
                                : 'text-text-muted hover:text-text'
                        }`}
                    >
                        <ListFilter className="size-4 shrink-0" />
                        <span>Daftar</span>
                        {sessionListBelumCount > 0 && (
                            <span className="rounded-full bg-amber-500/20 px-1.5 py-0.2 text-[10px] font-bold text-amber-600 dark:text-amber-400">
                                {sessionListBelumCount}
                            </span>
                        )}
                    </button>
                </div>

                {/* 4. Tab Contents */}

                {/* TAB 1: KAMERA */}
                {activeTab === 'kamera' && (
                    <div className="relative rounded-3xl overflow-hidden border border-border bg-black min-h-[420px] sm:min-h-[480px] flex flex-col justify-center items-center shadow-2xl">
                        {/* A. State: Unsupported Protocol */}
                        {cameraStatus === 'unsupported_protocol' && (
                            <div className="m-4 max-w-sm p-6 rounded-2xl border border-rose-500/40 bg-surface text-center space-y-4 shadow-xl">
                                <div className="size-12 mx-auto rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-500">
                                    <AlertTriangle className="size-6" />
                                </div>
                                <div>
                                    <h2 className="font-bold text-sm text-rose-600">Kamera Membutuhkan HTTPS</h2>
                                    <p className="text-xs text-text-muted mt-1 leading-relaxed">
                                        Peramban membatasi akses kamera jika tidak memakai protokol aman (HTTPS) atau localhost. Silakan gunakan tab Kode atau Daftar untuk input cepat.
                                    </p>
                                </div>
                                <div className="flex flex-col gap-2 pt-1">
                                    <button
                                        type="button"
                                        onClick={() => switchTab('kode')}
                                        className="w-full min-h-[44px] rounded-xl bg-amber-500 px-4 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                                    >
                                        Gunakan Tab Kode
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* B. State: Permission Denied */}
                        {cameraStatus === 'denied' && (
                            <div className="m-4 max-w-sm p-6 rounded-2xl border border-amber-500/40 bg-surface text-center space-y-4 shadow-xl">
                                <div className="size-12 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-500">
                                    <Camera className="size-6" />
                                </div>
                                <div>
                                    <h2 className="font-bold text-sm text-amber-600">Izin Kamera Ditolak</h2>
                                    <p className="text-xs text-text-muted mt-1 leading-relaxed">
                                        Akses modul kamera dinonaktifkan di peramban Anda. Izinkan kamera di pengaturan situs atau gunakan tab Kode.
                                    </p>
                                </div>
                                <div className="flex gap-2 pt-1">
                                    <button
                                        type="button"
                                        onClick={startCamera}
                                        className="min-h-[44px] flex-1 rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
                                    >
                                        Coba Lagi
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => switchTab('kode')}
                                        className="min-h-[44px] flex-1 rounded-xl bg-amber-500 px-4 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                                    >
                                        Buka Tab Kode
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* C. State: Camera Not Found */}
                        {cameraStatus === 'not_found' && (
                            <div className="m-4 max-w-sm p-6 rounded-2xl border border-rose-500/40 bg-surface text-center space-y-4 shadow-xl">
                                <div className="size-12 mx-auto rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-500">
                                    <XCircle className="size-6" />
                                </div>
                                <div>
                                    <h2 className="font-bold text-sm text-rose-600">Kamera Tidak Ditemukan</h2>
                                    <p className="text-xs text-text-muted mt-1 leading-relaxed">
                                        Perangkat ini tidak memiliki modul kamera yang terdeteksi atau dibatasi oleh sistem.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={() => switchTab('kode')}
                                    className="w-full min-h-[44px] rounded-xl bg-amber-500 px-4 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                                >
                                    Gunakan Tab Kode
                                </button>
                            </div>
                        )}

                        {/* D. State: Error */}
                        {cameraStatus === 'error' && (
                            <div className="m-4 max-w-sm p-6 rounded-2xl border border-rose-500/40 bg-surface text-center space-y-4 shadow-xl">
                                <div className="size-12 mx-auto rounded-2xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center text-rose-500">
                                    <AlertTriangle className="size-6" />
                                </div>
                                <div>
                                    <h2 className="font-bold text-sm text-rose-600">Kamera Tidak Dapat Dibuka</h2>
                                    <p className="text-xs text-text-muted mt-1 leading-relaxed">
                                        {cameraErrorMessage || 'Kamera sedang dipakai aplikasi lain atau sistem mengalami kendala.'}
                                    </p>
                                </div>
                                <div className="flex gap-2 pt-1">
                                    <button
                                        type="button"
                                        onClick={startCamera}
                                        className="min-h-[44px] flex-1 rounded-xl border border-border bg-surface px-4 text-xs font-semibold text-text hover:bg-surface-2 transition"
                                    >
                                        Muat Ulang
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => switchTab('kode')}
                                        className="min-h-[44px] flex-1 rounded-xl bg-amber-500 px-4 text-xs font-bold text-slate-950 hover:bg-amber-400 transition"
                                    >
                                        Buka Tab Kode
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* E. State: Idle / Prompt */}
                        {cameraStatus === 'idle' && (
                            <div className="m-auto max-w-sm p-6 sm:p-8 rounded-2xl border border-border bg-surface text-center space-y-4 shadow-2xl">
                                <div className="size-16 mx-auto rounded-2xl bg-amber-500/10 border border-amber-500/30 flex items-center justify-center text-amber-500">
                                    <Camera className="size-8" />
                                </div>
                                <div>
                                    <h2 className="text-base font-bold text-text">Izinkan Kamera untuk Memindai</h2>
                                    <p className="text-xs text-text-muted mt-1 leading-relaxed">
                                        Arahkan kamera ke QR Code e-tiket penonton untuk validasi kehadiran instan.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    onClick={startCamera}
                                    className="w-full min-h-[48px] inline-flex items-center justify-center gap-2 rounded-xl bg-amber-500 px-5 text-sm font-bold text-slate-950 hover:bg-amber-400 transition shadow-sm"
                                >
                                    <Camera className="size-4.5" />
                                    <span>Aktifkan Kamera</span>
                                </button>
                            </div>
                        )}

                        {/* F. Viewfinder Aktif */}
                        <div className={`relative w-full h-full min-h-[420px] sm:min-h-[480px] flex items-center justify-center overflow-hidden ${cameraStatus === 'active' ? '' : 'hidden'}`}>
                            <video
                                ref={videoRef}
                                playsInline
                                muted
                                autoPlay
                                className="absolute inset-0 w-full h-full object-cover"
                            />
                            <canvas ref={canvasRef} className="hidden" />

                            {/* Kotak Reticle Presisi */}
                            <div className="relative size-64 sm:size-72 border-2 border-amber-400/80 rounded-2xl pointer-events-none z-10 flex items-center justify-center overflow-hidden">
                                <span className="absolute -top-1 -left-1 size-6 border-t-4 border-l-4 border-amber-400 rounded-tl-xl" />
                                <span className="absolute -top-1 -right-1 size-6 border-t-4 border-r-4 border-amber-400 rounded-tr-xl" />
                                <span className="absolute -bottom-1 -left-1 size-6 border-b-4 border-l-4 border-amber-400 rounded-bl-xl" />
                                <span className="absolute -bottom-1 -right-1 size-6 border-b-4 border-r-4 border-amber-400 rounded-br-xl" />

                                {/* Garis Laser Animasi */}
                                <div className="absolute inset-x-3 h-0.5 bg-amber-400/90 scan-laser-beam pointer-events-none" />

                                <span className="text-[11px] font-bold text-white bg-black/75 px-3 py-1 rounded-full backdrop-blur-sm border border-white/10">
                                    Arahkan QR ke kotak ini
                                </span>
                            </div>

                            {/* Floating Toolbar Kontrol Kamera */}
                            <div className="absolute top-3 inset-x-3 z-20 flex items-center justify-between gap-2">
                                {videoDevices.length > 1 && (
                                    <select
                                        value={selectedCameraId}
                                        onChange={(e) => setSelectedCameraId(e.target.value)}
                                        className="h-9 max-w-[160px] rounded-xl border border-white/20 bg-black/70 px-2.5 text-[11px] font-semibold text-white backdrop-blur focus:outline-none"
                                    >
                                        {videoDevices.map((d, idx) => (
                                            <option key={d.deviceId || idx} value={d.deviceId} className="bg-slate-900 text-white">
                                                {d.label || `Kamera ${idx + 1}`}
                                            </option>
                                        ))}
                                    </select>
                                )}

                                <div className="flex items-center gap-2 ml-auto">
                                    {videoDevices.length > 1 && (
                                        <button
                                            type="button"
                                            onClick={flipCameraFacing}
                                            className="inline-flex size-9 sm:size-10 items-center justify-center rounded-xl border border-white/20 bg-black/70 text-white backdrop-blur transition hover:bg-black/90"
                                            title="Balik kamera (Depan/Belakang)"
                                        >
                                            <RotateCw className="size-4" />
                                        </button>
                                    )}

                                    {hasTorch && (
                                        <button
                                            type="button"
                                            onClick={toggleTorch}
                                            className={`inline-flex size-9 sm:size-10 items-center justify-center rounded-xl border border-white/20 backdrop-blur transition ${
                                                isTorchOn ? 'bg-amber-400 text-slate-950 font-bold' : 'bg-black/70 text-white'
                                            }`}
                                            title="Senter"
                                        >
                                            <Flashlight className="size-4" />
                                        </button>
                                    )}

                                    <button
                                        type="button"
                                        onClick={toggleCameraPause}
                                        className={`inline-flex size-9 sm:size-10 items-center justify-center rounded-xl border border-white/20 backdrop-blur transition ${
                                            isCameraPaused ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-black/70 text-white'
                                        }`}
                                        title={isCameraPaused ? 'Lanjutkan pemindaian' : 'Jeda kamera'}
                                    >
                                        {isCameraPaused ? <Play className="size-4" /> : <Pause className="size-4" />}
                                    </button>
                                </div>
                            </div>

                            {/* Floating Bottom Status Chip */}
                            <div className="absolute bottom-3 inset-x-3 z-10 flex justify-center pointer-events-none">
                                <span className="inline-flex items-center gap-1.5 rounded-full bg-black/75 border border-white/10 px-3.5 py-1 text-[11px] font-semibold text-white/90 backdrop-blur-sm">
                                    <span className="size-2 rounded-full bg-amber-400 animate-ping" />
                                    <span>Mencari QR Code tiket...</span>
                                </span>
                            </div>

                            {/* Banner Peringatan Tidak Valid */}
                            {clientAlertMessage && (
                                <div className="absolute bottom-12 inset-x-4 z-20 p-3 rounded-xl border border-rose-500/50 bg-black/90 text-center text-xs font-semibold text-rose-300 backdrop-blur shadow-xl animate-in fade-in">
                                    {clientAlertMessage}
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* TAB 2: KODE MANUAL & LIVE SEARCH */}
                {activeTab === 'kode' && (
                    <div className="space-y-4">
                        {/* Card Input Kode Tiket */}
                        <div className="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-xs space-y-3">
                            <div className="flex items-center justify-between">
                                <label htmlFor="manual-code-input" className="block text-xs font-bold uppercase tracking-wider text-text">
                                    Masukkan Kode Tiket atau 4-6 Karakter Akhir
                                </label>
                                <span className="text-[11px] text-text-muted font-mono">Kode Suffix / URL</span>
                            </div>

                            <form onSubmit={handleManualCodeSubmit} className="flex gap-2">
                                <div className="relative flex-1">
                                    <input
                                        id="manual-code-input"
                                        type="text"
                                        value={manualCodeInput}
                                        onChange={(e) => setManualCodeInput(e.target.value)}
                                        placeholder="HC-261103-XXXXXX atau XXXXXX"
                                        autoCapitalize="characters"
                                        autoComplete="off"
                                        spellCheck="false"
                                        className="h-14 w-full rounded-xl border border-border bg-surface-2 px-4 font-mono text-base sm:text-lg font-bold tracking-wider text-text uppercase placeholder:normal-case placeholder:font-sans placeholder:text-xs placeholder:font-normal placeholder:text-text-muted focus:border-amber-400 focus:outline-none pr-10"
                                    />
                                    {manualCodeInput.length > 0 && (
                                        <button
                                            type="button"
                                            onClick={() => setManualCodeInput('')}
                                            className="absolute right-3 top-1/2 -translate-y-1/2 size-7 rounded-lg inline-flex items-center justify-center text-text-muted hover:text-text hover:bg-surface transition"
                                            title="Hapus teks"
                                        >
                                            <X className="size-4" />
                                        </button>
                                    )}
                                </div>

                                <button
                                    type="submit"
                                    disabled={isSearching || !manualCodeInput.trim()}
                                    className="h-14 px-6 rounded-xl bg-amber-500 text-sm font-bold text-slate-950 hover:bg-amber-400 disabled:opacity-40 transition shadow-sm shrink-0 flex items-center justify-center gap-2"
                                >
                                    {isSearching ? <RefreshCw className="size-4 animate-spin" /> : <Search className="size-4" />}
                                    <span>Cari</span>
                                </button>
                            </form>

                            <div className="flex flex-wrap items-center justify-between gap-2 text-[11px] text-text-muted pt-1">
                                <span>Bisa memasukkan kode lengkap atau cukup 4-6 digit terakhir kode booking.</span>
                                <button
                                    type="button"
                                    onClick={pasteFromClipboard}
                                    className="inline-flex items-center gap-1 font-semibold text-amber-600 dark:text-amber-400 hover:underline"
                                >
                                    <Clipboard className="size-3.5" />
                                    <span>Tempel Clipboard</span>
                                </button>
                            </div>
                        </div>

                        {/* Ambiguous matches */}
                        {ambiguousMatches.length > 0 && (
                            <div className="rounded-2xl border border-amber-500/40 bg-surface p-4 sm:p-5 space-y-3 shadow-xs">
                                <div className="flex items-center gap-2">
                                    <span className="size-2 rounded-full bg-amber-500" />
                                    <p className="text-xs font-bold text-amber-600 dark:text-amber-400">
                                        Ditemukan beberapa tiket dengan akhiran tersebut. Pilih untuk verifikasi:
                                    </p>
                                </div>
                                <div className="divide-y divide-border rounded-xl border border-border overflow-hidden">
                                    {ambiguousMatches.map((item) => (
                                        <button
                                            key={item.id}
                                            type="button"
                                            onClick={() => performLookup(item.code, 'kode')}
                                            className="w-full py-3 px-3.5 flex items-center justify-between text-left hover:bg-surface-2 transition"
                                        >
                                            <div>
                                                <p className="text-xs font-bold text-text">{item.customer_name}</p>
                                                <p className="font-mono text-xs text-amber-500 font-bold">{item.code}</p>
                                            </div>
                                            <div className="text-right text-xs text-text-muted">
                                                <span className="font-bold text-text">{item.people_count || item.pax || 1} orang</span> · <span>{formatTime(item.start_time)}</span>
                                            </div>
                                        </button>
                                    ))}
                                </div>
                            </div>
                        )}

                        {/* Card Live Search Nama / WhatsApp */}
                        <div className="rounded-2xl border border-border bg-surface p-5 sm:p-6 shadow-xs space-y-3">
                            <label htmlFor="live-search-input" className="block text-xs font-bold uppercase tracking-wider text-text">
                                Cari Nama Pemesan atau WhatsApp
                            </label>
                            <div className="relative">
                                <input
                                    id="live-search-input"
                                    type="text"
                                    value={liveSearchInput}
                                    onChange={(e) => handleLiveSearchChange(e.target.value)}
                                    placeholder="Ketik nama penonton atau nomor telepon..."
                                    className="h-12 w-full rounded-xl border border-border bg-surface-2 pl-10 pr-4 text-xs font-semibold text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none"
                                />
                                <Search className="size-4 text-text-muted absolute left-3.5 top-4 pointer-events-none" />
                            </div>

                            {liveSearchResults.length > 0 && (
                                <div className="divide-y divide-border rounded-xl border border-border overflow-hidden pt-1 max-h-60 overflow-y-auto">
                                    {liveSearchResults.map((res) => (
                                        <button
                                            key={res.id}
                                            type="button"
                                            onClick={() => performLookup(res.code, 'kode')}
                                            className="w-full py-2.5 px-3 flex items-center justify-between text-left hover:bg-surface-2 transition"
                                        >
                                            <div>
                                                <p className="text-xs font-bold text-text">{res.customer_name}</p>
                                                <p className="text-[11px] text-text-muted font-mono">{res.code}</p>
                                            </div>
                                            <span className="rounded bg-amber-500/10 px-2.5 py-1 text-[10px] font-bold text-amber-600 dark:text-amber-400 border border-amber-500/20">
                                                Verifikasi
                                            </span>
                                        </button>
                                    ))}
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* TAB 3: DAFTAR SESI */}
                {activeTab === 'daftar' && (
                    <div className="space-y-4">
                        {/* Header & Filter Controls */}
                        <div className="rounded-2xl border border-border bg-surface p-4 sm:p-5 shadow-xs space-y-3">
                            <div className="flex items-center justify-between gap-2">
                                <div>
                                    <p className="text-xs font-bold text-text">{sessionSummary.text || 'Daftar Hadir Sesi'}</p>
                                    <p className="text-[10px] text-text-muted mt-0.5">Pembaruan otomatis tiap 20 detik saat tab aktif.</p>
                                </div>
                                <button
                                    type="button"
                                    onClick={loadSessionBookings}
                                    disabled={isLoadingDaftar}
                                    className="inline-flex size-9 items-center justify-center rounded-xl border border-border bg-surface text-text hover:bg-surface-2 transition shadow-xs"
                                    title="Segarkan daftar"
                                >
                                    <RefreshCw className={`size-4 ${isLoadingDaftar ? 'animate-spin text-amber-500' : ''}`} />
                                </button>
                            </div>

                            <div className="pt-2 border-t border-border flex flex-col sm:flex-row gap-2.5 items-stretch sm:items-center justify-between">
                                <div className="inline-flex p-1 rounded-xl bg-surface-2 border border-border text-xs font-semibold gap-1">
                                    <button
                                        type="button"
                                        onClick={() => setDaftarFilter('all')}
                                        className={`px-2.5 py-1 rounded-lg transition text-[11px] ${
                                            daftarFilter === 'all' ? 'bg-surface text-text font-bold shadow-xs' : 'text-text-muted hover:text-text'
                                        }`}
                                    >
                                        Semua ({sessionListItems.length})
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setDaftarFilter('belum')}
                                        className={`px-2.5 py-1 rounded-lg transition text-[11px] ${
                                            daftarFilter === 'belum' ? 'bg-surface text-amber-600 dark:text-amber-400 font-bold shadow-xs' : 'text-text-muted hover:text-text'
                                        }`}
                                    >
                                        Belum ({sessionListBelumCount})
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setDaftarFilter('hadir')}
                                        className={`px-2.5 py-1 rounded-lg transition text-[11px] ${
                                            daftarFilter === 'hadir' ? 'bg-surface text-emerald-600 dark:text-emerald-400 font-bold shadow-xs' : 'text-text-muted hover:text-text'
                                        }`}
                                    >
                                        Hadir ({sessionListHadirCount})
                                    </button>
                                </div>

                                <div className="relative flex-1 sm:max-w-xs">
                                    <input
                                        type="text"
                                        value={daftarSearch}
                                        onChange={(e) => setDaftarSearch(e.target.value)}
                                        placeholder="Filter nama / kode..."
                                        className="h-9 w-full rounded-xl border border-border bg-surface-2 pl-8 pr-3 text-xs text-text placeholder:text-text-muted focus:border-amber-400 focus:outline-none"
                                    />
                                    <Search className="size-3.5 text-text-muted absolute left-2.5 top-3 pointer-events-none" />
                                </div>
                            </div>
                        </div>

                        {/* List Bookings */}
                        <div className="rounded-2xl border border-border bg-surface divide-y divide-border overflow-hidden shadow-xs">
                            {filteredSessionItems.length === 0 ? (
                                <div className="p-8 text-center space-y-2">
                                    <Users className="size-8 mx-auto text-text-muted/60" />
                                    <p className="text-xs text-text-muted">Tidak ada booking yang cocok dengan filter yang dipilih.</p>
                                </div>
                            ) : (
                                filteredSessionItems.map((b) => (
                                    <div
                                        key={b.id}
                                        onClick={() => performLookup(b.code, 'daftar')}
                                        className={`p-3.5 sm:p-4 flex items-center justify-between gap-3 hover:bg-surface-2 transition cursor-pointer ${
                                            b.is_hadir ? 'bg-surface-2/20' : ''
                                        }`}
                                    >
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <p className="text-xs sm:text-sm font-bold text-text truncate">{b.customer_name}</p>
                                                <span
                                                    className={`rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider shrink-0 ${
                                                        b.is_hadir
                                                            ? 'bg-emerald-500/15 text-emerald-600 dark:text-emerald-400 border border-emerald-500/20'
                                                            : b.flow_state === 'aktif'
                                                            ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400 border border-amber-500/20'
                                                            : 'bg-rose-500/15 text-rose-600 dark:text-rose-400 border border-rose-500/20'
                                                    }`}
                                                >
                                                    {b.chip || (b.is_hadir ? 'Hadir' : 'Belum Hadir')}
                                                </span>
                                            </div>
                                            <div className="flex flex-wrap items-center gap-x-2 text-[11px] text-text-muted mt-1">
                                                <span className="font-mono text-text-muted font-semibold">{b.code}</span>
                                                <span>·</span>
                                                <span className="font-bold text-text">{b.people_count || b.pax || 1} orang</span>
                                                <span>·</span>
                                                <span>{b.session_time || '-'}</span>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-2 shrink-0">
                                            {!b.is_hadir && (
                                                <span className="hidden sm:inline-flex rounded-lg bg-emerald-600 text-white text-[11px] font-bold px-2.5 py-1 shadow-xs">
                                                    Check-In
                                                </span>
                                            )}
                                            <ArrowRight className="size-4 text-text-muted" />
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                )}
            </div>

            {/* ============================================================== */}
            {/* 5. BOTTOM SHEET / MODAL PRATINJAU TIKET                        */}
            {/* ============================================================== */}
            {sheetOpen && (
                <>
                    {/* Backdrop */}
                    <div
                        className="fixed inset-0 z-40 bg-black/60 backdrop-blur-xs transition-opacity"
                        onClick={closeSheet}
                    />

                    {/* Sheet Container */}
                    <div className="fixed inset-x-0 bottom-0 sm:bottom-8 sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 z-50 w-full sm:max-w-lg max-h-[90vh] overflow-y-auto rounded-t-3xl sm:rounded-3xl border-t sm:border border-border bg-surface p-5 sm:p-6 shadow-2xl space-y-4 pb-[max(1.25rem,env(safe-area-inset-bottom))] animate-in slide-in-from-bottom duration-200">
                        {/* Header Sheet */}
                        <div className="flex items-center justify-between pb-3 border-b border-border">
                            <div className="flex items-center gap-2">
                                <span
                                    className={`rounded-lg px-2.5 py-1 text-xs font-bold uppercase tracking-wider ${
                                        previewType === 'member'
                                            ? 'bg-amber-500/20 text-amber-700 dark:text-amber-300 border border-amber-500/40'
                                            : currentPreview?.verdict === 'siap_hadir'
                                            ? 'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300 border border-emerald-500/40'
                                            : 'bg-rose-500/20 text-rose-700 dark:text-rose-300 border border-rose-500/40'
                                    }`}
                                >
                                    {previewType === 'member'
                                        ? `Member ${memberData?.member?.status || ''}`
                                        : currentPreview?.verdict_badge?.label || 'Hasil Validasi'}
                                </span>
                                <span className="font-mono text-xs font-bold text-text-muted">
                                    {previewType === 'member' ? memberData?.member?.code : currentPreview?.code}
                                </span>
                            </div>
                            <button
                                type="button"
                                onClick={closeSheet}
                                className="size-8 inline-flex items-center justify-center rounded-xl border border-border text-text hover:bg-surface-2 transition"
                                aria-label="Tutup panel"
                            >
                                <X className="size-4" />
                            </button>
                        </div>

                        {/* Verdict Message Banner */}
                        <div
                            className={`p-3.5 rounded-xl border text-xs font-bold ${
                                previewType === 'member'
                                    ? memberData?.can_record
                                        ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300'
                                        : 'border-amber-500/40 bg-amber-500/10 text-amber-800 dark:text-amber-300'
                                    : currentPreview?.verdict === 'siap_hadir'
                                    ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300'
                                    : currentPreview?.verdict === 'sudah_hadir'
                                    ? 'border-amber-500/40 bg-amber-500/10 text-amber-800 dark:text-amber-300'
                                    : 'border-rose-500/40 bg-rose-500/10 text-rose-800 dark:text-rose-300'
                            }`}
                        >
                            <p>{previewType === 'member' ? memberData?.message : currentPreview?.message}</p>
                            {previewType !== 'member' && currentPreview?.checked_in_info && (
                                <p className="mt-1 font-semibold text-[11px]">{currentPreview.checked_in_info}</p>
                            )}
                        </div>

                        {/* TAMPILAN KHUSUS MEMBER */}
                        {previewType === 'member' && memberData && (
                            <div className="space-y-4">
                                <div className="rounded-2xl border border-border bg-surface-2/40 p-4 space-y-3 text-xs">
                                    <div className="flex items-center gap-3.5">
                                        {memberData.member.photo_url ? (
                                            <img
                                                src={memberData.member.photo_url}
                                                alt="Foto Member"
                                                className="size-14 rounded-xl object-cover shrink-0 border border-border"
                                            />
                                        ) : (
                                            <div className="size-14 rounded-xl border border-border bg-surface-2 flex items-center justify-center font-extrabold text-lg text-text-muted shrink-0">
                                                {memberData.member.name.charAt(0).toUpperCase()}
                                            </div>
                                        )}
                                        <div className="min-w-0 flex-1">
                                            <p className="text-sm font-extrabold text-text">{memberData.member.name}</p>
                                            <p className="font-mono text-xs font-bold text-amber-500 mt-0.5">{memberData.member.code}</p>
                                            <p className="text-[11px] text-text-muted mt-0.5">Petugas wajib mencocokkan foto dan nama member di pintu.</p>
                                        </div>
                                    </div>

                                    {/* Progres Siklus */}
                                    <div className="pt-2 border-t border-border space-y-1.5">
                                        <div className="flex items-center justify-between text-[11px]">
                                            <span className="font-bold text-text">
                                                Kunjungan {memberData.member.current_in_cycle} dari 10 (Siklus {memberData.member.cycle_number})
                                            </span>
                                            <span className="text-text-muted font-semibold">
                                                Total {memberData.member.total_valid_visits} kunjungan
                                            </span>
                                        </div>
                                        <div className="relative h-2 w-full rounded-full bg-border overflow-hidden">
                                            <div
                                                className="h-full rounded-full bg-amber-500 transition-all duration-300"
                                                style={{ width: `${(memberData.member.current_in_cycle / 10) * 100}%` }}
                                            />
                                        </div>
                                        <p className="text-[11px] text-text-muted">{memberData.member.progress_text}</p>
                                    </div>

                                    {/* Sesi & Booking Terpilih */}
                                    <div className="pt-2 border-t border-border space-y-2 text-[11px]">
                                        <div>
                                            <span className="font-bold text-text block mb-1">Sesi Penayangan:</span>
                                            <div className="rounded-xl border border-border bg-surface p-2.5 font-semibold text-text">
                                                {memberData.session ? `${memberData.session.film_title} · ${memberData.session.time}` : 'Tidak ada sesi hari ini'}
                                            </div>
                                        </div>

                                        {memberData.session_bookings && memberData.session_bookings.length > 0 && (
                                            <div>
                                                <label htmlFor="member-booking-select" className="font-bold text-text block mb-1">Hubungkan ke Booking Sesi (Opsional):</label>
                                                <select
                                                    id="member-booking-select"
                                                    value={selectedMemberBookingId}
                                                    onChange={(e) => setSelectedMemberBookingId(e.target.value)}
                                                    className="w-full min-h-[40px] rounded-xl border border-border bg-surface px-3 text-xs text-text"
                                                >
                                                    <option value="">Tanpa booking (datang langsung di kasir)</option>
                                                    {memberData.session_bookings.map((b) => (
                                                        <option key={b.id} value={b.id}>
                                                            {b.code} - {b.customer_name} ({b.pax} pax)
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Banner Reward Baru */}
                                {memberRecordedReward && (
                                    <div className="rounded-2xl border-2 border-amber-500 bg-amber-500/15 p-4 space-y-3">
                                        <div className="flex items-start gap-2.5">
                                            <span className="rounded-md bg-amber-500 px-2 py-0.5 text-[10px] font-black uppercase text-black">HADIAH KUNJUNGAN</span>
                                            <p className="text-sm font-extrabold text-amber-600 dark:text-amber-400">
                                                REWARD: {memberRecordedReward.type === 'free' ? 'GRATIS TIKET NONTON' : `POTONGAN Rp ${Number(memberRecordedReward.amount).toLocaleString('id-ID')}`}
                                            </p>
                                        </div>
                                        <div className="text-xs text-text space-y-1">
                                            <p className="font-semibold text-text">Metode Penyelesaian:</p>
                                            <p className="text-text-muted">
                                                {memberRecordedReward.settlement === 'deducted_bill'
                                                    ? 'Potongan dikurangkan langsung dari sisa tagihan booking di kasir.'
                                                    : memberRecordedReward.settlement === 'refund_cash'
                                                    ? 'Pengembalian tunai wajib diberikan langsung kepada pelanggan karena tiket sudah lunas online.'
                                                    : 'Potongan / tiket gratis langsung diberikan untuk kunjungan saat ini.'}
                                            </p>
                                        </div>
                                        {memberRecordedReward.status === 'pending' && (
                                            <div className="flex items-center gap-2 pt-1">
                                                <button
                                                    type="button"
                                                    onClick={() => settleMemberReward('given')}
                                                    className="flex-1 min-h-[40px] rounded-xl bg-emerald-600 text-xs font-bold text-white hover:bg-emerald-500 transition"
                                                >
                                                    Sudah Diberikan
                                                </button>
                                                <button
                                                    type="button"
                                                    onClick={() => settleMemberReward('skipped', 'Dilewati oleh petugas')}
                                                    className="min-h-[40px] rounded-xl border border-border bg-surface px-3 text-xs font-semibold text-text-muted hover:text-text transition"
                                                >
                                                    Lewati
                                                </button>
                                            </div>
                                        )}
                                        {memberRecordedReward.status === 'given' && (
                                            <div className="text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                                Status Reward: Berhasil ditandai sudah diberikan.
                                            </div>
                                        )}
                                    </div>
                                )}

                                {/* Tombol Aksi Member */}
                                <div className="space-y-2 pt-1">
                                    {memberData.can_record && !memberData.requires_override && (
                                        <button
                                            type="button"
                                            onClick={() => executeMemberCheckIn(false)}
                                            disabled={isSubmitting}
                                            className="w-full h-14 rounded-xl bg-emerald-600 text-base font-black text-white hover:bg-emerald-500 active:scale-[0.98] transition shadow-md flex items-center justify-center gap-2"
                                        >
                                            <span>Catat Kunjungan Member</span>
                                        </button>
                                    )}

                                    {memberData.can_record && memberData.requires_override && (
                                        <div className="space-y-2">
                                            <input
                                                type="text"
                                                value={memberOverrideReason}
                                                onChange={(e) => setMemberOverrideReason(e.target.value)}
                                                placeholder="Alasan override catatan kunjungan (wajib)..."
                                                className="w-full min-h-[40px] rounded-xl border border-amber-500/50 bg-surface px-3 text-xs text-text focus:border-amber-500"
                                            />
                                            <button
                                                type="button"
                                                onClick={() => executeMemberCheckIn(true)}
                                                disabled={isSubmitting || !memberOverrideReason.trim()}
                                                className="w-full h-14 rounded-xl bg-amber-500 text-sm font-bold text-black hover:bg-amber-400 active:scale-[0.98] transition shadow-md flex items-center justify-center gap-2 disabled:opacity-50"
                                            >
                                                <span>Konfirmasi Catat Kunjungan (Override)</span>
                                            </button>
                                        </div>
                                    )}

                                    {memberData.verdict === 'sudah_tercatat_sesi_ini' && memberData.member.last_valid_visit_id && (
                                        <button
                                            type="button"
                                            onClick={() => setMemberVoidModalOpen(true)}
                                            className="w-full min-h-[44px] rounded-xl border border-rose-500/40 bg-rose-500/10 text-xs font-bold text-rose-600 dark:text-rose-400 hover:bg-rose-500/20 transition flex items-center justify-center gap-2"
                                        >
                                            <span>Batalkan Kunjungan Terakhir Member</span>
                                        </button>
                                    )}

                                    <button
                                        type="button"
                                        onClick={closeSheet}
                                        className="w-full min-h-[44px] rounded-xl border border-border bg-surface text-xs font-semibold text-text hover:bg-surface-2 transition"
                                    >
                                        Tutup Panel
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* TAMPILAN TIKET BOOKING BIASA */}
                        {previewType !== 'member' && currentPreview && currentPreview.customer_name && (
                            <div className="rounded-2xl border border-border bg-surface-2/40 p-4 space-y-3 text-xs">
                                <div className="flex gap-3">
                                    {currentPreview.film_poster_url && (
                                        <img
                                            src={currentPreview.film_poster_url}
                                            alt="Poster Film"
                                            className="size-16 rounded-xl object-cover shrink-0 border border-border"
                                        />
                                    )}
                                    <div className="min-w-0 flex-1">
                                        <p className="text-sm font-bold text-text">{currentPreview.customer_name}</p>
                                        <p className="font-bold text-amber-600 dark:text-amber-400 mt-0.5 truncate">{currentPreview.film_title}</p>
                                        <p className="text-[11px] text-text-muted mt-0.5">
                                            <span>{currentPreview.session_date_formatted}</span> · <span>{currentPreview.session_time_formatted}</span>
                                        </p>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-2 pt-2 border-t border-border text-[11px]">
                                    <div>
                                        <span className="text-text-muted block">Paket & Penonton:</span>
                                        <strong className="text-text font-bold">{currentPreview.package_display}</strong>
                                    </div>
                                    <div>
                                        <span className="text-text-muted block">Status Bayar:</span>
                                        <strong className="text-text font-bold">{currentPreview.payment_status_label}</strong>
                                    </div>
                                </div>

                                {/* WhatsApp Phone Masking Toggle */}
                                <div className="pt-2 border-t border-border flex items-center justify-between text-[11px]">
                                    <div>
                                        <span className="text-text-muted block">WhatsApp:</span>
                                        <strong className="text-text font-mono">
                                            {showFullPhone ? currentPreview.whatsapp_raw : currentPreview.whatsapp_masked}
                                        </strong>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => setShowFullPhone(!showFullPhone)}
                                        className="px-2.5 py-1 rounded-lg border border-border bg-surface text-[10px] font-semibold text-text hover:bg-surface-2 transition"
                                    >
                                        {showFullPhone ? 'Sembunyikan' : 'Lihat nomor'}
                                    </button>
                                </div>

                                {currentPreview.snacks_summary && currentPreview.snacks_summary !== 'Tidak ada jajan' && (
                                    <div className="pt-2 border-t border-border text-[11px]">
                                        <span className="text-text-muted block">Pesanan Jajan:</span>
                                        <p className="font-semibold text-text">{currentPreview.snacks_summary}</p>
                                    </div>
                                )}

                                {currentPreview.notes && (
                                    <div className="pt-2 border-t border-border text-[11px] bg-amber-500/10 p-2.5 rounded-xl border border-amber-500/20">
                                        <span className="font-bold text-amber-700 dark:text-amber-300 block">Catatan Pemesan:</span>
                                        <p className="text-text">{currentPreview.notes}</p>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Tombol Aksi Booking */}
                        {previewType !== 'member' && (
                            <div className="space-y-2 pt-1">
                                {currentPreview?.can_check_in && (
                                    <button
                                        type="button"
                                        onClick={() => executeCheckIn(false)}
                                        disabled={isSubmitting}
                                        className="w-full h-14 rounded-xl bg-emerald-600 text-base font-black text-white hover:bg-emerald-500 active:scale-[0.98] transition shadow-md flex items-center justify-center gap-2"
                                    >
                                        <Check className="size-5" />
                                        <span>Tandai hadir sekarang</span>
                                    </button>
                                )}

                                {currentPreview?.can_override && !currentPreview?.can_check_in && (
                                    <button
                                        type="button"
                                        onClick={() => setOverrideModalOpen(true)}
                                        className="w-full h-14 rounded-xl bg-amber-500 text-sm font-bold text-slate-950 hover:bg-amber-400 active:scale-[0.98] transition shadow-sm flex items-center justify-center gap-2"
                                    >
                                        <AlertTriangle className="size-4.5" />
                                        <span>Tandai hadir tetap (Override)</span>
                                    </button>
                                )}

                                {currentPreview?.detail_url && (currentPreview.verdict === 'belum_bayar' || currentPreview.verdict === 'diproses') && (
                                    <a
                                        href={currentPreview.detail_url}
                                        className="w-full min-h-[44px] rounded-xl border border-border bg-surface text-xs font-bold text-text hover:bg-surface-2 transition flex items-center justify-center gap-2"
                                    >
                                        <span>Buka detail booking di admin</span>
                                        <ExternalLink className="size-3.5" />
                                    </a>
                                )}

                                <button
                                    type="button"
                                    onClick={closeSheet}
                                    className="w-full min-h-[44px] rounded-xl border border-border bg-surface text-xs font-semibold text-text-muted hover:text-text hover:bg-surface-2 transition"
                                >
                                    Tutup
                                </button>
                            </div>
                        )}
                    </div>
                </>
            )}

            {/* ============================================================== */}
            {/* 6. MODAL OVERRIDE CHECK-IN                                     */}
            {/* ============================================================== */}
            {overrideModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
                    <div className="w-full max-w-md rounded-2xl border border-border bg-surface p-5 shadow-2xl space-y-4">
                        <div className="flex items-center justify-between border-b border-border pb-3">
                            <h3 className="font-bold text-sm text-text">Konfirmasi Hadir Luar Jadwal</h3>
                            <button
                                type="button"
                                onClick={() => setOverrideModalOpen(false)}
                                className="size-8 inline-flex items-center justify-center rounded-lg text-text-muted hover:bg-surface-2"
                            >
                                <X className="size-4" />
                            </button>
                        </div>
                        <p className="text-xs text-text-muted leading-relaxed">{currentPreview?.message}</p>
                        <p className="text-xs text-amber-600 dark:text-amber-400 font-semibold">
                            Tiket ini berada di luar batas waktu resmi. Jika Anda melanjutkan, kehadiran akan dicatat dengan status override dalam jejak audit.
                        </p>
                        <div>
                            <label htmlFor="override_reason" className="block text-xs font-semibold text-text mb-1">
                                Alasan override (opsional):
                            </label>
                            <input
                                id="override_reason"
                                type="text"
                                value={overrideReason}
                                onChange={(e) => setOverrideReason(e.target.value)}
                                maxLength={120}
                                placeholder="Contoh: Tamu datang lebih awal atas izin pengelola"
                                className="w-full min-h-[40px] rounded-xl border border-border bg-surface-2 px-3 text-xs text-text focus:outline-none focus:ring-2 focus:ring-amber-500"
                            />
                        </div>
                        <div className="flex items-center justify-end gap-2 pt-2 border-t border-border">
                            <button
                                type="button"
                                onClick={() => setOverrideModalOpen(false)}
                                className="min-h-[40px] px-4 rounded-xl border border-border bg-surface text-xs font-semibold text-text hover:bg-surface-2"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                onClick={() => executeCheckIn(true)}
                                className="min-h-[40px] px-4 rounded-xl bg-amber-500 text-xs font-bold text-slate-950 hover:bg-amber-400"
                            >
                                Ya, Tandai Hadir Tetap
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* MODAL BATALKAN KUNJUNGAN MEMBER */}
            {memberVoidModalOpen && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-xs">
                    <div className="w-full max-w-md rounded-2xl border border-border bg-surface p-5 shadow-2xl space-y-4">
                        <div className="flex items-center justify-between border-b border-border pb-3">
                            <h3 className="font-bold text-sm text-text">Batalkan Kunjungan Terakhir Member</h3>
                            <button
                                type="button"
                                onClick={() => setMemberVoidModalOpen(false)}
                                className="size-8 inline-flex items-center justify-center rounded-lg text-text-muted hover:bg-surface-2"
                            >
                                <X className="size-4" />
                            </button>
                        </div>
                        <p className="text-xs text-text-muted leading-relaxed">
                            Pembatalan ini akan membatalkan status sah kunjungan terakhir dan reward terkait.
                        </p>
                        <div>
                            <label htmlFor="member_void_reason" className="block text-xs font-semibold text-text mb-1">
                                Alasan pembatalan (wajib):
                            </label>
                            <input
                                id="member_void_reason"
                                type="text"
                                value={memberVoidReason}
                                onChange={(e) => setMemberVoidReason(e.target.value)}
                                maxLength={120}
                                placeholder="Contoh: Salah scan kartu / penonton batal menonton..."
                                className="w-full min-h-[40px] rounded-xl border border-border bg-surface-2 px-3 text-xs text-text focus:outline-none focus:ring-2 focus:ring-rose-500"
                            />
                        </div>
                        <div className="flex items-center justify-end gap-2 pt-2 border-t border-border">
                            <button
                                type="button"
                                onClick={() => setMemberVoidModalOpen(false)}
                                className="min-h-[40px] px-4 rounded-xl border border-border bg-surface text-xs font-semibold text-text hover:bg-surface-2"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                onClick={executeMemberUndo}
                                disabled={!memberVoidReason.trim()}
                                className="min-h-[40px] px-4 rounded-xl bg-rose-600 text-xs font-bold text-white hover:bg-rose-500 disabled:opacity-50"
                            >
                                Ya, Batalkan Kunjungan
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </AdminLayout>
    );
}
