<?php

namespace App\Http\Controllers;

use App\Cart;
use App\Category;
use App\Product;
use Illuminate\Http\Request;
use Session;

class FrontendController extends Controller
{
    public function index()
    {
        $new_products = Product::where('new', 1)->with('category')->take(10)->get();
        $top_selling = Product::where('top_selling', 1)->with('category')->take(10)->get();
        return view('frontend.index', compact('new_products', 'top_selling'));
    }

    public function store($cate = 'all-products')
    {

        if ($cate === 'all-products') {
            $category = '';
            $products = Product::orderBy('created_at')->paginate(9);
        } else {
            $category = Category::where('keyword', '=', $cate)->with('parentCategories')->first();
            if ($category) { //check if url exists
                if ($category->parent_id === 0) {
                    $getIdSubCategory = $category->subCategories()->pluck('id');
                    $products = Product::whereIn('category_id', $getIdSubCategory)->paginate(9);
                } else {
                    $products = Product::where('category_id', '=', $category->id)->paginate(9);
                }
                return view('frontend.store', compact('category', 'products'));
            } else {
                return view('frontend.error');
            }
        }
    }

    public function getSearch(Request $request)
    {
        $search = Product::where('name', 'like', '%' . $request->search . '%')->orWhere('price', $request->search)->paginate(9);
        // return $search;
        return view('frontend.search', compact('search'));
    }

    public function show($id)
    {
        $product = Product::where('id', $id)->with('image_products')->first();
        $category = $product->category()->first()->parentCategories()->first();
        $comment_ratings = $product->users()->paginate(5);
        $related_products = Product::inRandomOrder()->with('category')->take(4)->get();
        // return $related_products;
        return view('frontend.product', compact('product', 'category', 'comment_ratings', 'related_products'));
    }

    public function getAddToCart(Request $request, $id)
    {
        $addtocart = Product::find($id);
        $oldCart = Session::has('cart') ? Session::get('cart') : null;
        $cart = new Cart($oldCart);
        $cart->add($addtocart, $addtocart->id);
        $request->session()->put('cart', $cart);
        //dd($request->session()->get('cart'));
        return redirect()->route('index');
    }

    public function getCart()
    {
        if (!Session::has('cart')) {
            return view('frontend.shopcart', ['addtocart' => null]);
        }
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);
        //dd($cart);
        return view('frontend.shopcart', ['addtocart' => $cart->items, 'totaLPrice' => $cart->totaLPrice]);
    }
    public function getCheckout()
    {
        if (!Session::has('cart')) {
            return view('frontend.shopcart', ['addtocart' => null]);
        }
        $oldCart = Session::get('cart');
        //dd($oldCart);
        $cart = new cart($oldCart);
        $total = $cart->totaLPrice;
        //dd($total);
        return view('frontend.checkout', ['total' => $total]);
    }
}
