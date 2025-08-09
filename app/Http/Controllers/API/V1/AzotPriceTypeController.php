<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\Azot;
use App\Models\AzotPriceType;
use Illuminate\Http\Request;

class AzotPriceTypeController extends Controller
{
    public function index(Azot $azot)
    {
        if ($azot->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        return response()->json($azot->priceTypes);
    }

    public function store(Request $request, Azot $azot)
    {
        if ($azot->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        $data = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric|min:0',
        ]);

        $priceType = $azot->priceTypes()->create($data);
        return response()->json($priceType, 201);
    }

    public function update(Request $request, Azot $azot, AzotPriceType $type)
    {
        if ($azot->status === 'deleted' || $type->azot_id !== $azot->id) {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        $data = $request->validate([
            'name' => 'sometimes|required|string',
            'price' => 'sometimes|required|numeric|min:0',
        ]);

        $type->update($data);
        return response()->json($type);
    }

    public function destroy(Azot $azot, AzotPriceType $type)
    {
        if ($azot->status === 'deleted' || $type->azot_id !== $azot->id) {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        $type->delete();
        return response()->json(['message' => 'Price type deleted successfully']);
    }

    public function publicIndex(Azot $azot)
    {
        // Faqat active bo'lgan Azot va uning priceTypes
        if ($azot->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $azot->priceTypes
        ]);
    }

}

