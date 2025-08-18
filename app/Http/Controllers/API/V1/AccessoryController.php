<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Accessory;
use Illuminate\Support\Facades\Storage;

class AccessoryController extends Controller
{
    public function index()
    {
        $validator = Validator::make(request()->all(), [
            'per_page'   => 'integer|min:1|max:100',
            'search'     => 'string|nullable',
            'status'     => 'in:active,archive',
            'sort_by'    => 'in:id,title,price,created_at',
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

        $query = Accessory::where('status', '!=', 'deleted');

        // 🔍 Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                  ->orWhere('description', 'like', "%$search%");
            });
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
            'title' => 'required|string',
            'price' => 'required|numeric|min:0',
            'image' => 'nullable|file|file|mimes:jpeg,png,jpg,gif,webp,bmp,tiff,svg|max:30720',
            'description' => 'nullable|string',
            'status' => ['nullable', Rule::in(['active', 'archive'])],
        ]);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('accessories', 'public');
        }
        
        $accessory = Accessory::create($data);
        return response()->json($accessory, 201);
    }

    public function show(Accessory $accessory)
    {
        if ($accessory->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }
        return response()->json($accessory);
    }

    public function update(Request $request, Accessory $accessory)
    {
        if ($accessory->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        $data = $request->validate([
            'title' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'image' => 'nullable|file|file|mimes:jpeg,png,jpg,gif,webp,bmp,tiff,svg|max:30720',
            'description' => 'nullable|string',
            'status' => ['nullable', Rule::in(['active', 'archive'])],
        ]);

        if ($request->hasFile('image')) {
            if ($accessory->image) {
                Storage::disk('public')->delete($accessory->image);
            }
            $data['image'] = $request->file('image')->store('accessories', 'public');
        }

        $accessory->update($data);
        return response()->json($accessory);
    }

    public function destroy(Accessory $accessory)
    {
        if ($accessory->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        $accessory->update(['status' => 'deleted']);
        return response()->json(['message' => 'Accessory soft deleted']);
    }

    public function publicIndex()
    {
        $validator = Validator::make(request()->all(), [
            'per_page'   => 'integer|min:1|max:100',
            'search'     => 'string|nullable',
            'sort_by'    => 'in:id,title,price,created_at',
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

        $query = Accessory::where('status', 'active');

        // 🔍 Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                ->orWhere('description', 'like', "%$search%");
            });
        }

        // 🔃 Sort
        $query->orderBy($sortBy, $sortOrder);

        // 📦 Pagination
        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }

    public function publicShow(Accessory $accessory)
    {
        if ($accessory->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $accessory
        ]);
    }

}
