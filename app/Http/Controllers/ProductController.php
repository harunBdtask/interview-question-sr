<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use App\Http\Requests\ProductRequest;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::filter($request)->orderby('id', 'DESC')->paginate(2);
        $variants = Variant::with(['productVariant' => function($query){
            $query->groupBy('variant');
        }])->get();
        return view('products.index', compact('products', 'variants'));
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

    public function store(ProductRequest $request)
    {
        $product  = Product::updateOrCreate(['id' => $request->id ], $request->validated());
        $variants = [];
        foreach($request->product_variant as $product_variant){
            foreach($product_variant['tags'] as $tag) {
                $variants[] = [
                    'product_id' => $product->id,
                    'variant_id' => $product_variant['option'], 
                    'variant' => $tag,
                ];
            }
        }
        ProductVariant::insert($variants);
        $varient_prices   = [];
        $product_variants = ProductVariant::where('product_id', $product->id)->get();
        foreach($request->product_variant_prices as $product_variant){
            $price_variants    = explode('/', $product_variant['title']);
            $product_variant_1 = !empty($price_variants[0]) ? $product_variants->where('variant', $price_variants[0])->first()->id : null;
            $product_variant_2 = !empty($price_variants[1]) ? $product_variants->where('variant', $price_variants[1])->first()->id : null;
            $product_variant_3 = !empty($price_variants[2]) ? $product_variants->where('variant', $price_variants[2])->first()->id : null;
            $varient_prices[] = [
                'product_variant_one' => $product_variant_1,
                'product_variant_two' => $product_variant_2,
                'product_variant_three' => $product_variant_3,
                'price' => $product_variant['price'],
                'stock' => $product_variant['stock'],
                'product_id' => $product->id,
            ];
        }
        $prices = ProductVariantPrice::insert($varient_prices);
        if ($prices) {
            return response()->json([ 'success' => true, 'message' => __('Prdouct added successfully') ]);
        } else {
            return response()->json([ 'error' => true, 'message' => __('Prdouct couldn\'t be added') ]);
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

    public function storeProductImage(Request $request)
    {
        if($request->has('file')) {
            $path = $request->file->store('proucts');
        }
        $product = Product::firstOrCreate(['id' => $request->id], ['title'=> '', 'sku'=> uniqid()]);
        ProductImage::create([
            'product_id' => $product->id,
            'file_path'  => $path,
        ]);
        return response()->json($product);
    }

    public function edit(Product $product)
    {
        $variants      = Variant::all();
        $variantPrices = [];
        foreach($product->variantPrices as $variantPrice){
            $variantPrices[] = [ 
                'price' => $variantPrice->price, 
                'stock' => $variantPrice->stock,
                'title' => $product->generateVariant($variantPrice),
            ];
        }
        $product['variants'] = $product->variants;
        $product['prices']   = $variantPrices;
        return view('products.edit', compact('variants', 'product'));
    }

    public function update(ProductRequest $request, Product $product)
    {
        $product->update($request->validated());
        $product->variants()->detach();
        $variants = [];
        foreach($request->product_variant as $product_variant){
            foreach($product_variant['tags'] as $tag) {
                $variants[] = [
                    'product_id' => $product->id,
                    'variant_id' => $product_variant['option'],
                    'variant' => $tag,
                ];
            }
        }
        ProductVariant::insert($variants);
        $varient_prices   = [];
        $product_variants = ProductVariant::where('product_id', $product->id)->get();
        foreach($request->product_variant_prices as $product_variant){
            $price_variants    = explode('/', $product_variant['title']);
            $product_variant_one = ! empty($price_variants[0]) ? $product_variants->where('variant', $price_variants[0])->first()->id : null;
            $product_variant_two = ! empty($price_variants[1]) ? $product_variants->where('variant', $price_variants[1])->first()->id : null;
            $product_variant_three = ! empty($price_variants[2]) ? $product_variants->where('variant', $price_variants[2])->first()->id : null;
            $varient_prices[]  = [
                'product_variant_one' => $product_variant_one,
                'product_variant_two' => $product_variant_two,
                'product_variant_three' => $product_variant_three,
                'price' => $product_variant['price'],
                'stock' => $product_variant['stock'],
                'product_id' => $product->id,
            ];
        }
        $prices = ProductVariantPrice::insert($varient_prices);
        if ($prices) {
            return response()->json([ 'success' => true, 'message' => __('Prdouct updated successfully') ]);
        } else {
            return response()->json([ 'error' => true, 'message' => __('Prdouct couldn\'t be updated') ]);
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
