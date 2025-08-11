<?php

namespace App\Http\Controllers\API\V1;


use App\Http\Controllers\Controller;
use App\Jobs\SendTelegramMessageJob;
use App\Models\MessageBatch;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Bus;

class TelegramMessageController extends Controller
{
    /**
     * GET /api/tg-messages
     */
    public function index(Request $request)
    {
        $query = MessageBatch::with('creator')
            ->orderByDesc('created_at');

        if ($search = $request->input('search')) {
            $query->where('message', 'like', "%{$search}%");
        }

        $batches = $query->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $batches,
        ]);
    }

    /**
     * POST /api/tg-messages
     */
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string|min:1|max:4096',
            'tg_ids' => 'nullable|array',
            'tg_ids.*' => 'exists:users,tg_id',
            'send_to_all' => 'boolean',
        ]);

        $userIds = $request->send_to_all
            ? User::pluck('tg_id')->toArray()
            : $request->tg_ids;

        if (empty($userIds)) {
            return response()->json([
                'status' => 'error',
                'message' => 'No users selected',
            ], 422);
        }

        $batch = MessageBatch::create([
            'message' => $request->message,
            'user_ids' => $userIds,
            'stats' => [
                'total' => count($userIds),
                'success' => 0,
                'failed' => 0,
                'pending' => count($userIds),
            ],
            'status' => 'pending',
            'created_by' => auth()->id(),
        ]);

        // Create jobs for each user
        $jobs = collect($userIds)->map(function ($userId) use ($request, $batch) {
            return new SendTelegramMessageJob($userId, $request->message, $batch->id);
        });

        Bus::batch($jobs)
            ->name("Telegram Message Batch #{$batch->id}")
            ->dispatch();

        return response()->json([
            'status' => 'success',
            'message' => 'Message sending started',
            'batch_id' => $batch->id,
        ]);
    }

    /**
     * GET /api/tg-messages/{id}
     */
    public function show(MessageBatch $batch)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'batch' => $batch,
                'users' => User::whereIn('id', $batch->user_ids)
                    ->select(['id', 'tg_id', 'username', 'phone', 'address'])
                    ->get(),
            ]
        ]);
    }
}
