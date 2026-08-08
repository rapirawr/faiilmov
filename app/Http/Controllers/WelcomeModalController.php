<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WelcomeModalController extends Controller
{
    /**
     * Dismiss welcome modal for authenticated user.
     */
    public function dismiss(Request $request)
    {
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $user->update(['has_seen_welcome_modal' => true]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Welcome modal status updated.'
        ]);
    }
}
