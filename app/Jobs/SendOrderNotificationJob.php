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

    private function getItemsList(Order $order): string
    {
        $items = [];

        // Fetch Azot items
        foreach ($order->azots as $orderAzot) {
            $azot = $orderAzot->azot;
            $priceType = $orderAzot->priceType;
            $itemName = $azot->title . ($priceType ? ' (' . $priceType->name . ')' : '');
            $items[] = "{$itemName}: {$orderAzot->count} шт";
        }

        // Fetch Accessory items
        foreach ($order->accessories as $orderAccessory) {
            $accessory = $orderAccessory->accessory;
            $items[] = "{$accessory->title}: {$orderAccessory->count} шт";
        }

        // Fetch Service items
        foreach ($order->services as $orderService) {
            $service = $orderService->service;
            $items[] = "{$service->name}: {$orderService->count} шт";
        }

        // If no items, return empty string to avoid unnecessary header
        if (empty($items)) {
            return '';
        }

        // Join items with newlines and prepend header
        return "Товары:\n" . implode("\n", $items);
    }

    public function handle()
    {
        $userData = User::find($this->userId);
        $order = Order::with(['azots.azot.priceTypes', 'accessories.accessory', 'services.service'])->find($this->orderId);

        if (!$userData || !$order) {
            return;
        }

        $order_notification = Setting::get('order_notification', 'Новый заказ создан ✅');
        $username = $userData->username ? '@' . $userData->username : 'N/A';
        $phone = $this->data['phone'] ?? $userData->phone ?? 'N/A';
        $address = $this->data['address'] ?? $userData->address ?? 'N/A';
        $comment = $this->data['comment'] ?? 'N/A';
        $paymentType = $order->payment_type ?? 'N/A';
        $itemsList = $this->getItemsList($order);

        $message = "<b>$order_notification</b>\n\n" .
                   "Заказ: #{$order->order_number}\n" .
                   "Пользователь: {$username} | ID: {$userData->tg_id}\n" .
                   "Телефон: {$phone}\n" .
                   "Адрес: {$address}\n" .
                   "Комментарий: {$comment}\n" .
                   "Тип оплаты: {$paymentType}\n" . // Added payment type
                   ($itemsList ? "{$itemsList}\n" : '') . // Add items list if exists
                   "Итоговая цена: " . $this->PricePipe($order->total_price) . " ₽";

        $bot = new TelegramBotService();
        $bot->sendMessage(
            Setting::get('chat_id', '@ninetydev'),
            $message
        );
    }
}