<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $variant = Variant::with('product_variant')->get();
        $products = Product::with(['product_variation_price.product_variant_one_data', 'product_variation_price.product_variant_two_data', 'product_variation_price.product_variant_three_data'])
            ->when(isset($request->title), function ($query) use ($request) {
                $query->where('title', 'LIKE', '%' . $request->title . '%');
            })
            ->when(isset($request->date), function ($query) use ($request) {
                $query->whereDate('created_at', $request->date);
            })
            ->whereHas('product_variation_price', function (Builder $query) use ($request) {
                if (isset($request->price_from) && isset($request->price_to)) {
                    $query->whereBetween('product_variant_prices.price', [$request->price_from, $request->price_to]);
                }

                if (isset($request->variant)) {
                    $query->where('product_variant_prices.product_variant_one', $request->variant)
                        ->orWhere('product_variant_prices.product_variant_two', $request->variant)
                        ->orWhere('product_variant_prices.product_variant_three', $request->variant);

                }
            })
            ->paginate(10);

        return view('products.index', compact('products', 'variant'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();
            //Product
            $product = new Product;
            $request->validate($product->validation());
            $product->fill($request->only(['title', 'sku', 'description']))->save();

            //Product Variation
            $product_variant = array();
            foreach ($request->product_variant as $key => $variant) {
                foreach ($variant['tags'] as $tag) {
                    $product_variant [] = [
                        'variant' => $tag,
                        'variant_id' => $variant['option'],
                        'product_id' => $product->id,
                        'created_at' => date('Y-m-y H:m:s'),
                        'updated_at' => date('Y-m-y H:m:s')
                    ];
                }
            }
            ProductVariant::insert($product_variant);

            //Product Variation Prices
            $product_variant = ProductVariant::all();
            $product_variant_price = array();

            foreach ($request->product_variant_prices as $product_variant_prices) {
                $explode = explode("/", $product_variant_prices['title']);
                $product_variant_one = collect($product_variant)->where('variant', $explode[0] ?? null)
                    ->first();
                $product_variant_two = collect($product_variant)->where('variant', $explode[1] ?? null)
                    ->first();
                $product_variant_three = collect($product_variant)->where('variant', $explode[2] ?? null)
                    ->first();

                $product_variant_price [] = [
                    'product_variant_one' => $product_variant_one->id,
                    'product_variant_two' => $product_variant_two->id,
                    'product_variant_three' => $product_variant_three->id,
                    'price' => $product_variant_prices['price'],
                    'stock' => $product_variant_prices['stock'],
                    'product_id' => $product->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
            }

            ProductVariantPrice::insert($product_variant_price);
            DB::commit();
            return response()->json(['msg' => 'Product Added Successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['msg' => $e->getMessage()], 505);
        }
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $variants = Variant::all();
        $product = Product::with(['product_variation_price.product_variant_one_data', 'product_variation_price.product_variant_two_data', 'product_variation_price.product_variant_three_data'])
            ->where('id', $id)
            ->first();

        $product_variant_prices = $product->product_variation_price->map(function ($variation) {
            $title_one = $variation->product_variant_one_data ? $variation->product_variant_one_data->variant . '/' : '';
            $title_two = $variation->product_variant_two_data ? $variation->product_variant_two_data->variant . '/' : '';
            $title_three = $variation->product_variant_three_data ? $variation->product_variant_three_data->variant . '/' : '';
            $title = $title_one . $title_two . $title_three;
            return [
                'title' => $title,
                'price' => $variation->price,
                'stock' => $variation->stock,
            ];
        });

        $variants = Variant::with('product_variant')->get();

        $product_variants = $variants->map(function ($var) use ($id) {
            $tags = collect($var->product_variant)->where('variant_id',  $var->id)
                                                    ->where('product_id',  $id)
                                                    ->pluck('variant')->unique()->toArray();
            return [
                'option' => $var->id,
                'tags' => $tags,
            ];
        });

        return view('products.edit', compact('variants', 'product', 'product_variant_prices', 'product_variants'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $product_variant_id = ProductVariant::where('product_id', $id)->get()->pluck('id')->toArray();
        $product_variant_price_id = ProductVariantPrice::where('product_id', $id)->get()->pluck('id')->toArray();

        try {
            DB::beginTransaction();
            //Product
            $product = Product::findOrFail($id);
            $request->validate($product->validation());
            $product->fill($request->only(['title', 'sku', 'description']))->save();

            //Product Variation
            $product_variant = array();
            foreach ($request->product_variant as $key => $variant) {
                foreach ($variant['tags'] as $tag) {
                    $product_variant [] = [
                        'variant' => $tag,
                        'variant_id' => $variant['option'],
                        'product_id' => $product->id,
                        'created_at' => date('Y-m-y H:m:s'),
                        'updated_at' => date('Y-m-y H:m:s')
                    ];
                }
            }
            ProductVariant::whereIn('id', $product_variant_id)->delete();
            ProductVariant::insert($product_variant);

            //Product Variation Prices
            $product_variant = ProductVariant::all();
            $product_variant_price = array();

            foreach ($request->product_variant_prices as $product_variant_prices) {
                $explode = explode("/", $product_variant_prices['title']);
                $product_variant_one = collect($product_variant)->where('variant', $explode[0] ?? null)
                    ->first();
                $product_variant_two = collect($product_variant)->where('variant', $explode[1] ?? null)
                    ->first();
                $product_variant_three = collect($product_variant)->where('variant', $explode[2] ?? null)
                    ->first();

                $product_variant_price [] = [
                    'product_variant_one' => $product_variant_one->id ?? null,
                    'product_variant_two' => $product_variant_two->id ?? null,
                    'product_variant_three' => $product_variant_three->id ?? null,
                    'price' => $product_variant_prices['price'],
                    'stock' => $product_variant_prices['stock'],
                    'product_id' => $product->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now()
                ];
            }
            ProductVariantPrice::whereIn('id', $product_variant_price_id)->delete();
            ProductVariantPrice::insert($product_variant_price);

            DB::commit();
            return response()->json(['msg' => 'Product Added Successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['msg' => $e->getMessage()], 505);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
