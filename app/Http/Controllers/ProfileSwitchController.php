<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileSwitchController extends Controller
{
    public function index()
    {
        $profiles = Auth::user()->profiles;
        return view('profiles.index', compact('profiles'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'pin' => 'nullable|string|max:4',
        ]);

        $profile = Auth::user()->profiles()->create([
            'name' => $request->name,
            'pin' => $request->pin ? Hash::make($request->pin) : null,
        ]);

        return response()->json(['status' => 'ok', 'profile' => $profile]);
    }

    public function switch(Profile $profile)
    {
        if ($profile->user_id !== Auth::id()) {
            abort(403);
        }

        session(['active_profile_id' => $profile->id]);
        return response()->json(['status' => 'ok']);
    }

    public function destroy(Profile $profile)
    {
        if ($profile->user_id !== Auth::id()) {
            abort(403);
        }
        $profile->delete();
        return response()->json(['status' => 'ok']);
    }
}
