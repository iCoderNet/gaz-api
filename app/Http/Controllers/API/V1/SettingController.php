<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;

class SettingController extends Controller
{
    // 1. GET /settings
    public function index()
    {
        $defaults = [
            'enable_promocode' => false,
            'require_phone_on_order' => true,
            'site_title' => 'My Site',
            'site_logo' => asset('images/default-logo.png'),
            'cargo_price' => 500,
            'bot_token' => "TOKEN",
            'order_notification' => "Новый заказ создан ✅",
            'chat_id' => "ID NUMBER",

        ];

        $settings = Setting::whereIn('key', array_keys($defaults))->pluck('value', 'key')->toArray();

        // Boolean qiymatlarni decode qilish
        foreach (['enable_promocode', 'require_phone_on_order'] as $boolKey) {
            if (isset($settings[$boolKey])) {
                $settings[$boolKey] = json_decode($settings[$boolKey]);
            }
        }

        // Default bilan birlashtirish
        $merged = array_merge($defaults, $settings);

        return response()->json([
            'success' => true,
            'data' => $merged,
        ]);
    }


    // 2. POST /settings/save
    public function save(Request $request)
    {
        $validated = $request->validate([
            'enable_promocode' => 'nullable|boolean',
            'require_phone_on_order' => 'nullable|boolean',
            'site_title' => 'nullable|string|max:255',
            'site_logo' => 'nullable',
            'cargo_price' => 'nullable|numeric|min:0',
            'bot_token' => 'nullable|string',
            'order_notification' => 'nullable|string|max:255',
            'chat_id' => 'nullable|string',
        ]);

        // 🖼️ Rasmni saqlash
        if ($request->hasFile('site_logo')) {
            $path = $request->file('site_logo')->store('logos', 'public');
            $validated['site_logo'] = asset('storage/' . $path);
        } elseif ($request->filled('site_logo')) {
            $validated['site_logo'] = $request->input('site_logo'); // string URL
        }


        // 🔄 Sozlamalarni saqlash
        foreach ($validated as $key => $value) {
            Setting::updateOrCreate(
                ['key' => $key],
                ['value' => is_bool($value) ? json_encode($value) : $value]
            );
        }

        return response()->json(['message' => 'Settings saved successfully']);
    }


    // 3. GET /updates
    public function updates()
    {
        $defaults = [
            'enable_promocode' => false,
            'require_phone_on_order' => true,
            'site_title' => 'My Site',
            'site_logo' => asset('images/default-logo.png'),
            'cargo_price' => 500,
        ];

        $settings = Setting::whereIn('key', array_keys($defaults))->pluck('value', 'key')->toArray();

        // Boolean qiymatlarni decode qilish
        foreach (['enable_promocode', 'require_phone_on_order'] as $boolKey) {
            if (isset($settings[$boolKey])) {
                $settings[$boolKey] = json_decode($settings[$boolKey]);
            }
        }

        // Default bilan birlashtirish
        $merged = array_merge($defaults, $settings);

        return response()->json([
            'success' => true,
            'data' => $merged,
        ]);
    }
}
