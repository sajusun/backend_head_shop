<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of comments for a blog post.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blog_id' => 'required|exists:blogs,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $comments = Comment::with('user')
                ->where('blog_id', $request->blog_id)
                ->where('status', 'approved')
                ->whereNull('parent_id')
                ->with('replies.user')
                ->latest()
                ->paginate(20);

            return $this->success('Successfully retrieve', $comments->items(), true, $comments);
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
            'blog_id' => 'required|exists:blogs,id',
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $comment = Comment::create([
                'blog_id' => $request->blog_id,
                'user_id' => auth('api')->id(),
                'content' => $request->content,
                'parent_id' => $request->parent_id,
                'status' => 'pending', // Comments need approval by default
            ]);

            return $this->success('Comment submitted for approval', $comment, false, null, true, 201);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Update the specified resource in storage. (Admin or Author only)
     */
    public function update(Request $request, string $id)
    {
        try {
            $comment = Comment::findOrFail($id);

            if (auth('api')->user()->role !== 'admin') {
                return $this->error('Unauthorized', 403);
            }

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,approved,rejected',
            ]);

            if ($validator->fails()) {
                return $this->error('Validation error', 422, $validator->errors()->toArray());
            }

            $comment->update(['status' => $request->status]);

            return $this->success('Comment updated successfully', $comment);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Comment not found', 404);
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
            $comment = Comment::findOrFail($id);

            if (auth('api')->id() !== $comment->user_id && auth('api')->user()->role !== 'admin') {
                return $this->error('Unauthorized', 403);
            }

            $comment->delete();

            return $this->success('Comment deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Comment not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }
}
