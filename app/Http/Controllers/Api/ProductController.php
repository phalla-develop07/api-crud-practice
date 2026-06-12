<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
            'message' => 'Products are listed successfully.',
            'count' => $products->count(),
            'data' => $products,
        ], 200);
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:products,name',
            'description' => 'nullable|string|max:1000',
            'image' => 'nullable|string|max:255',
            'is_active' => 'boolean',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
        ], [
            'name.required' => 'Product name is required.',
            'name.unique' => 'Product name already exists.',
            'price.required' => 'Price is required.',
            'price.numeric' => 'Price must be a number.',
            'stock.required' => 'Stock is required.',
            'stock.integer' => 'Stock must be an integer.',
            'category_id.required' => 'Category is required.',
            'category_id.exists' => 'Selected category does not exist.',
            'is_active.boolean' => 'Is active must be a boolean.',
            'image.string' => 'Image must be a string.',
            'image.max' => 'Image must not exceed 255 characters.',
            'description.string' => 'Description must be a string.',
            'description.max' => 'Description must not exceed 1000 characters.',
            'name.string' => 'Name must be a string.',
            'name.max' => 'Name must not exceed 255 characters.',
            'price.min' => 'Price must not be less than 0.',
            'stock.min' => 'Stock must not be less than 0.',
            'category_id.integer' => 'Category must be an integer.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product is created successfully.',
            'data' => $product->load('category'),
        ], 201);
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
            'data' => $product,
        ], 200);
    }

    /**
     * Update the specified resource.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('products')->ignore($id),
            ],
            'description' => 'nullable|string|max:1000',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'image' => 'nullable|string|max:255',
            'category_id' => 'sometimes|required|exists:categories,id',
        ], [
            'name.required' => 'Product name is required.',
            'name.unique' => 'Product name already exists.',
            'price.numeric' => 'Price must be a number.',
            'stock.integer' => 'Stock must be an integer.',
            'is_active.boolean' => 'Is active must be a boolean.',
            'image.string' => 'Image must be a string.',
            'image.max' => 'Image must not exceed 255 characters.',
            'category_id.required' => 'Category is required.',
            'category_id.integer' => 'Category must be an integer.',
            'category_id.exists' => 'Selected category does not exist.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Product is updated successfully.',
            'data' => $product->load('category'),
        ], 200);
    }

    /**
     * Remove the specified resource.
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product is deleted successfully.',
        ], 200);
    }
}
