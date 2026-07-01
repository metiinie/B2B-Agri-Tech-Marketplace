<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    /**
     * Get all active categories.
     */
    public function index(): Response
    {
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response($categories);
    }

    /**
     * Create a new category (admin only).
     */
    public function store(Request $request): Response
    {
        $this->authorize('create', Category::class);

        $validated = $request->validate([
            'name' => 'required|string|unique:categories,name',
            'slug' => 'required|string|unique:categories,slug',
            'is_active' => 'sometimes|boolean',
        ]);

        $category = Category::create($validated);
        return response($category, 201);
    }

    /**
     * Display a specific category.
     */
    public function show(Category $category): Response
    {
        return response($category);
    }

    /**
     * Update a category (admin only).
     */
    public function update(Request $request, Category $category): Response
    {
        $this->authorize('update', $category);

        $validated = $request->validate([
            'name' => 'sometimes|string|unique:categories,name,' . $category->id,
            'slug' => 'sometimes|string|unique:categories,slug,' . $category->id,
            'is_active' => 'sometimes|boolean',
        ]);

        $category->update($validated);
        return response($category);
    }

    /**
     * Delete a category (admin only).
     */
    public function destroy(Category $category): Response
    {
        $this->authorize('delete', $category);

        // Don't actually delete; just deactivate
        $category->update(['is_active' => false]);
        return response(['message' => 'Category deactivated'], 200);
    }

    /**
     * Get categories with listing count.
     */
    public function withListingCount(): Response
    {
        $categories = Category::where('is_active', true)
            ->withCount('listings')
            ->orderBy('name')
            ->get();

        return response($categories);
    }
}
