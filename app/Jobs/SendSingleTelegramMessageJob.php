<?php

namespace App\Jobs;

use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSingleTelegramMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $chatId,
        public string $message
    ) {
    }

    public function handle(TelegramBotService $telegramBotService)
    {
        try {
            $telegramBotService->sendMessage($this->chatId, $this->message);
        } catch (\Throwable $e) {
            Log::error("Error sending single Telegram message to {$this->chatId}: " . $e->getMessage());
        }
    }
}
