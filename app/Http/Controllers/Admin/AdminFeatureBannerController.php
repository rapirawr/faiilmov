<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeatureBanner;
use Illuminate\Http\Request;

class AdminFeatureBannerController extends Controller
{
    public function index()
    {
        $banners = FeatureBanner::ordered()->paginate(15);
        return view('admin.feature-banners.index', compact('banners'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'badge_text' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'placeholder_text' => 'nullable|string|max:255',
            'input_type' => 'nullable|string|in:text,email,number,url,none',
            'button_text' => 'required|string|max:255',
            'button_icon' => 'nullable|string|max:50',
            'action_type' => 'required|in:request_modal,url_link',
            'action_url' => 'nullable|string|max:500',
            'bg_gradient' => 'required|string',
            'bg_gradient_from' => 'nullable|string|max:50',
            'bg_gradient_to' => 'nullable|string|max:50',
            'sort_order' => 'integer',
        ]);

        $validated['is_active'] = $request->has('is_active');

        FeatureBanner::create($validated);

        return redirect()->route('admin.feature-banners.index')
            ->with('success', 'Banner fitur baru berhasil ditambahkan.');
    }

    public function update(Request $request, FeatureBanner $featureBanner)
    {
        $validated = $request->validate([
            'badge_text' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'placeholder_text' => 'nullable|string|max:255',
            'input_type' => 'nullable|string|in:text,email,number,url,none',
            'button_text' => 'required|string|max:255',
            'button_icon' => 'nullable|string|max:50',
            'action_type' => 'required|in:request_modal,url_link',
            'action_url' => 'nullable|string|max:500',
            'bg_gradient' => 'required|string',
            'bg_gradient_from' => 'nullable|string|max:50',
            'bg_gradient_to' => 'nullable|string|max:50',
            'sort_order' => 'integer',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $featureBanner->update($validated);

        return redirect()->route('admin.feature-banners.index')
            ->with('success', 'Banner fitur berhasil diperbarui.');
    }

    public function toggleActive(FeatureBanner $featureBanner)
    {
        $featureBanner->update([
            'is_active' => !$featureBanner->is_active,
        ]);

        return redirect()->route('admin.feature-banners.index')
            ->with('success', 'Status banner berhasil diubah.');
    }

    public function destroy(FeatureBanner $featureBanner)
    {
        $featureBanner->delete();

        return redirect()->route('admin.feature-banners.index')
            ->with('success', 'Banner fitur berhasil dihapus.');
    }

    public function generateAi(Request $request)
    {
        $request->validate([
            'topic' => 'required|string|max:255',
        ]);

        $topic = trim($request->input('topic'));
        $aiService = app(\App\Services\NvidiaAiService::class);
        $result = $aiService->generateBannerCopywriting($topic);

        if (!$result || !isset($result['title'])) {
            $lower = strtolower($topic);
            
            if (str_contains($lower, 'request') || str_contains($lower, 'film') || str_contains($lower, 'minta')) {
                $result = [
                    'badge_text' => 'REQUEST FILM',
                    'title' => 'Film yang Kamu Cari Belum Ada di Katalog?',
                    'description' => "Kirim permintaan judul film atau series. Kami akan mencarikan dan mengimpornya untukmu secara gratis.",
                    'placeholder_text' => 'Ketik atau cari judul film...',
                    'button_text' => 'Request Sekarang',
                    'action_type' => 'request_modal',
                    'input_type' => 'text',
                    'bg_gradient' => 'amber_purple',
                ];
            } elseif (str_contains($lower, 'promo') || str_contains($lower, 'event') || str_contains($lower, 'diskon')) {
                $result = [
                    'badge_text' => 'EVENT & PROMO',
                    'title' => 'Dapatkan Penawaran Eksklusif Minggu Ini',
                    'description' => "Ikuti event {$topic} dan nikmati pengalaman nonton bersama teman.",
                    'placeholder_text' => 'Masukkan email kamu...',
                    'button_text' => 'Lihat Promo',
                    'action_type' => 'url_link',
                    'input_type' => 'email',
                    'bg_gradient' => 'rose_orange',
                ];
            } else {
                $result = [
                    'badge_text' => 'PEMBERITAHUAN FITUR',
                    'title' => 'Nikmati Pengalaman Nonton Terbaik di Faiilmov',
                    'description' => "Pembaruan seputar {$topic}. Jelajahi koleksi film dan series terbaru di Faiilmov.",
                    'placeholder_text' => 'Cari film atau series...',
                    'button_text' => 'Jelajahi Sekarang',
                    'action_type' => 'request_modal',
                    'input_type' => 'text',
                    'bg_gradient' => 'sky_indigo',
                ];
            }
        }

        return response()->json([
            'status' => 'success',
            'data' => $result,
        ]);
    }
}
