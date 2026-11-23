<?php

namespace App\Http\Controllers\Telegram;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class TelegramWebhookController extends Controller
{
    /**
     * Default CS support webhook handler (backward compatibility).
     */
    public function handle(Request $request, TelegramBotService $botService): JsonResponse
    {
        return $this->handleCs($request, $botService);
    }

    /**
     * Handle webhook updates for 24/7 CS Support Bot (@SynemaXChat_bot).
     */
    public function handleCs(Request $request, TelegramBotService $botService): JsonResponse
    {
        $payload = $request->all();

        if (empty($payload)) {
            return response()->json(['ok' => true, 'note' => 'Empty payload']);
        }

        try {
            $botService->handleUpdate($payload);
        } catch (Throwable $e) {
            Log::warning('[TelegramWebhook CS] Error handling update: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Handle webhook updates for Admin Control Bot (@SynemaXAdmin_bot).
     */
    public function handleAdmin(Request $request, TelegramBotService $botService): JsonResponse
    {
        $payload = $request->all();

        if (empty($payload)) {
            return response()->json(['ok' => true, 'note' => 'Empty payload']);
        }

        try {
            $botService->handleAdminUpdate($payload);
        } catch (Throwable $e) {
            Log::warning('[TelegramWebhook Admin] Error handling update: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
