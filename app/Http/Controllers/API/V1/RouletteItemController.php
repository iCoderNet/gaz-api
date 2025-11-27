<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\RouletteItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class RouletteItemController extends Controller
{
    /**
     * Display a listing of roulette items
     */
    public function index(Request $request)
    {
        $query = RouletteItem::with('accessory');

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        // Search by title
        if ($request->has('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'id');
        $sortOrder = $request->input('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $perPage = min($request->input('per_page', 15), 100);
        $items = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $items,
        ]);
    }

    /**
     * Store a newly created roulette item
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'accessory_id' => 'nullable|exists:accessories,id',
            'probability' => 'required|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imagePath = $image->store('roulette', 'public');
            $data['image'] = $imagePath;
        }

        $item = RouletteItem::create($data);
        $item->load('accessory');

        return response()->json([
            'success' => true,
            'message' => 'Roulette item created successfully',
            'data' => $item,
        ], 201);
    }

    /**
     * Display the specified roulette item
     */
    public function show($id)
    {
        $item = RouletteItem::with('accessory')->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Roulette item not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    /**
     * Update the specified roulette item
     */
    public function update(Request $request, $id)
    {
        $item = RouletteItem::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Roulette item not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'accessory_id' => 'nullable|exists:accessories,id',
            'probability' => 'sometimes|required|numeric|min:0|max:100',
            'is_active' => 'boolean',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($item->image) {
                Storage::disk('public')->delete($item->image);
            }

            $image = $request->file('image');
            $imagePath = $image->store('roulette', 'public');
            $data['image'] = $imagePath;
        }

        $item->update($data);
        $item->load('accessory');

        return response()->json([
            'success' => true,
            'message' => 'Roulette item updated successfully',
            'data' => $item,
        ]);
    }

    /**
     * Remove the specified roulette item
     */
    public function destroy($id)
    {
        $item = RouletteItem::find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Roulette item not found',
            ], 404);
        }

        // Delete image if exists
        if ($item->image) {
            Storage::disk('public')->delete($item->image);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'Roulette item deleted successfully',
        ]);
    }
}
