<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $type = $request->query('type', 'product');
            $categories = Category::with('children')
                ->where('type', $type)
                ->whereNull('parent_id')
                ->latest()
                ->paginate($request->query('per_page', 20));
            
            return $this->success('Successfully retrieve', $categories->items(), true, $categories);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'type' => 'in:product,blog',
            'parent_id' => 'nullable|exists:categories,id',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'status' => 'in:active,inactive',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $data = $validator->validated();
            $data['slug'] = Str::slug($data['name']);

            // Ensure slug uniqueness
            $count = Category::where('slug', 'like', $data['slug'] . '%')->count();
            if ($count > 0) {
                $data['slug'] = $data['slug'] . '-' . ($count + 1);
            }

            $category = Category::create($data);

            return $this->success('Category created successfully', $category, false, null, true, 201);
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
            $category = Category::with(['children', 'products'])->findOrFail($id);
            return $this->success('Successfully retrieve', $category);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Category not found', 404);
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
            $category = Category::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'string|max:255',
                'type' => 'in:product,blog',
                'parent_id' => 'nullable|exists:categories,id',
                'description' => 'nullable|string',
                'image' => 'nullable|string',
                'status' => 'in:active,inactive',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation error', 422, $validator->errors()->toArray());
            }

            $data = $validator->validated();
            if (isset($data['name'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            $category->update($data);

            return $this->success('Category updated successfully', $category);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Category not found', 404);
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
            $category = Category::findOrFail($id);
            $category->delete();

            return $this->success('Category deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Category not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }
}
