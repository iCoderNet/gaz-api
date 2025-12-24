<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\AzotPriceType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoulettePriceTypeController extends Controller
{
    /**
     * List all unique price type names with roulette_allowed status
     */
    public function index()
    {
        // Get unique price type names with their roulette status
        // We group by name and take the roulette_allowed from any matching record
        $priceTypes = AzotPriceType::select('name', 'roulette_allowed')
            ->groupBy('name', 'roulette_allowed')
            ->orderBy('name')
            ->get()
            ->unique('name')
            ->values()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'roulette_allowed' => (bool) $item->roulette_allowed,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $priceTypes,
        ]);
    }

    /**
     * Toggle roulette_allowed for all price types with the given name
     */
    public function update(Request $request, $name)
    {
        $request->validate([
            'roulette_allowed' => 'required|boolean',
        ]);

        // Update all price types with this name
        $affected = AzotPriceType::where('name', $name)
            ->update(['roulette_allowed' => $request->roulette_allowed]);

        if ($affected === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Price type not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Roulette access updated successfully',
            'data' => [
                'name' => $name,
                'roulette_allowed' => (bool) $request->roulette_allowed,
            ],
        ]);
    }
}
