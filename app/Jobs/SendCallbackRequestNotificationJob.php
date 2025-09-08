<?php

namespace App\Jobs;

use App\Models\CallbackRequests;
use App\Models\User;
use App\Models\Setting;
use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendCallbackRequestNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $callbackRequestId;

    public function __construct($callbackRequestId)
    {
        $this->callbackRequestId = $callbackRequestId;
    }

    public function handle()
    {
        $callbackRequest = CallbackRequests::with('user')->find($this->callbackRequestId);

        if (!$callbackRequest) {
            Log::error("Callback request not found: {$this->callbackRequestId}");
            return;
        }

        $user = $callbackRequest->user;

        if (!$user) {
            Log::error("User not found for callback request: {$this->callbackRequestId}");
            return;
        }

        // Setting dan notification matnini olish
        $callback_notification = Setting::get('callback_notification', 'Новая заявка на обратный звонок 📞');
        
        // Username ni formatlash
        $username = $user->username ? '@' . $user->username : 'N/A';
        
        // Habar matni
        $message = "<b>$callback_notification</b>\n\n" .
                   "ID: #{$callbackRequest->id}\n" .
                   "Пользователь: {$username} | TG ID: {$user->tg_id}\n" .
                   "Телефон: {$callbackRequest->phone}";

        try {
            $bot = new TelegramBotService();
            
            // Admin chat ga habar yuborish
            $chatId = Setting::get('chat_id', '@ninetydev');
            $response = $bot->sendMessage($chatId, $message);

            if ($response && ($response['ok'] ?? false)) {
                Log::info("Callback request notification sent successfully for ID: {$this->callbackRequestId}");
            } else {
                Log::error("Failed to send callback request notification for ID: {$this->callbackRequestId}");
            }

        } catch (\Throwable $e) {
            Log::error("Error sending callback request notification for ID {$this->callbackRequestId}: " . $e->getMessage());
        }
    }
}