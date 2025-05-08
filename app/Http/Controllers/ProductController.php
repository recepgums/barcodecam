<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        if ($request->title) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->barcode) {
            $query->where('barcode', 'like', '%' . $request->barcode . '%');
        }

        if ($request->min_price) {
            $query->where('price', '>=', $request->min_price);
        }

        if ($request->max_price) {
            $query->where('price', '<=', $request->max_price);
        }

        if ($request->stock_status) {
            if ($request->stock_status === 'in_stock') {
                $query->where('quantity', '>', 0);
            } else {
                $query->where('quantity', '=', 0);
            }
        }

        $products = $query->with('store')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('products.index', compact('products'));
    }
}
