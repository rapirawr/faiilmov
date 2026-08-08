<?php

namespace App\Http\Controllers;

use App\Models\AppLaunchNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DownloadAppController extends Controller
{
    /**
     * Display the mobile app coming soon landing page.
     */
    public function index()
    {
        return view('download-app');
    }

    /**
     * Store email notification request for mobile app launch.
     */
    public function notifyMe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email:rfc,dns|max:255',
        ], [
            'email.required' => 'Alamat email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first('email'),
            ], 422);
        }

        $email = strtolower(trim($request->input('email')));

        $existing = AppLaunchNotification::where('email', $email)->first();

        if ($existing) {
            return response()->json([
                'success' => true,
                'message' => 'Email Anda sudah terdaftar! Kami akan memberi tahu Anda begitu aplikasi Flutter dirilis.',
            ]);
        }

        AppLaunchNotification::create(['email' => $email]);

        return response()->json([
            'success' => true,
            'message' => 'Terima kasih! Email Anda telah tersimpan. Kami akan mengirimkan notifikasi begitu versi mobile rilis.',
        ]);
    }
}
