<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Promocode;

class PromocodeController extends Controller
{
    public function index()
    {
        $validator = Validator::make(request()->all(), [
            'per_page'   => 'integer|min:1|max:100',
            'search'     => 'string|nullable',
            'status'     => 'in:active,archive',
            'type'       => 'in:countable,fixed-term',
            'sort_by'    => 'in:id,promocode,amount,start_date,end_date,used_count,created_at',
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
        $type      = request()->get('type');
        $sortBy    = request()->get('sort_by', 'id');
        $sortOrder = request()->get('sort_order', 'desc');

        $query = Promocode::where('status', '!=', 'deleted');

        // 🔍 Search
        if ($search) {
            $query->where('promocode', 'like', "%$search%");
        }

        // 🧮 Filter
        if ($status) {
            $query->where('status', $status);
        }

        if ($type) {
            $query->where('type', $type);
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
            'promocode'   => 'required|string|unique:promocodes,promocode',
            'amount'      => 'required|numeric|min:0',
            'status'      => ['nullable', Rule::in(['active', 'archive'])],
            'type'        => ['required', Rule::in(['countable', 'fixed-term'])],
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'countable'   => 'nullable|integer|min:0',
        ]);

        $promocode = Promocode::create($data);
        return response()->json($promocode, 201);
    }

    public function show(Promocode $promocode)
    {
        if ($promocode->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        return response()->json($promocode);
    }

    public function update(Request $request, Promocode $promocode)
    {
        if ($promocode->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        $data = $request->validate([
            'promocode'   => ['nullable', 'string', Rule::unique('promocodes')->ignore($promocode->id)],
            'amount'      => 'nullable|numeric|min:0',
            'status'      => ['nullable', Rule::in(['active', 'archive'])],
            'type'        => ['nullable', Rule::in(['countable', 'fixed-term'])],
            'start_date'  => 'nullable|date',
            'end_date'    => 'nullable|date|after_or_equal:start_date',
            'countable'   => 'nullable|integer|min:0',
        ]);

        $promocode->update($data);
        return response()->json($promocode);
    }

    public function destroy(Promocode $promocode)
    {
        if ($promocode->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        $promocode->update(['status' => 'deleted']);
        return response()->json(['message' => 'Promocode deleted successfully']);
    }

    public function check(Request $request)
    {
        $data = $request->validate([
            'promocode' => 'required|string',
        ]);

        $promo = Promocode::whereRaw('BINARY promocode = ?', [$data['promocode']])
            ->where('status', 'active')
            ->first();

        if (!$promo) {
            return response()->json([
                'success' => false,
                'message' => 'Promocode not found or inactive'
            ], 404);
        }

        // ✅ Agar type countable bo‘lsa
        if ($promo->type === 'countable') {
            if ($promo->countable <= $promo->used_count) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promocode usage limit exceeded'
                ], 400);
            }
        }

        // ✅ Agar type fixed-term bo‘lsa
        if ($promo->type === 'fixed-term') {
            $now = now();
            if (
                ($promo->start_date && $now->lt($promo->start_date)) ||
                ($promo->end_date && $now->gt($promo->end_date))
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Promocode expired or not yet valid'
                ], 400);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Promocode is valid',
            'data' => [
                'id' => $promo->id,
                'promocode' => $promo->promocode,
                'amount' => $promo->amount,
                'type' => $promo->type,
            ]
        ]);
    }

}
