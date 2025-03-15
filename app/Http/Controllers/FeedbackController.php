<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Feedback;

class FeedbackController extends Controller
{
    // Store feedback
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_email' => 'nullable|email|exists:guest_user,user_email',
            'emoji_rating' => 'required|in:in-love,happy,neutral,sad,angry',
            'comment' => 'nullable|string|max:1000',
        ]);

        $feedback = Feedback::create($validated);

        return response()->json([
            'message' => 'Feedback submitted successfully.',
            'feedback' => $feedback,
        ], 201);
    }

    // Retrieve all feedback
    public function index()
    {
        $feedback = Feedback::with('user')->get();

        return response()->json($feedback);
    }
}
