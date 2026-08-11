<?php

namespace App\Http\Controllers;

use App\Models\AppLaunchNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class DownloadAppController extends Controller
{
    /**
     * Display the mobile app landing & download page.
     */
    public function index()
    {
        $versionFilePath = public_path('version.json');
        $versionData = [
            'latest_version' => '1.0.0',
            'latest_build_number' => 1,
            'download_url' => url('/apk-files/faiilmov-release.apk'),
            'force_update' => false,
            'release_notes' => 'Rilis perdana aplikasi mobile faiilmov.',
        ];

        if (File::exists($versionFilePath)) {
            $jsonContent = File::get($versionFilePath);
            $decoded = json_decode($jsonContent, true);
            if (is_array($decoded)) {
                $versionData = array_merge($versionData, $decoded);
            }
        }

        if (isset($versionData['download_url'])) {
            $versionData['download_url'] = preg_replace('/^https:\/\/(127\.0\.0\.1|localhost)/', 'http://$1', $versionData['download_url']);
        }

        // Check for latest uploaded APK file in public/apk-files
        $apkFile = null;
        $downloadDir = public_path('apk-files');
        if (File::exists($downloadDir)) {
            $files = File::files($downloadDir);
            $latestMtime = 0;
            foreach ($files as $file) {
                if (strtolower($file->getExtension()) === 'apk') {
                    if ($file->getMTime() > $latestMtime) {
                        $latestMtime = $file->getMTime();
                        $apkUrl = asset('apk-files/' . $file->getFilename());
                        $apkUrl = preg_replace('/^https:\/\/(127\.0\.0\.1|localhost)/', 'http://$1', $apkUrl);

                        // Read metadata directly from APK file
                        $apkMeta = \App\Services\ApkParser::parse($file->getRealPath());

                        if (!empty($apkMeta['version_name'])) {
                            $versionData['latest_version'] = $apkMeta['version_name'];
                        }
                        if (!empty($apkMeta['build_number'])) {
                            $versionData['latest_build_number'] = (int) $apkMeta['build_number'];
                        }

                        $apkFile = [
                            'name' => $file->getFilename(),
                            'size' => round($file->getSize() / (1024 * 1024), 1) . ' MB',
                            'url' => $apkUrl,
                            'version_name' => $apkMeta['version_name'] ?? null,
                            'build_number' => $apkMeta['build_number'] ?? null,
                        ];
                    }
                }
            }
        }

        return view('download-app', compact('versionData', 'apkFile'));
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
