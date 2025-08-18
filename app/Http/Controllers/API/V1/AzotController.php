<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Azot;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class AzotController extends Controller
{

    public function index()
    {
        $validator = Validator::make(request()->all(), [
            'per_page'   => 'integer|min:1|max:100',
            'search'     => 'string|nullable',
            'type'       => 'string|nullable',
            'country'    => 'string|nullable',
            'status'     => 'in:active,archive',
            'sort_by'    => 'in:id,title,type,country,created_at',
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
        $type      = request()->get('type');
        $country   = request()->get('country');
        $status    = request()->get('status');
        $sortBy    = request()->get('sort_by', 'id');
        $sortOrder = request()->get('sort_order', 'desc');

        $query = Azot::where('status', '!=', 'deleted')->with('priceTypes');

        // 🔍 Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                ->orWhere('type', 'like', "%$search%")
                ->orWhere('country', 'like', "%$search%")
                ->orWhere('description', 'like', "%$search%");
            });
        }

        // 🧮 Filter
        if ($type) {
            $query->where('type', $type);
        }

        if ($country) {
            $query->where('country', $country);
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
            'title' => 'required|string',
            'type' => 'required|string',
            'image' => 'nullable|file|file|mimes:jpeg,png,jpg,gif,webp,bmp,tiff,svg|max:30720',
            'description' => 'nullable|string',
            'country' => 'required|string',
            'status' => ['nullable', Rule::in(['active', 'archive'])],
            'price_types' => 'nullable|array',
            'price_types.*.name' => 'required|string',
            'price_types.*.price' => 'required|numeric',
        ]);

        if ($request->hasFile('image')) {
            // saqlash papkasi: storage/app/public/azots
            $path = $request->file('image')->store('azots', 'public');
            $data['image'] = $path; // DBda 'azots/filename.jpg' saqlanadi
        }

        $azot = Azot::create($data);

        if (!empty($data['price_types'])) {
            foreach ($data['price_types'] as $pt) {
                $azot->priceTypes()->create($pt);
            }
        }

        return response()->json($azot->load('priceTypes'), 201);
    }



    public function show(Azot $azot)
    {
        if ($azot->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }
        return $azot->load('priceTypes');
    }

    public function update(Request $request, Azot $azot)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'type' => 'required|string',
            'image' => 'nullable|file|file|mimes:jpeg,png,jpg,gif,webp,bmp,tiff,svg|max:30720',
            'description' => 'nullable|string',
            'country' => 'required|string',
            'status' => ['nullable', Rule::in(['active', 'archive'])],
            'price_types' => 'nullable|array',
            'price_types.*.id' => 'nullable|exists:azot_price_types,id',
            'price_types.*.name' => 'required|string',
            'price_types.*.price' => 'required|numeric',
        ]);

        if ($request->hasFile('image')) {
            // eski faylni o'chirish (agar mavjud bo'lsa)
            if ($azot->image && Storage::disk('public')->exists($azot->image)) {
                Storage::disk('public')->delete($azot->image);
            }

            $path = $request->file('image')->store('azots', 'public');
            $data['image'] = $path;
        }

        $azot->update($data);

        if (isset($data['price_types'])) {
            $existingIds = [];

            foreach ($data['price_types'] as $pt) {
                if (isset($pt['id'])) {
                    $existing = $azot->priceTypes()->where('id', $pt['id'])->first();
                    if ($existing) {
                        $existing->update($pt);
                        $existingIds[] = $pt['id'];
                    }
                } else {
                    $new = $azot->priceTypes()->create($pt);
                    $existingIds[] = $new->id;
                }
            }

            // remove old ones
            $azot->priceTypes()->whereNotIn('id', $existingIds)->delete();
        }

        return response()->json($azot->load('priceTypes'));
    }


    public function destroy(Azot $azot)
    {
        if ($azot->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }
        
        $azot->update(['status' => 'deleted']);
        return response()->json(['message' => 'Azot soft deleted']);
    }

    public function publicIndex()
    {
        $validator = Validator::make(request()->all(), [
            'per_page'   => 'integer|min:1|max:100',
            'search'     => 'string|nullable',
            'type'       => 'string|nullable',
            'country'    => 'string|nullable',
            'sort_by'    => 'in:id,title,type,country,created_at',
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
        $type      = request()->get('type');
        $country   = request()->get('country');
        $sortBy    = request()->get('sort_by', 'id');
        $sortOrder = request()->get('sort_order', 'desc');

        $query = Azot::where('status', 'active')->with('priceTypes');

        // 🔍 Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                ->orWhere('type', 'like', "%$search%")
                ->orWhere('country', 'like', "%$search%")
                ->orWhere('description', 'like', "%$search%");
            });
        }

        // 🧮 Filter
        if ($type) {
            $query->where('type', $type);
        }

        if ($country) {
            $query->where('country', $country);
        }

        // 🔃 Sort
        $query->orderBy($sortBy, $sortOrder);

        return response()->json([
            'success' => true,
            'data' => $query->paginate($perPage),
        ]);
    }


    public function publicShow(Azot $azot)
    {
        if ($azot->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $azot->load('priceTypes')
        ]);
    }

}
