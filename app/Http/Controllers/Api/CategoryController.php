<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Category::all();

        if ($categories->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No categories found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Categories are listed successfully.',
            'count'   => $categories->count(),
            'data'    => $categories,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'        => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'Category name is required.',
            'name.string'   => 'Category name must be a string.',
            'name.max'      => 'Category name must not exceed 255 characters.',
            'name.unique'   => 'Category name already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $category = Category::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category is created successfully.',
            'data'    => $category,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json([
                'success' => false,
                'message' => 'Category not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $category,
        ], 200);
    }
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('categories')->ignore($id),
            ],
            'description' => 'nullable|string|max:1000',
        ], [
            'name.required' => 'Category name is required.',
            'name.string'   => 'Category name must be a string.',
            'name.max'      => 'Category name must not exceed 255 characters.',
            'name.unique'   => 'Category name already exists.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $category->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Category is updated successfully.',
            'data'    => $category,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category is deleted successfully.',
        ], 200);
    }
}
