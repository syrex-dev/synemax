<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Telegram\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class InAppChatBotController extends Controller
{
    public function handle(Request $request, TelegramBotService $botService): JsonResponse
    {
        $validated = $request->validate([
            'message' => ['nullable', 'string', 'max:2000'],
            'action' => ['nullable', 'string', 'max:100'],
            'session_id' => ['nullable', 'string', 'max:100'],
            'sender_name' => ['nullable', 'string', 'max:100'],
        ]);

        try {
            $response = $botService->processWebChatMessage(
                message: $validated['message'] ?? null,
                action: $validated['action'] ?? null,
                sessionId: $validated['session_id'] ?? null,
                senderName: $validated['sender_name'] ?? null
            );

            return response()->json($response);
        } catch (Throwable $e) {
            return response()->json([
                'ok' => false,
                'reply' => 'Maaf, terjadi sedikit kendala koneksi ke sistem bot bioskop. Silakan coba kembali dalam beberapa saat.',
                'quick_replies' => [
                    ['label' => 'Coba Lagi', 'action' => 'menu_main'],
                ],
            ], 500);
        }
    }
}
