<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CrossSellController extends Controller
{
    public function show()
    {
        // خواندن داده‌ها از جدول order_items
        $orderItems = DB::table('order_items')->select('order_id', 'product_id')->get();

        // پردازش داده‌ها برای استخراج ترکیب‌های محصولات
        $productPairs = [];
        foreach ($orderItems as $orderItem) {
            $pair = $orderItem->product_id;
            // پردازش برای بدست آوردن ترکیب‌ها
            $productPairs[] = $pair;
        }

        // انجام پردازش برای تعداد تکرار هر ترکیب
        $productCounts = array_count_values($productPairs);

        // ارسال داده‌ها به ویو
        return view('cross_sell', ['productCounts' => $productCounts]);
    }
}
