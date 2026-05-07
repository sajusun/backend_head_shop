<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CommentController extends Controller
{
    /**
     * Display a listing of comments for a blog post.
     */
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'blog_id' => 'required|exists:blogs,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $comments = Comment::with('user')
            ->where('blog_id', $request->blog_id)
            ->where('status', 'approved')
            ->whereNull('parent_id')
            ->with('replies.user')
            ->latest()
            ->paginate(20);

        return response()->json($comments);
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
            return response()->json($validator->errors(), 422);
        }

        $comment = Comment::create([
            'blog_id' => $request->blog_id,
            'user_id' => auth('api')->id(),
            'content' => $request->content,
            'parent_id' => $request->parent_id,
            'status' => 'pending', // Comments need approval by default
        ]);

        return response()->json([
            'message' => 'Comment submitted for approval',
            'comment' => $comment
        ], 201);
    }

    /**
     * Update the specified resource in storage. (Admin or Author only)
     */
    public function update(Request $request, string $id)
    {
        $comment = Comment::findOrFail($id);

        if (auth('api')->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,rejected',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $comment->update(['status' => $request->status]);

        return response()->json($comment);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $comment = Comment::findOrFail($id);

        if (auth('api')->id() !== $comment->user_id && auth('api')->user()->role !== 'admin') {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $comment->delete();

        return response()->json(['message' => 'Comment deleted successfully']);
    }
}
