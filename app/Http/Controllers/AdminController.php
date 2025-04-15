<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Product;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendPriceChangeNotification;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function products()
    {
        $products = Product::all();
        return view('admin.products', compact('products'));
    }

    public function editProduct($id)
    {
        $product = Product::findOrFail($id);
        return view('admin.edit_product', compact('product'));
    }

    public function updateProduct(Request $request, $id)
    {
        // Validate the name field
        $request->validate([
            'name' => 'required|min:3',
        ]);

        $product = Product::findOrFail($id);

        // Store the old price before updating
        $oldPrice = $product->price;

        $product->update($request->except("image"));

        $this->upsertImageFromRequest($request, $product);

        // Check if price has changed
        if ($oldPrice != $product->price) {
            // Get notification email from env
            $notificationEmail = config('data.emails.price_notification');

            try {
                SendPriceChangeNotification::dispatch(
                    $product,
                    $oldPrice,
                    $product->price,
                    $notificationEmail
                );
            } catch (\Exception $e) {
                 Log::error('Failed to dispatch price change notification: ' . $e->getMessage());
            }
        }

        return redirect()->route('admin.products')->with('success', 'Product updated successfully');
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return redirect()->route('admin.products')->with('success', 'Product deleted successfully');
    }

    public function addProductForm()
    {
        return view('admin.add_product');
    }

    public function addProduct(Request $request)
    {
        $request->validate([
            'name' => 'required|min:3',
        ]);

        $product = Product::create($request->except('image'));

        $this->upsertImageFromRequest($request, $product);

        return redirect()->route('admin.products')->with('success', 'Product added successfully');
    }

    private function upsertImageFromRequest(Request $request, Product $product)
    {
        if ($request->hasFile('image')) {
            if(!empty($product->image) && $product->image != 'product-placeholder.jpg') {
                // Delete the old image if it exists
                Storage::disk("public")->delete($product->image);
            }
            $filename = "p-{$product->id}.{$request->file('image')->getClientOriginalExtension()}";
            $request->file('image')->storeAs(
                'uploads',
                $filename,
                'public'
            );

            $product->image = "uploads/{$filename}";
            $product->save();
        } elseif (empty($product->image)) {
            $product->image = 'product-placeholder.jpg';
            $product->save();
        }
    }

}
