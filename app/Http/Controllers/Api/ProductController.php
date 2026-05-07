<?php

namespace App\Http\Controllers\Api;

use App\Models\Product;
use App\Traits\ApiResponse;
use App\Traits\UploadTrait;
use Illuminate\Support\Str;
use App\Models\ProductImage;
use Illuminate\Http\Request;
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
            $query = Product::with(['category', 'primaryImage']);

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

            return $this->success('Successfully retrieve', $products->items(), true, $products);

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
            'description' => 'nullable|string',
            'price' => 'required|numeric',
            'offer_price' => 'nullable|numeric',
            'stock' => 'required|integer',
            'variants' => 'nullable|json',
            'is_featured' => 'boolean',
            'status' => 'in:active,inactive',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $product = DB::transaction(function () use ($request) {
                $data = $request->except('images');
                $data['slug'] = Str::slug($data['name']);

                // Ensure slug uniqueness
                $count = Product::where('slug', 'like', $data['slug'] . '%')->count();
                if ($count > 0) {
                    $data['slug'] = $data['slug'] . '-' . ($count + 1);
                }

                if (empty($data['sku'])) {
                    $data['sku'] = 'HS-' . strtoupper(Str::random(8));
                }

                $product = Product::create($data);

                // Handle Images
                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $index => $image) {
                        $path = $this->uploadFile($image, 'products');

                        ProductImage::create([
                            'product_id' => $product->id,
                            'image_path' => $path,
                            'is_primary' => $index === 0,
                            'sort_order' => $index,
                        ]);
                    }
                }

                return $product->load('images');
            });

            return $this->success('Product created successfully', $product, false, null, true, 201);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $product = Product::with(['category', 'images', 'reviews.user'])->findOrFail($id);
            return $this->success('Successfully retrieve', $product);
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
                'description' => 'nullable|string',
                'price' => 'numeric',
                'offer_price' => 'nullable|numeric',
                'stock' => 'integer',
                'variants' => 'nullable|json',
                'is_featured' => 'boolean',
                'status' => 'in:active,inactive',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation error', 422, $validator->errors()->toArray());
            }

            $data = $request->all();
            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $product->update($data);

            return $this->success('Product updated successfully', $product);
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
}
