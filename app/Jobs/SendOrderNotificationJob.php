<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\User;
use App\Models\Setting;
use App\Services\TelegramBotService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;
    protected $userId;
    protected $data;

    public function __construct($orderId, $userId, array $data)
    {
        $this->orderId = $orderId;
        $this->userId = $userId;
        $this->data = $data;
    }

    private function PricePipe($value): string
    {
        if ($value === null || $value === '') {
            return '0';
        }

        $num = floatval($value);
        if (!is_numeric($num)) {
            return strval($value);
        }

        return number_format($num, 0, '.', ' ');
    }


    public function handle()
    {
        $userData = User::find($this->userId);
        $order = Order::find($this->orderId);

        if (!$userData || !$order) {
            return;
        }

        $order_notification = Setting::get('order_notification', 'Новый заказ создан ✅');
        $username = $userData->username ? '@' . $userData->username : 'N/A';
        $phone = $this->data['phone'] ?? $userData->phone ?? 'N/A';
        $address = $this->data['address'] ?? $userData->address ?? 'N/A';
        $comment = $this->data['comment'] ?? 'N/A';

        $bot = new TelegramBotService();
        $bot->sendMessage(
            Setting::get('chat_id', '@ninetydev'),
            "<b>$order_notification</b>\n\n" .
            "Заказ: #{$order->id}\n" .
            "Пользователь: {$username} | ID: {$userData->tg_id}\n" .
            "Телефон: {$phone}\n" .
            "Адрес: {$address}\n" .
            "Комментарий: {$comment}\n" .
            "Итоговая цена: ". $this->PricePipe($order->total_price) . " ₽"
        );
    }
}
