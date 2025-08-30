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
use Illuminate\Support\Facades\Log;

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
            'data' => $query->with(['azots.azot.priceTypes', 'accessories.accessory', 'services.service', 'user'])->paginate($perPage),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id'      => 'required|exists:users,id',
            'promocode_id' => 'nullable|exists:promocodes,id',
            'payment_type' => 'nullable|string|max:255',

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
                'payment_type'=> $data['payment_type'] ?? null,
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

            return response()->json($order->load(['azots.azot.priceTypes', 'accessories.accessory', 'services.service', 'promocode', 'user']), 201);
        });
    }

    public function show($id)
    {
        $order = Order::where('id', $id)
              ->where('status', '!=', 'deleted')
              ->with(['azots.azot.priceTypes', 'accessories.accessory', 'services.service', 'promocode', 'user'])
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
            'payment_type' => 'nullable|string|max:255',
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
            ->where('is_hidden_for_user', false) 
            ->with(['azots.azot.priceTypes', 'accessories.accessory', 'services.service', 'promocode', 'user'])
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
            'promocode'  => 'nullable|exists:promocodes,promocode',
            'payment_type' => 'nullable|string|max:255',
        ]);

        $userId = User::where('tg_id', $data['tg_id'])->value('id');

        return DB::transaction(function () use ($data, $userId) {
            // Cart itemlarni lock bilan olish (race condition oldini olish uchun)
            $cartItems = Cart::where('user_id', $userId)->lockForUpdate()->get();

            if ($cartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cart is empty',
                ], 400);
            }

            // Cart itemlarni pre-validation qilish
            $validCartItems = collect();
            $errors = [];

            foreach ($cartItems as $item) {
                $isValid = true;
                $errorMsg = null;

                if ($item->type === 'azot') {
                    // Azot mavjudligini tekshirish
                    $azot = Azot::where('id', $item->product_id)->where('status', 'active')->first();
                    if (!$azot) {
                        $isValid = false;
                        $errorMsg = "Azot ID {$item->product_id} not found or inactive";
                    }

                    // Price type mavjudligini tekshirish
                    if ($isValid) {
                        $priceType = AzotPriceType::where('id', $item->price_type_id)
                                                ->where('azot_id', $item->product_id)
                                                ->first();
                        if (!$priceType) {
                            $isValid = false;
                            $errorMsg = "Price type ID {$item->price_type_id} not found for azot {$item->product_id}";
                        }
                    }
                } elseif ($item->type === 'accessuary') {
                    $accessory = Accessory::where('id', $item->product_id)->where('status', 'active')->first();
                    if (!$accessory) {
                        $isValid = false;
                        $errorMsg = "Accessory ID {$item->product_id} not found or inactive";
                    }
                } elseif ($item->type === 'service') {
                    $service = AdditionalService::where('id', $item->product_id)->where('status', 'active')->first();
                    if (!$service) {
                        $isValid = false;
                        $errorMsg = "Service ID {$item->product_id} not found or inactive";
                    }
                }

                if ($isValid) {
                    $validCartItems->push($item);
                } else {
                    $errors[] = $errorMsg;
                    // Noto'g'ri cart itemni o'chirish
                    $item->delete();
                }
            }

            // Agar hech qanday valid item yo'q bo'lsa
            if ($validCartItems->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid items in cart',
                    'errors' => $errors,
                ], 400);
            }

            // Agar ba'zi itemlar o'chirilgan bo'lsa, foydalanuvchiga xabar berish
            if (!empty($errors)) {
                Log::warning('Some cart items were invalid and removed:', $errors);
            }

            // Promocode hisoblash
            $promocodeId = null;
            $promoDiscount = 0;
            $promoStatus = 'not_found';
            if (!empty($data['promocode'])) {
                $promo = Promocode::where('promocode', $data['promocode'])->first();
                $promocodeId = $promo ? $promo->id : null;
                $promoStatus = 'exist';
                if ($promo && $promo->status === 'active') {
                    $now = now();
                    if ($promo->type === 'countable') {
                        $maxUsable = $promo->countable ?? 0;
                        $used = $promo->used_count ?? 0;
                        if ($maxUsable > 0 && $used < $maxUsable) {
                            $promoDiscount += is_numeric($promo->amount) ? $promo->amount : 0;
                            $promo->increment('used_count');
                            $promoStatus = 'active';
                        } else {
                            $promoStatus = 'limit_reached';
                        }
                    }
                    if ($promo->type === 'fixed-term') {
                        $startOk = !$promo->start_date || $now->gte($promo->start_date);
                        $endOk   = !$promo->end_date   || $now->lte($promo->end_date);
                        if ($startOk && $endOk) {
                            $promoDiscount += is_numeric($promo->amount) ? $promo->amount : 0;
                            $promoStatus = 'active';
                        } else {
                            $promoStatus = 'expired';
                        }
                    }
                }
            }

            // Order yaratish
            $order = Order::create([
                'user_id'      => $userId,
                'promocode_id' => $promocodeId,
                'promo_status' => $promoStatus,
                'payment_type' => $data['payment_type'] ?? null,
                'status'       => 'new',
                'promo_price'  => $promoDiscount,
                'all_price'    => 0,
                'total_price'  => 0,
            ]);

            // Faqat valid cart itemlarni orderga yozish
            $allPrice = 0;
            foreach ($validCartItems as $item) {
                if ($item->type === 'azot') {
                    $priceType = AzotPriceType::where('id', $item->price_type_id)
                                            ->where('azot_id', $item->product_id)
                                            ->first();
                    $price = $priceType->price;
                    $total = $price * $item->quantity;

                    OrderAzot::create([
                        'order_id' => $order->id,
                        'azot_id'  => $item->product_id,
                        'price_type_id' => $item->price_type_id,
                        'count'    => $item->quantity,
                        'price'    => $price,
                        'total_price' => $total,
                    ]);
                    $allPrice += $total;
                }

                if ($item->type === 'accessuary') {
                    $accessory = Accessory::find($item->product_id);
                    $price = $accessory->price;
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
                    $service = AdditionalService::find($item->product_id);
                    $price = $service->price;
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

            $order->update([
                'all_price'   => $allPrice,
                'total_price' => $allPrice - $promoDiscount,
            ]);

            // Cartni tozalash
            Cart::where('user_id', $order->user_id)->delete();

            $responseData = [
                'success' => true,
                'message' => 'Order created. Use /orders/finish to complete it.',
                'data'    => $order->load(['azots.azot.priceTypes', 'accessories.accessory', 'services.service', 'promocode', 'user']),
            ];

            // Agar ba'zi itemlar o'chirilgan bo'lsa, warning qo'shish
            if (!empty($errors)) {
                $responseData['warnings'] = [
                    'Some invalid items were removed from cart',
                    'errors' => $errors
                ];
            }

            return response()->json($responseData, 201);
        });
    }

    public function finish(Request $request, Order $order)
    {
        $data = $request->validate([
            'phone'       => 'nullable|string|max:255',
            'address'     => 'nullable|string',
            'comment'     => 'nullable|string',
            'cargo_with'  => 'nullable|boolean',
            'payment_type' => 'nullable|string|max:255',
            'service_ids' => 'nullable|array',
            'service_ids.*' => 'exists:additional_services,id',
        ]);

        if ($order->status !== 'new') {
            return response()->json([
                'success' => false,
                'message' => 'Order already finished or invalid status',
            ], 400);
        }

        return DB::transaction(function () use ($data, $order) {
            $cargoPrice = !empty($data['cargo_with']) ? Setting::get('cargo_price', 500) : 0;

            // Yangi servicelarni qo'shish
            $additionalPrice = 0;
            if (!empty($data['service_ids'])) {
                foreach ($data['service_ids'] as $serviceId) {
                    $service = AdditionalService::findOrFail($serviceId);
                    $price = $service->price;
                    
                    OrderService::create([
                        'order_id' => $order->id,
                        'additional_service_id' => $serviceId,
                        'count' => 1, // count doim 1
                        'price' => $price,
                        'total_price' => $price,
                    ]);
                    
                    $additionalPrice += $price;
                }
            }

            // Yangi all_price ni hisoblash
            $newAllPrice = $order->all_price + $additionalPrice;

            // PROMOCODEni qayta hisoblash
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

            $order->update([
                'phone'       => $data['phone'] ?? null,
                'address'     => $data['address'] ?? null,
                'comment'     => $data['comment'] ?? null,
                'payment_type' => $data['payment_type'] ?? $order->payment_type,
                'cargo_price' => $cargoPrice,
                'promo_price' => $promoDiscount,
                'all_price'   => $newAllPrice, // yangilangan all_price
                'total_price' => max($newAllPrice + $cargoPrice - $promoDiscount, 0),
                'status'      => 'pending',
            ]);

            SendOrderNotificationJob::dispatch($order->id, $order->user_id, $data);

            return response()->json([
                'success' => true,
                'message' => 'Order finished and set to pending.',
                'data'    => $order->fresh(['azots.azot.priceTypes', 'accessories.accessory', 'services.service', 'promocode', 'user']),
            ]);
        });
    }

    public function delete(Request $request, Order $order)
    {
        $data = $request->validate([
            'tg_id' => 'required|string|exists:users,tg_id',
        ]);

        $user = User::where('tg_id', $data['tg_id'])->first();

        if (!$user || $order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized action',
            ], 403);
        }

        // Agar order new bo‘lsa → to‘liq delete (admin va userdan)
        if ($order->status === 'new') {
            $order->update(['status' => 'deleted']);
            return response()->json([
                'success' => true,
                'message' => 'Order fully deleted',
            ]);
        }

        // Agar order oformlenie qilingan bo‘lsa → faqat userdan yashirish
        $order->update(['is_hidden_for_user' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Order hidden for user only',
        ]);
    }

}