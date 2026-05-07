<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

use App\Traits\UploadTrait;

class BlogController extends Controller
{
    use UploadTrait;
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = Blog::with(['author', 'category'])
                ->where('status', 'published');

            if ($request->filled('category')) {
                $query->whereHas('category', function ($q) use ($request) {
                    $q->where('slug', $request->category);
                });
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
            }

            $blogs = $query->latest('published_at')->paginate($request->query('per_page', 24));

            return $this->success('Successfully retrieve', $blogs->items(), true, $blogs);
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
            'category_id' => 'required|exists:categories,id',
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'content' => 'required|string',
            'featured_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'status' => 'in:draft,published',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $data = $request->except('featured_image');
            $data['author_id'] = auth('api')->id();
            $data['slug'] = Str::slug($data['title']);

            // Ensure slug uniqueness
            $count = Blog::where('slug', 'like', $data['slug'] . '%')->count();
            if ($count > 0) {
                $data['slug'] = $data['slug'] . '-' . ($count + 1);
            }

            if ($data['status'] === 'published') {
                $data['published_at'] = now();
            }

            if ($request->hasFile('featured_image')) {
                $data['featured_image'] = $this->uploadFile($request->file('featured_image'), 'blogs');
            }

            $blog = Blog::create($data);

            return $this->success('Blog created successfully', $blog, false, null, true, 201);
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
            $blog = Blog::with(['author', 'category', 'comments.user'])->findOrFail($id);

            // Increment views
            $blog->increment('views_count');

            return $this->success('Successfully retrieve', $blog);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Blog not found', 404);
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
            $blog = Blog::findOrFail($id);

            // Check if user is the author or admin
            if (auth('api')->id() !== $blog->author_id && auth('api')->user()->role !== 'admin') {
                return $this->error('Unauthorized', 403);
            }

            $validator = Validator::make($request->all(), [
                'category_id' => 'exists:categories,id',
                'title' => 'string|max:255',
                'excerpt' => 'nullable|string',
                'content' => 'string',
                'featured_image' => 'nullable|string',
                'status' => 'in:draft,published',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation error', 422, $validator->errors()->toArray());
            }

            $data = $validator->validated();
            if (isset($data['title'])) {
                $data['slug'] = Str::slug($data['title']);
            }

            if (isset($data['status']) && $data['status'] === 'published' && !$blog->published_at) {
                $data['published_at'] = now();
            }

            $blog->update($data);

            return $this->success('Blog updated successfully', $blog);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Blog not found', 404);
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
            $blog = Blog::findOrFail($id);

            if (auth('api')->id() !== $blog->author_id && auth('api')->user()->role !== 'admin') {
                return $this->error('Unauthorized', 403);
            }

            $blog->delete();

            return $this->success('Blog post deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Blog not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Like/Unlike a blog post.
     */
    public function toggleLike(string $id)
    {
        try {
            $blog = Blog::findOrFail($id);
            $user = auth('api')->user();

            if ($user->likedBlogs()->where('blog_id', $id)->exists()) {
                $user->likedBlogs()->detach($id);
                $blog->decrement('likes_count');
                $status = 'unliked';
            } else {
                $user->likedBlogs()->attach($id);
                $blog->increment('likes_count');
                $status = 'liked';
            }

            return $this->success("Blog post {$status} successfully", [
                'status' => $status,
                'likes_count' => $blog->likes_count,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Blog not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }
}
