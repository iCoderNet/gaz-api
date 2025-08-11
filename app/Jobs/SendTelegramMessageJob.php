<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\TelegramBotService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendTelegramMessageJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $userId,
        public string $message,
        public string $jobBatchId
    ) {}

    public function handle(TelegramBotService $telegramBotService)
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        // 35ms delay to avoid hitting Telegram rate limits
        usleep(35000);

        $user = User::where('tg_id', $this->userId)->first();

        if (!$user) {
            Log::error("User not found: {$this->userId}");
            return;
        }

        try {
            $response = $telegramBotService->sendMessage($user->tg_id, $this->message);

            $status = ($response && ($response['ok'] ?? false)) ? 'success' : 'failed';

            $this->batch()?->add(
                new UpdateMessageStatusJob($this->jobBatchId, $this->userId, $status)
            );

        } catch (\Throwable $e) {
            Log::error("Error sending message to user {$this->userId}: " . $e->getMessage());

            $this->batch()?->add(
                new UpdateMessageStatusJob($this->jobBatchId, $this->userId, 'failed')
            );
        }
    }
}
