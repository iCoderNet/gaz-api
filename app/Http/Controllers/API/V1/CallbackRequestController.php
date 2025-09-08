<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendCallbackRequestNotificationJob;
use App\Models\CallbackRequests;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CallbackRequestController extends Controller
{
    /**
     * List callback requests with search & pagination
     */
    public function index(Request $request)
    {
        $query = CallbackRequests::with(['user', 'admin']);

        // Search by phone, status, or user name
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('phone', 'like', "%{$search}%")
                  ->orWhere('status', 'like', "%{$search}%")
                  ->orWhereHas('user', fn($uq) =>
                      $uq->where('name', 'like', "%{$search}%")
                  );
            });
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $perPage = $request->input('per_page', 15);
        $callbacks = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json($callbacks, Response::HTTP_OK);
    }

    /**
     * User creates a new callback request
     */
    public function store(Request $request)
    {
        $request->validate([
            'tg_id' => 'required|string',
            'phone' => 'required|string',
        ]);

        $user = User::where('tg_id', $request->tg_id)->first();

        if (!$user) {
            return response()->json([
                'message' => 'User not found by tg_id'
            ], 404);
        }

        $callback = CallbackRequests::create([
            'user_id' => $user->id,
            'phone'   => $request->phone,
            'status'  => 'new',
        ]);

        // Telegram notification yuborish
        SendCallbackRequestNotificationJob::dispatch($callback->id);

        return response()->json([
            'message' => 'Callback request created successfully',
            'data'    => $callback,
        ], 201);
    }

    /**
     * Admin updates status of a callback request
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:' . implode(',', CallbackRequests::STATUSES),
        ]);

        $callback = CallbackRequests::findOrFail($id);

        $callback->status = $request->status;
        $callback->admin_id = $request->user()->id; // Authenticated admin
        $callback->save();

        return response()->json([
            'message' => 'Status updated successfully',
            'data'    => $callback,
        ]);
    }

    /**
     * Delete callback request (Admin only)
     */
    public function destroy($id)
    {
        $callback = CallbackRequests::findOrFail($id);
        $callback->delete();

        return response()->json([
            'message' => 'Callback request deleted successfully',
        ]);
    }
}