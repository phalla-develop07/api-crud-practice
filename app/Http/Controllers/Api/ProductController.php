<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with('category')->get();

        if ($products->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No products found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Products retrieved successfully.',
            'count'   => $products->count(),
            'data'    => $products,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $product = Product::with('category')->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Product retrieved successfully.',
            'data'    => $product,
        ], 200);
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:products,name',
            'description' => 'nullable|string|max:2000',
            'price'       => 'required|numeric|min:0|max:999999.99',
            'stock'       => 'required|integer|min:0|max:999999',
            'category_id' => 'required|exists:categories,id',
            'is_active'   => 'boolean',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully.',
            'data'    => $product->load('category'),
        ], 201);
    }

    /**
     * Update the specified resource.
     * Supports both PUT (JSON) and POST with _method=PUT (multipart/form-data for file uploads).
     */
    public function update(Request $request, $id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('products')->ignore($id),
            ],
            'description' => 'nullable|string|max:2000',
            'price'       => 'sometimes|required|numeric|min:0|max:999999.99',
            'stock'       => 'sometimes|required|integer|min:0|max:999999',
            'category_id' => 'sometimes|required|exists:categories,id',
            'is_active'   => 'sometimes|boolean',
            'image'       => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,svg|max:5120',
            'remove_image'=> 'sometimes|boolean',
        ], $this->validationMessages());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Handle explicit image removal
        if (!empty($data['remove_image'])) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = null;
            unset($data['remove_image']);
        }
        // Handle new image upload
        elseif ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }
        // Prevent accidental overwrite of image with a plain string
        elseif (array_key_exists('image', $data) && is_string($data['image'])) {
            unset($data['image']);
        }

        $product->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully.',
            'data'    => $product->fresh('category'),
        ], 200);
    }

    /**
     * Remove the specified resource.
     */
    public function destroy($id)
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found.',
            ], 404);
        }

        // Delete associated image from storage
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully.',
        ], 200);
    }

    /**
     * Reusable validation messages.
     */
    private function validationMessages(): array
    {
        return [
            'name.required'       => 'Product name is required.',
            'name.unique'         => 'A product with this name already exists.',
            'name.max'            => 'Product name cannot exceed 255 characters.',

            'price.required'      => 'Price is required.',
            'price.numeric'       => 'Price must be a valid number.',
            'price.min'           => 'Price cannot be negative.',
            'price.max'           => 'Price cannot exceed $999,999.99.',

            'stock.required'      => 'Stock quantity is required.',
            'stock.integer'       => 'Stock must be a whole number.',
            'stock.min'           => 'Stock cannot be negative.',
            'stock.max'           => 'Stock quantity cannot exceed 999,999.',

            'category_id.required'=> 'Please select a category.',
            'category_id.exists'  => 'The selected category does not exist.',

            'image.image'         => 'The uploaded file must be a valid image.',
            'image.mimes'         => 'Allowed image types: jpeg, png, jpg, gif, webp, svg.',
            'image.max'           => 'Image size must not exceed 5MB.',

            'description.max'     => 'Description cannot exceed 2,000 characters.',
        ];
    }
}
