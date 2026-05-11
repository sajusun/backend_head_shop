<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class PromotionController extends Controller
{
    /**
     * Public: Get active promotions
     */
    public function active()
    {
        $promotions = Promotion::where('status', true)
            ->where(function ($query) {
                $query->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            })
            ->with(['products' => function($q) {
                $q->where('status', 'active')->limit(10);
            }])
            ->orderBy('order')
            ->get();

        return response()->json($promotions);
    }

    /**
     * Admin: List all promotions
     */
    public function index()
    {
        $promotions = Promotion::withCount('products')->orderBy('order')->get();
        return response()->json($promotions);
    }

    /**
     * Admin: Store a new promotion
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:banner,flash_sale,seasonal',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'boolean',
            'order' => 'integer',
            'banner' => 'nullable|image|max:2048',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id'
        ]);

        $validated['slug'] = Str::slug($validated['title']) . '-' . Str::random(5);

        if ($request->hasFile('banner')) {
            $validated['banner'] = $request->file('banner')->store('promotions', 'public');
        }

        $promotion = Promotion::create($validated);

        if (!empty($validated['product_ids'])) {
            $promotion->products()->sync($validated['product_ids']);
        }

        return response()->json([
            'message' => 'Promotion created successfully',
            'promotion' => $promotion->load('products')
        ], 201);
    }

    /**
     * Admin: Update promotion
     */
    public function update(Request $request, Promotion $promotion)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|string|in:banner,flash_sale,seasonal',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'boolean',
            'order' => 'integer',
            'banner' => 'nullable|image|max:2048',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id'
        ]);

        if ($request->hasFile('banner')) {
            if ($promotion->getRawOriginal('banner')) {
                Storage::disk('public')->delete($promotion->getRawOriginal('banner'));
            }
            $validated['banner'] = $request->file('banner')->store('promotions', 'public');
        }

        $promotion->update($validated);

        if (isset($validated['product_ids'])) {
            $promotion->products()->sync($validated['product_ids']);
        }

        return response()->json([
            'message' => 'Promotion updated successfully',
            'promotion' => $promotion->load('products')
        ]);
    }

    /**
     * Admin: Delete promotion
     */
    public function destroy(Promotion $promotion)
    {
        if ($promotion->getRawOriginal('banner')) {
            Storage::disk('public')->delete($promotion->getRawOriginal('banner'));
        }
        $promotion->delete();
        return response()->json(['message' => 'Promotion deleted successfully']);
    }
}
