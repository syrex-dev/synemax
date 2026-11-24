<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotFeedback;
use App\Services\CinemaSettings;
use App\Services\TelegramNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminBotFeedbackController extends Controller
{
    public function index(Request $request, CinemaSettings $settings, TelegramNotificationService $telegram): Response
    {
        $category = $request->query('category', 'all');
        $status = $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));

        $query = BotFeedback::with('booking:id,code,status,session_id')
            ->latest('id');

        if ($category !== 'all' && in_array($category, ['ISSUE_REPORT', 'ORDER_INQUIRY', 'FEEDBACK', 'GENERAL'], true)) {
            $query->where('category', $category);
        }

        if ($status !== 'all' && in_array($status, ['AUTO_RESOLVED', 'FLAGGED', 'RESOLVED'], true)) {
            $query->where('status', $status);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('message', 'like', "%{$search}%")
                    ->orWhere('booking_code', 'like', "%{$search}%")
                    ->orWhere('sender_name', 'like', "%{$search}%")
                    ->orWhere('telegram_username', 'like', "%{$search}%")
                    ->orWhere('telegram_user_id', 'like', "%{$search}%");
            });
        }

        $feedbacks = $query->paginate(20)->withQueryString();

        $stats = [
            'total' => BotFeedback::count(),
            'issues' => BotFeedback::where('category', 'ISSUE_REPORT')->count(),
            'orders' => BotFeedback::where('category', 'ORDER_INQUIRY')->count(),
            'feedbacks' => BotFeedback::where('category', 'FEEDBACK')->count(),
            'flagged' => BotFeedback::where('status', 'FLAGGED')->count(),
        ];

        return Inertia::render('Admin/BotFeedbacks/Index', [
            'feedbacks' => $feedbacks,
            'stats' => $stats,
            'filters' => [
                'category' => $category,
                'status' => $status,
                'q' => $search,
            ],
            'retentionDays' => $settings->feedbackRetentionDays(),
            'botInfo' => [
                'username' => $settings->string('telegram_bot_username'),
                'active' => $telegram->isEnabled(),
                'hasToken' => ! empty($telegram->getDecryptedToken()),
                'adminChatConfigured' => ! empty($telegram->getChatIds()),
            ],
        ]);
    }

    public function cleanup(Request $request, CinemaSettings $settings): RedirectResponse
    {
        $days = (int) $request->input('days', $settings->feedbackRetentionDays());
        if ($days <= 0) {
            $days = 30;
        }

        $cutoff = now()->subDays($days);
        $count = BotFeedback::where('created_at', '<', $cutoff)->count();
        BotFeedback::where('created_at', '<', $cutoff)->delete();

        return back()->with('status', "Berhasil membersihkan {$count} data keluhan/feedback yang lebih lama dari {$days} hari.");
    }

    public function updateStatus(Request $request, BotFeedback $feedback): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:AUTO_RESOLVED,FLAGGED,RESOLVED'],
            'admin_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $feedback->update([
            'status' => $validated['status'],
            'admin_note' => $validated['admin_note'] ?? $feedback->admin_note,
        ]);

        return back()->with('status', "Status tiket {$feedback->ticketCode()} berhasil diperbarui.");
    }

    public function destroy(BotFeedback $feedback): RedirectResponse
    {
        $code = $feedback->ticketCode();
        $feedback->delete();

        return back()->with('status', "Data tiket {$code} berhasil dihapus.");
    }
}
