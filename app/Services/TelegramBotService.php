<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class TelegramBotService
{
    protected Client $client;
    protected string $botToken;
    protected string $apiUrl;

    /**
     * TelegramBotService constructor.
     *
     * @param string|null $botToken
     */
    public function __construct(?string $botToken = null)
    {
        $settingBotToken = Setting::get('bot_token', '');
        $this->botToken = $botToken ?? $settingBotToken;

        if (empty($this->botToken)) {
            throw new \InvalidArgumentException('Telegram BotToken is required.');
        }

        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}/";

        $this->client = new Client([
            'base_uri' => $this->apiUrl,
            'timeout'  => 5.0,
        ]);
    }

    /**
     * Send a message to Telegram
     *
     * @param int|string $chatId
     * @param string $message
     * @return array|null
     */
    public function sendMessage($chatId, string $message): ?array
    {
        try {
            $response = $this->client->post('sendMessage', [
                'json' => [
                    'chat_id' => $chatId,
                    'text' => $message,
                    'parse_mode' => 'HTML',
                ]
            ]);

            $decoded = json_decode($response->getBody()->getContents(), true);

            Log::info("Telegram message sent to user {$chatId}: ", $decoded);
            return $decoded;

        } catch (\Throwable $e) {
            Log::error("Error sending Telegram message: " . $e->getMessage());
            return null;
        }
    }

    public function sendMessageForAdmins(string $message): ?array
    {
        $users = User::whereNotNull('telegram_id')
                    ->get();

        $responses = [];

        foreach ($users as $user) {
            try {
                $response = $this->client->post('sendMessage', [
                    'json' => [
                        'chat_id' => $user->telegram_id,
                        'text' => $message,
                        'parse_mode' => 'HTML',
                    ]
                ]);

                $decoded = json_decode($response->getBody()->getContents(), true);
                $responses[$user->id] = $decoded;

                // Log response
                Log::info("Telegram message sent to user {$user->id}: ", $decoded);

            } catch (\Throwable $e) {
                Log::error("Error sending Telegram message to user {$user->id}: " . $e->getMessage());
                $responses[$user->id] = null;
            }
        }

        return $responses;
    }
}
