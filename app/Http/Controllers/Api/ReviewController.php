<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ReviewResource;
use App\Models\Review;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    use ApiResponse;

    public function index(Request $request, string $productId)
    {
        try {
            $reviews = Review::query()
                ->with(['user', 'approvedReplies.user'])
                ->where('product_id', $productId)
                ->approved()
                ->parentsOnly()
                ->latest()
                ->get();

            return $this->success(
                'Successfully retrieve',
                ReviewResource::collection($reviews)->resolve($request)
            );
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    public function store(Request $request)
    {
        $payload = $request->all();

        if (isset($payload['productId']) && !isset($payload['product_id'])) {
            $payload['product_id'] = $payload['productId'];
        }

        $validator = Validator::make($payload, [
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:3|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $review = Review::create([
                'product_id' => $validator->validated()['product_id'],
                'user_id' => auth('api')->id(),
                'rating' => $validator->validated()['rating'],
                'comment' => $validator->validated()['comment'],
                'status' => 'approved',
            ])->load(['user', 'approvedReplies.user']);

            return $this->success(
                'Review submitted successfully',
                ReviewResource::make($review)->resolve($request),
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

    public function storeReply(Request $request, string $reviewId)
    {
        $validator = Validator::make($request->all(), [
            'comment' => 'required|string|min:2|max:2000',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $parent = Review::query()
                ->approved()
                ->parentsOnly()
                ->findOrFail($reviewId);

            $reply = Review::create([
                'product_id' => $parent->product_id,
                'user_id' => auth('api')->id(),
                'rating' => $parent->rating,
                'comment' => $validator->validated()['comment'],
                'status' => 'approved',
                'parent_id' => $parent->id,
            ])->load(['user', 'approvedReplies.user']);

            return $this->success(
                'Reply submitted successfully',
                ReviewResource::make($reply)->resolve($request),
                false,
                null,
                true,
                201
            );
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Review not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    public function destroy(string $id)
    {
        try {
            $review = Review::findOrFail($id);
            $user = auth('api')->user();

            if ($review->user_id !== $user->id && $user->role !== 'admin') {
                return $this->error('Forbidden', 403);
            }

            $review->delete();

            return $this->success('Review deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Review not found', 404);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }
}
