<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Validation\Rule;

class UserController extends Controller
{

    public function index()
    {
        $validator = Validator::make(request()->all(), [
            'per_page'   => 'integer|min:1|max:100',
            'search'     => 'string|nullable',
            'role'       => 'in:admin,user',
            'status'     => 'in:active,inactive,blocked',
            'sort_by'    => 'in:id,tg_id,username,phone,address,created_at',
            'sort_order' => 'in:asc,desc',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $perPage   = request()->get('per_page', 10);
        $search    = request()->get('search');
        $role      = request()->get('role');
        $status    = request()->get('status');
        $sortBy    = request()->get('sort_by', 'id');
        $sortOrder = request()->get('sort_order', 'desc');

        $query = User::where('status', '!=', 'deleted');

        // 🔍 Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('tg_id', 'like', "%$search%")
                ->orWhere('username', 'like', "%$search%")
                ->orWhere('phone', 'like', "%$search%")
                ->orWhere('address', 'like', "%$search%");
            });
        }

        // 🧮 Filter
        if ($role) {
            $query->where('role', $role);
        }

        if ($status) {
            $query->where('status', $status);
        }

        // 🔃 Sort
        $query->orderBy($sortBy, $sortOrder);

        // 📦 Pagination
        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }



    public function store(Request $request)
    {
        $data = $request->validate([
            'tg_id' => 'required|unique:users,tg_id',
            'username' => 'nullable|string|unique:users,username',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'password' => 'nullable|string',
            'role' => ['nullable', Rule::in(['admin', 'user'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'blocked'])],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }

        $user = User::create($data);
        return response()->json([
            'success' => true,
            'message' => ucfirst($user->role) . ' created',
            'data' => $user
        ], 201);
    }

    public function show(User $user)
    {
        if ($user->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }
        return $user;
    }

    public function update(Request $request, User $user)
    {
        if ($user->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        $data = $request->validate([
            'username' => ['nullable', 'string',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'phone' => 'nullable|string',
            'password' => 'nullable|string',
            'address' => 'nullable|string',
            'role' => ['nullable', Rule::in(['admin', 'user'])],
            'status' => ['nullable', Rule::in(['active', 'inactive', 'blocked'])],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = bcrypt($data['password']);
        }
        
        $user->update($data);
        return response()->json([
            'success' => true,
            'message' => ucfirst($user->role).' updated',
            'data' => $user
        ]);
    }

    public function destroy(User $user)
    {
        if ($user->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        $user->update(['status' => 'deleted']);
        return response()->json(['message' => ucfirst($user->role).' soft deleted']);
    }
}