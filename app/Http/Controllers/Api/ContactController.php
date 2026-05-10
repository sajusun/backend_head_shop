<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ContactMessage;
use App\Traits\ApiResponse;
use App\Mail\ContactReplyMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Services\NotificationService;

class ContactController extends Controller
{
    use ApiResponse;

    /**
     * Store a new contact message (Public).
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'nullable|string|max:255',
            'message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $message = ContactMessage::create($request->all());

            // Notify Admins
            NotificationService::notifyAdmins(
                'info',
                "New Contact Message! 📧",
                "You have received a new message from {$request->name} regarding: " . ($request->subject ?? 'No Subject'),
                '/admin/contact',
                ['message_id' => $message->id]
            );

            return $this->success('Message sent successfully', $message, false, null, true, 201);
        } catch (\Throwable $th) {
            Log::error('Contact Store Error: ' . $th->getMessage());
            return $this->error('Failed to send message', 500);
        }
    }

    /**
     * List all messages (Admin Only).
     */
    public function index(Request $request)
    {
        try {
            $query = ContactMessage::with('replier')->latest();

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            $messages = $query->paginate(15);
            return $this->success('Messages retrieved successfully', $messages->items(), true, $messages);
        } catch (\Throwable $th) {
            Log::error('Contact Index Error: ' . $th->getMessage());
            return $this->error('Failed to retrieve messages', 500);
        }
    }

    /**
     * Show a single message (Admin Only).
     */
    public function show($id)
    {
        try {
            $message = ContactMessage::with('replier')->findOrFail($id);
            return $this->success('Message retrieved successfully', $message);
        } catch (\Throwable $th) {
            return $this->error('Message not found', 404);
        }
    }

    /**
     * Reply to a message (Admin Only).
     */
    public function reply(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reply_message' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->error('Validation error', 422, $validator->errors()->toArray());
        }

        try {
            $contactMessage = ContactMessage::findOrFail($id);

            // Send Email
            $replyData = [
                'name' => $contactMessage->name,
                'original_message' => $contactMessage->message,
                'subject' => $contactMessage->subject,
                'reply_message' => $request->reply_message,
            ];

            Mail::to($contactMessage->email)->send(new ContactReplyMail($replyData));

            // Update Message
            $contactMessage->update([
                'reply_message' => $request->reply_message,
                'status' => 'replied',
                'replied_at' => now(),
                'replied_by' => auth('api')->id(),
            ]);

            return $this->success('Reply sent successfully', $contactMessage);
        } catch (\Throwable $th) {
            Log::error('Contact Reply Error: ' . $th->getMessage());
            return $this->error('Failed to send reply: ' . $th->getMessage(), 500);
        }
    }

    /**
     * Delete a message (Admin Only).
     */
    public function destroy($id)
    {
        try {
            $message = ContactMessage::findOrFail($id);
            $message->delete();
            return $this->success('Message deleted successfully');
        } catch (\Throwable $th) {
            return $this->error('Message not found', 404);
        }
    }
}
