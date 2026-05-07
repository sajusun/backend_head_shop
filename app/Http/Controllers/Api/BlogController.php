<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;

use App\Traits\UploadTrait;

class BlogController extends Controller
{
    use UploadTrait;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Blog::with(['author', 'category'])
            ->where('status', 'published');

        if ($request->has('category')) {
            $query->whereHas('category', function($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        $blogs = $query->latest('published_at')->paginate($request->query('per_page', 24));

        return response()->json($blogs);
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
            return response()->json($validator->errors(), 422);
        }

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

        return response()->json($blog, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $blog = Blog::with(['author', 'category', 'comments.user'])->findOrFail($id);
        
        // Increment views
        $blog->increment('views_count');

        return response()->json($blog);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $blog = Blog::findOrFail($id);

        // Check if user is the author or admin
        if (auth('api')->id() !== $blog->author_id && auth('api')->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
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
            return response()->json($validator->errors(), 422);
        }

        $data = $validator->validated();
        if (isset($data['title'])) {
            $data['slug'] = Str::slug($data['title']);
        }

        if (isset($data['status']) && $data['status'] === 'published' && !$blog->published_at) {
            $data['published_at'] = now();
        }

        $blog->update($data);

        return response()->json($blog);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $blog = Blog::findOrFail($id);
        
        if (auth('api')->id() !== $blog->author_id && auth('api')->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $blog->delete();

        return response()->json(['message' => 'Blog post deleted successfully']);
    }

    /**
     * Like/Unlike a blog post.
     */
    public function toggleLike(string $id)
    {
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

        return response()->json(['message' => "Blog post {$status} successfully", 'likes_count' => $blog->likes_count]);
    }
}
