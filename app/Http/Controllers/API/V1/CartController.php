<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Accessory;
use App\Models\AdditionalService;
use App\Models\Azot;
use App\Models\AzotPriceType;
use App\Models\User;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'tg_id' => 'required|string',
        ]);

        $userId = User::where('tg_id', $data['tg_id'])->value('id');

        return $this->getCartData($userId);
    }

    private function getCartData($userId)
    {
        $items = Cart::where('user_id', $userId)->get();

        $azots = [];
        $accessories = [];
        $services = [];
        $totalPrice = 0;

        foreach ($items as $item) {
            switch ($item->type) {
                case 'azot':
                    $product = Azot::find($item->product_id);

                    if (!isset($product->status) || $product->status === 'deleted') {
                        // Skip deleted products
                        continue 2; // Skip to the next item in the outer loop
                    }
                    
                    $priceType = AzotPriceType::where('id', $item->price_type_id)
                        ->where('azot_id', $item->product_id)
                        ->first();
                    $priceTypes = AzotPriceType::where('azot_id', $item->product_id)->get();

                    if ($product && $priceType) {
                        $azots[] = [
                            'product_id' => $product->id,
                            'name'       => $product->title,
                            'price_type' => $priceType->name,
                            'price'      => $priceType->price,
                            'price_type_id' => $priceType->id,
                            'quantity'   => $item->quantity,
                            'product'    => $product,
                            'price_types' => $priceTypes
                        ];
                        $totalPrice += $priceType->price * $item->quantity;
                    }
                    break;

                case 'accessuary':
                    $product = Accessory::find($item->product_id);
                    if ($product) {
                        if (!isset($product->status) || $product->status === 'deleted') {
                            // Skip deleted products
                            continue 2; // Skip to the next item in the outer loop
                        }
                        $accessories[] = [
                            'product_id' => $product->id,
                            'name'       => $product->name,
                            'price'      => $product->price,
                            'quantity'   => $item->quantity,
                            'product'    => $product,
                        ];
                        $totalPrice += $product->price * $item->quantity;
                    }
                    break;

                case 'service':
                    $service = AdditionalService::find($item->product_id);
                    if ($service) {
                        if (!isset($service->status) || $service->status === 'deleted') {
                            // Skip deleted products
                            continue 2; // Skip to the next item in the outer loop
                        }
                        $services[] = [
                            'service_id' => $service->id,
                            'name'       => $service->name,
                            'price'      => $service->price,
                            'service'    => $service
                        ];
                        $totalPrice += $service->price;
                    }
                    break;
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'azots'       => $azots,
                'accessories' => $accessories,
                'services'    => $services,
                'total_price' => $totalPrice
            ]
        ]);
    }

    private function addItem($type, $productId, $quantity, $userId, $priceType = null)
    {
        $query = Cart::where('user_id', $userId)
            ->where('type', $type)
            ->where('product_id', $productId);

        if ($type === 'azot') {
            $query->where('price_type_id', $priceType);
        }

        $item = $query->first();

        if ($item) {
            $item->quantity += $quantity;
            $item->save();
        } else {
            Cart::create([
                'user_id'       => $userId,
                'type'          => $type,
                'product_id'    => $productId,
                'quantity'      => $type === 'service' ? 1 : $quantity,
                'price_type_id' => $priceType
            ]);
        }

        return $this->getCartData($userId);
    }

    private function minusItem($type, $productId, $quantity, $userId, $priceType = null)
    {
        $query = Cart::where('user_id', $userId)
            ->where('type', $type)
            ->where('product_id', $productId);

        if ($type === 'azot') {
            $query->where('price_type_id', $priceType);
        }

        $item = $query->first();

        if ($item) {
            $item->quantity -= $quantity;
            if ($item->quantity <= 0) {
                $item->delete();
            } else {
                $item->save();
            }
        }

        return $this->getCartData($userId);
    }

    public function addAzot(Request $request)
    {
        $data = $request->validate([
            'tg_id'         => 'required|string',
            'product_id'    => 'required|integer',
            'price_type_id' => 'required|integer',
            'quantity'      => 'nullable|integer|min:1'
        ]);

        $userId = User::where('tg_id', $data['tg_id'])->value('id');
        return $this->addItem('azot', $data['product_id'], $data['quantity'] ?? 1, $userId, $data['price_type_id']);
    }

    public function addAccessuary(Request $request)
    {
        $data = $request->validate([
            'tg_id'      => 'required|string',
            'product_id' => 'required|integer',
            'quantity'   => 'nullable|integer|min:1'
        ]);

        $userId = User::where('tg_id', $data['tg_id'])->value('id');
        return $this->addItem('accessuary', $data['product_id'], $data['quantity'] ?? 1, $userId);
    }

    public function addService(Request $request)
    {
        $data = $request->validate([
            'tg_id'      => 'required|string',
            'product_id' => 'required|integer'
        ]);

        $userId = User::where('tg_id', $data['tg_id'])->value('id');
        return $this->addItem('service', $data['product_id'], 1, $userId);
    }

    public function minusAzot(Request $request)
    {
        $data = $request->validate([
            'tg_id'         => 'required|string',
            'product_id'    => 'required|integer',
            'price_type_id' => 'required|integer',
            'quantity'      => 'nullable|integer|min:1'
        ]);

        $userId = User::where('tg_id', $data['tg_id'])->value('id');
        return $this->minusItem('azot', $data['product_id'], $data['quantity'] ?? 1, $userId, $data['price_type_id']);
    }

    public function minusAccessuary(Request $request)
    {
        $data = $request->validate([
            'tg_id'      => 'required|string',
            'product_id' => 'required|integer',
            'quantity'   => 'nullable|integer|min:1'
        ]);

        $userId = User::where('tg_id', $data['tg_id'])->value('id');
        return $this->minusItem('accessuary', $data['product_id'], $data['quantity'] ?? 1, $userId);
    }

    public function minusService(Request $request)
    {
        $data = $request->validate([
            'tg_id'      => 'required|string',
            'product_id' => 'required|integer'
        ]);

        $userId = User::where('tg_id', $data['tg_id'])->value('id');
        return $this->minusItem('service', $data['product_id'], 1, $userId);
    }

    public function clear(Request $request)
    {
        $data = $request->validate([
            'tg_id' => 'required|string',
        ]);

        $userId = User::where('tg_id', $data['tg_id'])->value('id');
        Cart::where('user_id', $userId)->delete();

        return $this->getCartData($userId);
    }
}
