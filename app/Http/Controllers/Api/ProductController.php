<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Traits\ApiResponse;
use App\Traits\UploadTrait;
use Illuminate\Support\Str;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use App\Http\Resources\ProductResource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    use UploadTrait;
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 16);
        $currentPage = $request->input('current_page', 1);

        try {
            $query = Product::with(['category', 'primaryImage', 'variants.color', 'variants.size', 'amenityItems'])
                ->withCount('approvedReviews')
                ->withAvg('approvedReviews', 'rating');

            // Filtering by Category
            if ($request->has('category')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', $request->category);
                });
            }

            // Search by Name or SKU
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            // Price Range
            if ($request->has('min_price')) {
                $query->where('price', '>=', $request->min_price);
            }
            if ($request->has('max_price')) {
                $query->where('price', '<=', $request->max_price);
            }

            // Featured
            if ($request->has('is_featured')) {
                $query->where('is_featured', $request->is_featured);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->query('status'));
            }

            // Sorting
            $sort = $request->query('sort', 'latest');
            switch ($sort) {
                case 'price_low':
                    $query->orderBy('price', 'asc');
                    break;
                case 'price_high':
                    $query->orderBy('price', 'desc');
                    break;
                case 'latest':
                default:
                    $query->latest();
                    break;
            }

            $products = $query->paginate($perPage, '*', 'page', $currentPage);

            return $this->success(
                'Successfully retrieve',
                ProductResource::collection($products->items())->resolve($request),
                true,
                $products
            );

        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error("Somethings Went Wrong");
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
            $validator = Validator::make($request->all(), [
                'category_id' => 'required|exists:categories,id',
                'name' => 'required|string|max:255',
                'sku' => 'nullable|string|unique:products,sku',
                'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'description' => 'nullable|string',
                'price' => 'required|numeric',
                'offer_price' => 'nullable|numeric',
                'stock' => 'nullable|integer|min:0',
                'amenities' => 'nullable|array',
                'variants' => 'nullable|array',
                'variants.*.color_id' => 'nullable|exists:colors,id',
                'variants.*.size_id' => 'nullable|exists:sizes,id',
                'variants.*.price' => 'required_with:variants|numeric|min:0',
                'variants.*.offer_price' => 'nullable|numeric|min:0',
                'variants.*.stock' => 'required_with:variants|integer|min:0',
                'variants.*.status' => 'nullable|in:published,draft',
                'is_featured' => 'boolean',
                'status' => 'in:published,draft',
                'images' => 'nullable|array|max:4',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $product = DB::transaction(function () use ($request) {
                $data = $request->except('images', 'variants', '_method');
                $data['slug'] = Str::slug($data['name']);

                // Ensure slug uniqueness
                $count = Product::where('slug', 'like', $data['slug'] . '%')->count();
                if ($count > 0) {
                    $data['slug'] = $data['slug'] . '-' . ($count + 1);
                }

                if (empty($data['sku'])) {
                    $data['sku'] = 'HS-' . strtoupper(Str::random(8));
                }

                if ($request->hasFile('thumbnail')) {
                    $data['thumbnail'] = $this->uploadFile($request->file('thumbnail'), 'products');
                }

                $product = Product::create($data);

                // Handle Images
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $index => $image) {
                        $path = $this->uploadFile($image, 'products');

                        ProductImage::create([
                            'product_id' => $product->id,
                            'path' => $path,
                            'is_primary' => $index === 0,
                            'order' => $index,
                        ]);
                    }
                }

                if ($request->filled('variants')) {
                    foreach ($request->variants as $variant) {
                        // If color_id or size_id is string, it might be a new attribute or empty
                        if (!empty($variant['color_id']) || !empty($variant['size_id'])) {
                            $product->variants()->create($variant);
                        }
                    }
                }
                
                if ($request->filled('amenities')) {
                    $product->amenityItems()->sync($request->amenities);
                }

                return $product
                    ->load(['category', 'images', 'primaryImage', 'variants.color', 'variants.size', 'amenityItems'])
                    ->loadCount('approvedReviews')
                    ->loadAvg('approvedReviews', 'rating');
            });

            return $this->success(
                'Product created successfully',
                ProductResource::make($product)->resolve($request),
                false,
                null,
                true,
                201
            );
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function showBySlug(Request $request, string $slug)
    {
        try {
            $product = $this->detailsQuery()
                ->where('slug', $slug)
                ->firstOrFail();

            return $this->success('Successfully retrieve', ProductResource::make($product)->resolve($request));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Product not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $product = Product::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'category_id' => 'exists:categories,id',
                'name' => 'string|max:255',
                'sku' => 'nullable|string|unique:products,sku,' . $id,
                'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
                'description' => 'nullable|string',
                'price' => 'numeric',
                'offer_price' => 'nullable|numeric',
                'stock' => 'nullable|integer|min:0',
                'amenities' => 'nullable|array',
                'variants' => 'nullable|array',
                'variants.*.id' => 'nullable|exists:product_variants,id',
                'variants.*.color_id' => 'nullable|exists:colors,id',
                'variants.*.size_id' => 'nullable|exists:sizes,id',
                'variants.*.price' => 'required_with:variants|numeric|min:0',
                'variants.*.offer_price' => 'nullable|numeric|min:0',
                'variants.*.stock' => 'required_with:variants|integer|min:0',
                'variants.*.status' => 'nullable|in:published,draft',
                'is_featured' => 'boolean',
                'status' => 'in:published,draft',
                'images' => 'nullable|array|max:4',
                'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation error', 422, $validator->errors()->toArray());
            }

            $data = $request->except('images', 'variants', '_method');
            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            DB::transaction(function () use ($product, $data, $request) {
                if ($request->hasFile('thumbnail')) {
                    $data['thumbnail'] = $this->uploadFile($request->file('thumbnail'), 'products');
                }

                $product->update($data);

                if ($request->hasFile('images')) {
                    $hasPrimaryImage = $product->images()->where('is_primary', true)->exists();

                    foreach ($request->file('images') as $index => $image) {
                        $path = $this->uploadFile($image, 'products');

                        ProductImage::create([
                            'product_id' => $product->id,
                            'path' => $path,
                            'is_primary' => !$hasPrimaryImage && $index === 0,
                            'order' => (int) $product->images()->max('order') + $index + 1,
                        ]);
                    }
                }

                if ($request->filled('variants')) {
                    foreach ($request->variants as $variant) {
                        $variantId = $variant['id'] ?? null;
                        unset($variant['id']);

                        if ($variantId) {
                            ProductVariant::where('product_id', $product->id)
                                ->where('id', $variantId)
                                ->update($variant);
                        } else {
                            if (!empty($variant['color_id']) || !empty($variant['size_id'])) {
                                $product->variants()->create($variant);
                            }
                        }
                    }
                }

                if ($request->filled('amenities')) {
                    $product->amenityItems()->sync($request->amenities);
                }
            });

            $product = $product
                ->load(['category', 'images', 'primaryImage', 'variants.color', 'variants.size', 'amenityItems'])
                ->loadCount('approvedReviews')
                ->loadAvg('approvedReviews', 'rating');

            return $this->success('Product updated successfully', ProductResource::make($product)->resolve($request));
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Product not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return $this->success('Product deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Product not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    private function detailsQuery()
    {
        return Product::query()
            ->with([
                'category',
                'primaryImage',
                'images',
                'amenityItems',
                'variants.color',
                'variants.size',
                'approvedReviews.user',
                'approvedReviews.approvedReplies.user',
            ])
            ->withCount('approvedReviews')
            ->withAvg('approvedReviews', 'rating');
    }
}
