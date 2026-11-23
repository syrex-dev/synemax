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
    public function handle(Request $request, TelegramBotService $botService): JsonResponse
    {
        $payload = $request->all();

        if (empty($payload)) {
            return response()->json(['ok' => true, 'note' => 'Empty payload']);
        }

        try {
            $botService->handleUpdate($payload);
        } catch (Throwable $e) {
            Log::warning('[TelegramWebhook] Error handling update: '.$e->getMessage(), [
                'exception' => $e,
            ]);
        }

        return response()->json(['ok' => true]);
    }
}
