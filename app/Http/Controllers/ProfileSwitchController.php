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
            'avatar' => 'nullable|string',
            'is_child' => 'nullable|boolean',
            'pin' => 'nullable|string|max:4',
        ]);

        $profile = Auth::user()->profiles()->create([
            'name' => $request->name,
            'avatar' => $request->avatar ?? null,
            'is_child' => $request->boolean('is_child'),
            'pin' => $request->pin ? Hash::make($request->pin) : null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['status' => 'ok', 'profile' => $profile]);
        }

        return redirect()->route('profiles.index')->with('success', "Profil {$profile->name} berhasil dibuat.");
    }

    public function switch(Profile $profile)
    {
        if ($profile->user_id !== Auth::id()) {
            abort(403);
        }

        session(['active_profile_id' => $profile->id]);

        if (request()->wantsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return redirect()->route('home')->with('success', "Beralih ke profil {$profile->name}.");
    }

    public function switchMain(Request $request)
    {
        session()->forget('active_profile_id');

        if ($request->wantsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return redirect()->route('home')->with('success', 'Beralih ke Akun Utama.');
    }

    public function destroy(Profile $profile)
    {
        if ($profile->user_id !== Auth::id()) {
            abort(403);
        }

        if (session('active_profile_id') == $profile->id) {
            session()->forget('active_profile_id');
        }

        $profile->delete();

        if (request()->wantsJson()) {
            return response()->json(['status' => 'ok']);
        }

        return redirect()->route('profiles.index')->with('success', 'Profil telah dihapus.');
    }
}
