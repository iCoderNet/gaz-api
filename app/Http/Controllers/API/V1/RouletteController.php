<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\RouletteSpin;
use App\Models\RouletteItem;
use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RouletteController extends Controller
{
    /**
     * Check if user can spin the roulette
     */
    public function canSpin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tg_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('tg_id', $request->tg_id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Check if roulette is enabled
        $rouletteEnabled = Setting::where('key', 'enable_roulette')->value('value');

        if (!$rouletteEnabled) {
            return response()->json([
                'success' => false,
                'can_spin' => false,
                'message' => 'Roulette is disabled',
            ]);
        }

        $frequency = Setting::where('key', 'roulette_frequency')->value('value') ?? 'per_order';
        if ($frequency === 'daily') {
            // Check if user already spun today
            $hasSpunToday = RouletteSpin::forUser($user->id)
                ->today()
                ->exists();

            if ($hasSpunToday) {
                return response()->json([
                    'success' => true,
                    'can_spin' => false,
                    'message' => 'Already spun today',
                    'frequency' => 'daily',
                ]);
            }
        } elseif ($frequency === 'per_order') {
            // Check if order_id is provided and valid
            if ($request->has('order_id')) {
                $orderId = $request->order_id;

                // Check if order belongs to user
                $order = \App\Models\Order::where('id', $orderId)
                    ->where('user_id', $user->id)
                    ->first();

                if (!$order) {
                    return response()->json([
                        'success' => true,
                        'can_spin' => false,
                        'message' => 'Order not found or does not belong to user',
                        'frequency' => 'per_order',
                    ]);
                }

                // Check if order already used for spin
                $alreadySpun = RouletteSpin::where('order_id', $orderId)->exists();

                if ($alreadySpun) {
                    return response()->json([
                        'success' => true,
                        'can_spin' => false,
                        'message' => 'This order has already been used for a spin',
                        'frequency' => 'per_order',
                    ]);
                }
            } else {
                return response()->json([
                    'success' => false,
                    'can_spin' => false,
                    'message' => 'Order ID is required for per-order spins',
                    'frequency' => 'per_order',
                ], 422);
            }
        }

        return response()->json([
            'success' => true,
            'can_spin' => true,
            'message' => 'Can spin the roulette',
            'frequency' => $frequency,
        ]);
    }

    /**
     * Spin the roulette and get a random prize
     */
    public function spin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tg_id' => 'required|string',
            'order_id' => 'nullable|exists:orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('tg_id', $request->tg_id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        // Check if can spin
        $canSpinResponse = $this->canSpin($request);
        $canSpinData = $canSpinResponse->getData();

        if (!$canSpinData->can_spin) {
            return response()->json([
                'success' => false,
                'message' => $canSpinData->message ?? 'Cannot spin the roulette',
            ], 403);
        }

        // Additional check for per_order in spin method to ensure order_id is present
        $frequency = Setting::where('key', 'roulette_frequency')->value('value') ?? 'per_order';
        if ($frequency === 'per_order') {
            if (!$request->has('order_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order ID is required for per-order spins',
                ], 422);
            }

            // Explicitly check order ownership
            $order = \App\Models\Order::where('id', $request->order_id)
                ->where('user_id', $user->id)
                ->first();

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Order not found or does not belong to user',
                ], 403);
            }
        }

        // Get active roulette items
        $items = RouletteItem::active()->get();

        if ($items->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No roulette items available',
            ], 400);
        }

        // Validate total probability
        $totalProbability = $items->sum('probability');

        if ($totalProbability <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid probability configuration',
            ], 500);
        }

        // Weighted random selection
        $selectedItem = $this->weightedRandomSelection($items, $totalProbability);

        // Save the spin result
        $spin = RouletteSpin::create([
            'user_id' => $user->id,
            'order_id' => $request->order_id,
            'roulette_item_id' => $selectedItem->id,
        ]);

        $spin->load(['rouletteItem.accessory', 'order']);

        // Notifications
        try {
            // 1. Notify Admin (Immediate)
            $admins = User::where('role', 'admin')->whereNotNull('tg_id')->get();
            $adminMessage = "🎰 <b>Рулетка сыграна!</b>\n\n" .
                "👤 Пользователь: {$user->username} ({$user->phone})\n" .
                "🎁 Выигрыш: <b>{$selectedItem->title}</b>\n" .
                "🆔 ID игры: {$spin->id}";

            if ($request->order_id) {
                $adminMessage .= "\n📦 ID заказа: {$request->order_id}";
            }

            $telegramService = new \App\Services\TelegramBotService();
            foreach ($admins as $admin) {
                $telegramService->sendMessage($admin->tg_id, $adminMessage);
            }

            // 2. Notify User (Delayed 3 seconds)
            $userMessage = "🎉 Поздравляем!\n\n" .
                "Вы выиграли <b>{$selectedItem->title}</b>! 🎁";

            \App\Jobs\SendSingleTelegramMessageJob::dispatch($user->tg_id, $userMessage)
                ->delay(now()->addSeconds(3));

        } catch (\Exception $e) {
            Log::error("Error sending roulette notifications: " . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => 'Roulette spun successfully',
            'data' => [
                'spin_id' => $spin->id,
                'prize' => [
                    'id' => $selectedItem->id,
                    'title' => $selectedItem->title,
                    'description' => $selectedItem->description,
                    'image_url' => $selectedItem->image_url,
                    'accessory' => $selectedItem->accessory,
                ],
                'created_at' => $spin->created_at,
            ],
        ], 201);
    }

    /**
     * Weighted random selection based on probability
     */
    private function weightedRandomSelection($items, $totalProbability)
    {
        // Generate random number between 0 and total probability
        $random = mt_rand(0, $totalProbability * 100) / 100;

        $cumulative = 0;
        foreach ($items as $item) {
            $cumulative += $item->probability;
            if ($random <= $cumulative) {
                return $item;
            }
        }

        // Fallback (should not reach here)
        return $items->last();
    }

    /**
     * Get user's spin history
     */
    public function userHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tg_id' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('tg_id', $request->tg_id)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $spins = RouletteSpin::forUser($user->id)
            ->with(['rouletteItem.accessory', 'order'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $spins,
        ]);
    }

    /**
     * Get all spins history (Admin only)
     */
    public function history(Request $request)
    {
        $query = RouletteSpin::with(['user', 'rouletteItem.accessory', 'order']);

        // Filter by user
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter by prize
        if ($request->has('roulette_item_id')) {
            $query->where('roulette_item_id', $request->roulette_item_id);
        }

        $perPage = min($request->input('per_page', 15), 100);
        $spins = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $spins,
        ]);
    }

    /**
     * Get roulette statistics (Admin only)
     */
    public function statistics()
    {
        $totalSpins = RouletteSpin::count();
        $spinsToday = RouletteSpin::today()->count();

        $prizeDistribution = RouletteSpin::select('roulette_item_id', DB::raw('count(*) as count'))
            ->with('rouletteItem')
            ->groupBy('roulette_item_id')
            ->orderBy('count', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'total_spins' => $totalSpins,
                'spins_today' => $spinsToday,
                'prize_distribution' => $prizeDistribution,
            ],
        ]);
    }
}
