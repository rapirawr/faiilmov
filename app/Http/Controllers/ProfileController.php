<?php

namespace App\Http\Controllers;

use App\Models\Watchlist;
use App\Models\WatchHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $watchlists = $user->watchlists()
            ->whereHas('film')
            ->with('film')
            ->latest()
            ->get();

        $reviews = $user->reviews()
            ->whereHas('film')
            ->with('film')
            ->latest()
            ->get();

        $watchHistories = $user->watchHistories()
            ->whereHas('film')
            ->with(['film.genres', 'film.seasons.episodes'])
            ->latest('updated_at')
            ->get();

        // Analytics: total hours watched
        $totalSeconds = $watchHistories->sum('progress_seconds');
        $totalHoursWatched = round($totalSeconds / 3600, 1);

        // Analytics: top genre
        $genreCounts = [];
        foreach ($watchHistories as $h) {
            if ($h->film && $h->film->genres) {
                foreach ($h->film->genres as $g) {
                    $genreCounts[$g->name] = ($genreCounts[$g->name] ?? 0) + 1;
                }
            }
        }
        arsort($genreCounts);
        $topGenre = !empty($genreCounts) ? array_key_first($genreCounts) : 'Beragam';

        return view('profile', compact('user', 'watchlists', 'reviews', 'watchHistories', 'totalHoursWatched', 'topGenre'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|max:255|unique:users,email,' . $user->id,
            'avatar_file' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:25600',
            'avatar'      => 'nullable|string|max:1000',
            'bio'         => 'nullable|string|max:500',
            'phone'       => 'nullable|string|max:50',
        ]);

        $avatarUrl = $request->avatar;

        if ($request->hasFile('avatar_file')) {
            $avatarUrl = $this->compressAndSaveAvatar($request->file('avatar_file'));
        }

        $user->update([
            'name'   => $request->name,
            'email'  => $request->email,
            'avatar' => $avatarUrl ?: $user->avatar,
            'bio'    => $request->bio,
            'phone'  => $request->phone,
        ]);

        return redirect()->route('profile')->with('success', 'Profil berhasil diperbarui!');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password'      => 'required',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => 'Kata sandi saat ini tidak sesuai.',
            ]);
        }

        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('profile')->with('success', 'Kata sandi berhasil diperbarui!');
    }

    public function clearWatchlist()
    {
        Watchlist::where('user_id', Auth::id())->delete();
        return redirect()->route('profile')->with('success', 'Watchlist berhasil dikosongkan.');
    }

    public function deleteAccount(Request $request)
    {
        $user = Auth::user();

        $request->validate(['confirm_password' => 'required']);

        if (!Hash::check($request->confirm_password, $user->password)) {
            return redirect()->back()->withErrors(['confirm_password' => 'Password tidak sesuai.']);
        }

        Auth::logout();
        $user->watchlists()->delete();
        $user->watchHistories()->delete();
        $user->reviews()->delete();
        $user->delete();

        return redirect('/')->with('success', 'Akun Anda telah dihapus secara permanen.');
    }

    public function destroyHistory(WatchHistory $watchHistory)
    {
        if ($watchHistory->user_id === Auth::id()) {
            $watchHistory->delete();
        }

        return redirect()->back()->with('success', 'Riwayat tontonan berhasil dihapus.');
    }

    public function clearHistory()
    {
        Auth::user()->watchHistories()->delete();

        return redirect()->back()->with('success', 'Seluruh riwayat tontonan berhasil dibersihkan.');
    }

    private function compressAndSaveAvatar($file): string
    {
        $filename = 'avatars/' . Str::random(40) . '.jpg';
        $fullPath = storage_path('app/public/' . $filename);

        if (!file_exists(dirname($fullPath))) {
            mkdir(dirname($fullPath), 0755, true);
        }

        if (extension_loaded('gd')) {
            $imageInfo = @getimagesize($file->getRealPath());
            if ($imageInfo) {
                $mime = $imageInfo['mime'];
                $sourceImage = match ($mime) {
                    'image/jpeg' => @imagecreatefromjpeg($file->getRealPath()),
                    'image/png'  => @imagecreatefrompng($file->getRealPath()),
                    'image/webp' => @imagecreatefromwebp($file->getRealPath()),
                    'image/gif'  => @imagecreatefromgif($file->getRealPath()),
                    default      => null,
                };

                if ($sourceImage) {
                    $origWidth  = imagesx($sourceImage);
                    $origHeight = imagesy($sourceImage);
                    $maxSize    = 600;

                    if ($origWidth > $maxSize || $origHeight > $maxSize) {
                        $ratio     = min($maxSize / $origWidth, $maxSize / $origHeight);
                        $newWidth  = (int) round($origWidth * $ratio);
                        $newHeight = (int) round($origHeight * $ratio);

                        $resized = imagecreatetruecolor($newWidth, $newHeight);
                        imagealphablending($resized, false);
                        imagesavealpha($resized, true);
                        imagecopyresampled($resized, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $origWidth, $origHeight);
                        imagedestroy($sourceImage);
                        $sourceImage = $resized;
                    }

                    imagejpeg($sourceImage, $fullPath, 82);
                    imagedestroy($sourceImage);
                    return asset('storage/' . $filename);
                }
            }
        }

        $path = $file->store('avatars', 'public');
        return asset('storage/' . $path);
    }
}
