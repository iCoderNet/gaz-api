<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\AdditionalService;

class AdditionalServiceController extends Controller
{
    public function index()
    {
        $validator = Validator::make(request()->all(), [
            'per_page'   => 'integer|min:1|max:100',
            'search'     => 'string|nullable',
            'status'     => 'in:active,archive',
            'sort_by'    => 'in:id,name,price,created_at',
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
        $status    = request()->get('status');
        $sortBy    = request()->get('sort_by', 'id');
        $sortOrder = request()->get('sort_order', 'desc');

        $query = AdditionalService::where('status', '!=', 'deleted');

        // 🔍 Search
        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        // 🧮 Filter
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
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
            'status' => ['nullable', Rule::in(['active', 'archive'])],
        ]);

        $service = AdditionalService::create($data);
        return response()->json($service, 201);
    }

    public function show(AdditionalService $service)
    {
        if ($service->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        return response()->json($service);
    }

    public function update(Request $request, AdditionalService $service)
    {
        if ($service->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        $data = $request->validate([
            'name' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'status' => ['nullable', Rule::in(['active', 'archive'])],
        ]);

        $service->update($data);
        return response()->json($service);
    }

    public function destroy(AdditionalService $service)
    {
        if ($service->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        $service->update(['status' => 'deleted']);
        return response()->json(['message' => 'Additional service soft deleted']);
    }

    public function publicIndex()
    {
        $validator = Validator::make(request()->all(), [
            'per_page'   => 'integer|min:1|max:100',
            'search'     => 'string|nullable',
            'sort_by'    => 'in:id,name,price,created_at',
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
        $sortBy    = request()->get('sort_by', 'id');
        $sortOrder = request()->get('sort_order', 'desc');

        $query = AdditionalService::where('status', 'active');

        // 🔍 Search
        if ($search) {
            $query->where('name', 'like', "%$search%");
        }

        // 🔃 Sort
        $query->orderBy($sortBy, $sortOrder);

        // 📦 Pagination
        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }

    public function publicShow(AdditionalService $service)
    {
        if ($service->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $service
        ]);
    }
}
