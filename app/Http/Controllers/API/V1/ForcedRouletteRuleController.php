<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\ForcedRouletteRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ForcedRouletteRuleController extends Controller
{
    /**
     * Display a listing of forced roulette rules
     */
    public function index(Request $request)
    {
        $query = ForcedRouletteRule::with(['azot', 'rouletteItem']);

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = min($request->input('per_page', 15), 100);
        $rules = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $rules,
        ]);
    }

    /**
     * Store a newly created forced roulette rule
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'azot_id' => 'required|exists:azots,id',
            'price_type_name' => 'required|string|exists:azot_price_types,name',
            'roulette_item_id' => 'required|exists:roulette_items,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Check for duplicate rule
        $existing = ForcedRouletteRule::where('azot_id', $request->azot_id)
            ->where('price_type_name', $request->price_type_name)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'A rule for this azot and payment type already exists',
            ], 422);
        }

        $rule = ForcedRouletteRule::create($validator->validated());
        $rule->load(['azot', 'rouletteItem']);

        return response()->json([
            'success' => true,
            'message' => 'Forced roulette rule created successfully',
            'data' => $rule,
        ], 201);
    }

    /**
     * Display the specified forced roulette rule
     */
    public function show($id)
    {
        $rule = ForcedRouletteRule::with(['azot', 'rouletteItem'])->find($id);

        if (!$rule) {
            return response()->json([
                'success' => false,
                'message' => 'Forced roulette rule not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $rule,
        ]);
    }

    /**
     * Update the specified forced roulette rule
     */
    public function update(Request $request, $id)
    {
        $rule = ForcedRouletteRule::find($id);

        if (!$rule) {
            return response()->json([
                'success' => false,
                'message' => 'Forced roulette rule not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'azot_id' => 'sometimes|required|exists:azots,id',
            'price_type_name' => 'sometimes|required|string|exists:azot_price_types,name',
            'roulette_item_id' => 'sometimes|required|exists:roulette_items,id',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $rule->update($validator->validated());
        $rule->load(['azot', 'rouletteItem']);

        return response()->json([
            'success' => true,
            'message' => 'Forced roulette rule updated successfully',
            'data' => $rule,
        ]);
    }

    /**
     * Remove the specified forced roulette rule
     */
    public function destroy($id)
    {
        $rule = ForcedRouletteRule::find($id);

        if (!$rule) {
            return response()->json([
                'success' => false,
                'message' => 'Forced roulette rule not found',
            ], 404);
        }

        $rule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Forced roulette rule deleted successfully',
        ]);
    }

    /**
     * Get all available price type names (for forced roulette rules dropdown)
     */
    public function getPriceTypeNames()
    {
        $priceTypeNames = \App\Models\AzotPriceType::select('name')
            ->distinct()
            ->orderBy('name')
            ->pluck('name');

        return response()->json([
            'success' => true,
            'data' => $priceTypeNames,
        ]);
    }
}
