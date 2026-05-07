<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscription;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    use ApiResponse;
    /**
     * Subscribe to the newsletter.
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:newsletter_subscriptions,email',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $subscription = NewsletterSubscription::create([
                'email' => $request->email,
                'status' => 'subscribed',
            ]);

            return $this->success('Subscribed successfully', $subscription, false, null, true, 201);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Unsubscribe from the newsletter.
     */
    public function unsubscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:newsletter_subscriptions,email',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $subscription = NewsletterSubscription::where('email', $request->email)->first();
            if (!$subscription) {
                return $this->error('Subscription not found', 404);
            }

            $subscription->update(['status' => 'unsubscribed']);

            return $this->success('Unsubscribed successfully');
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * List all subscriptions (Admin Only).
     */
    public function index()
    {
        try {
            $subscriptions = NewsletterSubscription::latest()->paginate(20);
            return $this->success('Successfully retrieve', $subscriptions->items(), true, $subscriptions);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }

    /**
     * Delete a subscription (Admin Only).
     */
    public function destroy($id)
    {
        try {
            $subscription = NewsletterSubscription::findOrFail($id);
            $subscription->delete();

            return $this->success('Subscription deleted successfully');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->error('Subscription not found', 404);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return $this->error('Somethings Went Wrong', 500);
        }
    }
}
