<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Jobs\SendOrderNotificationJob;
use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\Models\Order;
use App\Models\OrderAzot;
use App\Models\OrderAccessory;
use App\Models\OrderService;
use App\Models\AzotPriceType;
use App\Models\Accessory;
use App\Models\AdditionalService;
use App\Models\Azot;
use App\Models\Cart;
use App\Models\Promocode;
use App\Models\Setting;
use App\Models\User;
use DB;

class OrderController extends Controller
{
    public function index()
    {
        $validator = Validator::make(request()->all(), [
            'per_page'   => 'integer|min:1|max:100',
            'search'     => 'string|nullable',
            'status'     => 'in:new,pending,accepted,rejected,completed',
            'sort_by'    => 'in:id,all_price,total_price,status,created_at',
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

        $query = Order::where('status', '!=', 'deleted');

        if ($search) {
            $query->where('id', $search)
                  ->orWhereHas('user', function ($q) use ($search) {
                      $q->where('tg_id', 'like', "%$search%")
                        ->orWhere('phone', 'like', "%$search%")
                        ->orWhere('username', 'like', "%$search%");
                  });
        }

        if ($status) {
            $query->where('status', $status);
        }

        $query->orderBy($sortBy, $sortOrder);

        return response()->json([
            'success' => true,
            'data' => $query->with(['azots', 'accessories', 'services', 'user'])->paginate($perPage),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'      => 'required|exists:users,id',
            'promocode_id' => 'nullable|exists:promocodes,id',

            'phone'        => 'nullable|string|max:255',
            'address'      => 'nullable|string',
            'comment'      => 'nullable|string',
            'cargo_price'  => 'nullable|numeric|min:0',

            'azots'        => 'array',
            'azots.*.id'   => 'required|exists:azots,id',
            'azots.*.type_id' => 'required|exists:azot_price_types,id',
            'azots.*.count'=> 'required|integer|min:1',

            'accessories'  => 'array',
            'accessories.*.id' => 'required|exists:accessories,id',
            'accessories.*.count'=> 'required|integer|min:1',

            'services'     => 'array',
            'services.*.id' => 'required|exists:additional_services,id',
            'services.*.count'=> 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($data) {
            $order = Order::create([
                'user_id'     => $data['user_id'],
                'promocode_id'=> $data['promocode_id'] ?? null,
                'phone'       => $data['phone'] ?? null,
                'address'     => $data['address'] ?? null,
                'comment'     => $data['comment'] ?? null,
                'cargo_price' => $data['cargo_price'] ?? 0,
                'promo_price' => 0,
                'all_price'   => 0,
                'total_price' => 0,
                'status'      => 'new',
            ]);

            $allPrice = 0;

            // AZOTS
            if (!empty($data['azots'])) {
                foreach ($data['azots'] as $azotData) {
                    $priceType = AzotPriceType::findOrFail($azotData['type_id']);
                    $price = $priceType->price;
                    $total = $price * $azotData['count'];

                    OrderAzot::create([
                        'order_id' => $order->id,
                        'azot_id'  => $azotData['id'],
                        'count'    => $azotData['count'],
                        'price'    => $price,
                        'total_price' => $total,
                    ]);

                    $allPrice += $total;
                }
            }

            // ACCESSORIES
            if (!empty($data['accessories'])) {
                foreach ($data['accessories'] as $accData) {
                    $price = Accessory::findOrFail($accData['id'])->price;
                    $total = $price * $accData['count'];

                    OrderAccessory::create([
                        'order_id' => $order->id,
                        'accessory_id' => $accData['id'],
                        'count' => $accData['count'],
                        'price' => $price,
                        'total_price' => $total,
                    ]);

                    $allPrice += $total;
                }
            }

            // SERVICES
            if (!empty($data['services'])) {
                foreach ($data['services'] as $srvData) {
                    $price = AdditionalService::findOrFail($srvData['id'])->price;
                    $total = $price * $srvData['count'];

                    OrderService::create([
                        'order_id' => $order->id,
                        'additional_service_id' => $srvData['id'],
                        'count' => $srvData['count'],
                        'price' => $price,
                        'total_price' => $total,
                    ]);

                    $allPrice += $total;
                }
            }

            // PROMOCODE hisoblash
            $promoDiscount = 0;
            if ($order->promocode_id) {
                $promo = Promocode::find($order->promocode_id);
                if ($promo && $promo->status === 'active') {
                    if ($promo->type === 'countable') {
                        // kelajakdagi hisoblash
                    } elseif ($promo->type === 'fixed-term') {
                        // kelajakdagi hisoblash
                    }
                    $promoDiscount += $promo->amount;
                }
            }

            // Yakuniy hisoblash (cargo_price ham qo'shiladi)
            $order->update([
                'all_price'   => $allPrice,
                'promo_price' => $promoDiscount,
                'total_price' => max($allPrice + ($data['cargo_price'] ?? 0) - $promoDiscount, 0),
            ]);

            return response()->json($order->load(['azots', 'accessories', 'services', 'promocode', 'user']), 201);
        });
    }

    public function show($id)
    {
        $order = Order::where('id', $id)
              ->where('status', '!=', 'deleted')
              ->with(['azots', 'accessories', 'services', 'promocode', 'user'])
              ->first();

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $order,
        ]);
    }

    public function update(Request $request, $id)
    {
        $order = Order::where('id', $id)
              ->where('status', '!=', 'deleted')
              ->first();

        if ($order->status === 'deleted') {
            return response()->json([
                'success' => false,
                'message' => 'Not found'
            ], 404);
        }

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $data = $request->validate([
            'status'       => ['nullable', Rule::in(['new','pending','accepted','rejected','completed'])],
        ]);

        $order->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Order updated successfully',
            'data'    => $order,
        ]);
    }


    public function destroy($id)
    {
        $order = Order::where('id', $id)
              ->where('status', '!=', 'deleted')
              ->first();


        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found',
            ], 404);
        }

        $order->update(['status' => 'deleted']);

        return response()->json([
            'success' => true,
            'message' => 'Order deleted successfully',
        ]);
    }

    public function publicOrder(Request $request)
    {
        $data = $request->validate([
            'tg_id' => 'required|string',
        ]);

        $orders = Order::whereHas('user', function ($q) use ($data) {
                $q->where('tg_id', $data['tg_id']);
            })
            ->where('status', '!=', 'deleted')
            ->with(['azots', 'accessories', 'services'])
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ]);
    }

    public function publicStore(Request $request)
    {
        $data = $request->validate([
            'tg_id'      => 'required|exists:users,tg_id',
            'promocode' => 'nullable|exists:promocodes,promocode',
            'phone'        => 'nullable|string|max:255',
            'address'      => 'nullable|string',
            'comment'      => 'nullable|string',
            'cargo_with'  => 'nullable|boolean',
        ]);

        $userId = User::where('tg_id', $data['tg_id'])->value('id');

        return DB::transaction(function () use ($data, $userId) {
            $cartItems = Cart::where('user_id', $userId)->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty',
                ], 400);
            }

            $cargoPrice = !empty($data['cargo_with']) ? Setting::get('cargo_price', 500) : 0;


            $order = Order::create([
                'user_id'     => $userId,
                'promocode_id'=> $data['promocode_id'] ?? null,
                'phone'       => $data['phone'] ?? null,
                'address'     => $data['address'] ?? null,
                'comment'     => $data['comment'] ?? null,
                'cargo_price' => $cargoPrice,
                'promo_price' => 0,
                'all_price'   => 0,
                'total_price' => 0,
                'status'      => 'new',
            ]);

            $allPrice = 0;

            foreach ($cartItems as $item) {
                if ($item->type === 'azot') {
                    $priceType = AzotPriceType::findOrFail($item->price_type_id);
                    $price = $priceType->price;
                    $total = $price * $item->quantity;

                    OrderAzot::create([
                        'order_id' => $order->id,
                        'azot_id'  => $item->product_id,
                        'count'    => $item->quantity,
                        'price'    => $price,
                        'total_price' => $total,
                    ]);

                    $allPrice += $total;
                }

                if ($item->type === 'accessuary') {
                    $price = Accessory::findOrFail($item->product_id)->price;
                    $total = $price * $item->quantity;

                    OrderAccessory::create([
                        'order_id' => $order->id,
                        'accessory_id' => $item->product_id,
                        'count' => $item->quantity,
                        'price' => $price,
                        'total_price' => $total,
                    ]);

                    $allPrice += $total;
                }

                if ($item->type === 'service') {
                    $price = AdditionalService::findOrFail($item->product_id)->price;
                    $total = $price * $item->quantity;

                    OrderService::create([
                        'order_id' => $order->id,
                        'additional_service_id' => $item->product_id,
                        'count' => $item->quantity,
                        'price' => $price,
                        'total_price' => $total,
                    ]);

                    $allPrice += $total;
                }
            }

            // PROMOCODE hisoblash
            $promoDiscount = 0;
            if ($order->promocode_id) {
                $promo = Promocode::find($order->promocode_id);

                if ($promo && $promo->status === 'active') {
                    $now = now();

                    if ($promo->type === 'countable') {
                        $maxUsable = $promo->countable ?? 0;
                        $used = $promo->used_count ?? 0;

                        if ($maxUsable > 0 && $used < $maxUsable) {
                            $promoDiscount += is_numeric($promo->amount) ? $promo->amount : 0;
                            $promo->increment('used_count');
                        }
                    }

                    if ($promo->type === 'fixed-term') {
                        $startOk = !$promo->start_date || $now->gte($promo->start_date);
                        $endOk   = !$promo->end_date   || $now->lte($promo->end_date);

                        if ($startOk && $endOk) {
                            $promoDiscount += is_numeric($promo->amount) ? $promo->amount : 0;
                        }
                    }
                }
            }


            // Yakuniy hisob
            $order->update([
                'all_price'   => $allPrice,
                'promo_price' => $promoDiscount,
                'total_price' => max($allPrice + ($cargoPrice ?? 0) - $promoDiscount, 0),
            ]);

            SendOrderNotificationJob::dispatch($order->id, $userId, $data);

            // Cartni tozalash
            Cart::where('user_id', $userId)->delete();

            return response()->json([
                'success' => true,
                'data'    => $order->load(['azots', 'accessories', 'services', 'promocode', 'user']),
            ], 201);
        });
    }

}
