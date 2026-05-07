<?php 

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NewsletterSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NewsletterController extends Controller
{
    /**
     * Subscribe to the newsletter.
     */
    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|unique:newsletter_subscriptions,email',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $subscription = NewsletterSubscription::create([
            'email' => $request->email,
            'status' => 'subscribed',
        ]);

        return response()->json(['message' => 'Subscribed successfully', 'subscription' => $subscription], 201);
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
            return response()->json($validator->errors(), 422);
        }

        $subscription = NewsletterSubscription::where('email', $request->email)->first();
        $subscription->update(['status' => 'unsubscribed']);

        return response()->json(['message' => 'Unsubscribed successfully']);
    }

    /**
     * List all subscriptions (Admin Only).
     */
    public function index()
    {
        $subscriptions = NewsletterSubscription::latest()->paginate(20);
        return response()->json($subscriptions);
    }
}
