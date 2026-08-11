<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class AdminAppReleaseController extends Controller
{
    /**
     * Display the APK Release Management page.
     */
    public function index()
    {
        $versionFilePath = public_path('version.json');
        $versionData = [
            'latest_version' => '1.0.0',
            'latest_build_number' => 1,
            'download_url' => url('/download/faiilmov-release.apk'),
            'force_update' => false,
            'release_notes' => 'Rilis perdana aplikasi mobile.',
        ];

        if (File::exists($versionFilePath)) {
            $jsonContent = File::get($versionFilePath);
            $decoded = json_decode($jsonContent, true);
            if (is_array($decoded)) {
                $versionData = array_merge($versionData, $decoded);
            }
        }

        // List files in public/download folder
        $downloadDir = public_path('download');
        $uploadedFiles = [];

        if (File::exists($downloadDir)) {
            $files = File::files($downloadDir);
            foreach ($files as $file) {
                if ($file->getFilename() === '.gitkeep') continue;
                $uploadedFiles[] = [
                    'name' => $file->getFilename(),
                    'size' => $this->formatFileSize($file->getSize()),
                    'size_bytes' => $file->getSize(),
                    'modified_at' => date('d M Y H:i', $file->getMTime()),
                    'url' => asset('download/' . $file->getFilename()),
                ];
            }
        }

        return view('admin.app_release.index', compact('versionData', 'uploadedFiles'));
    }

    /**
     * Store and update APK release information.
     */
    public function store(Request $request)
    {
        $request->validate([
            'version_name' => 'required|string|max:20',
            'build_number' => 'required|integer|min:1',
            'download_url' => 'nullable|url',
            'apk_file'     => 'nullable|file|max:204800', // Max 200MB
            'release_notes'=> 'required|string|max:2000',
        ], [
            'version_name.required' => 'Nama versi (misal 1.0.1) wajib diisi.',
            'build_number.required' => 'Nomor build wajib diisi.',
            'apk_file.max'          => 'Ukuran file APK maksimal adalah 200MB.',
            'release_notes.required'=> 'Catatan pembaruan wajib diisi.',
        ]);

        $versionName = trim($request->input('version_name'));
        $buildNumber = (int) $request->input('build_number');
        $forceUpdate = $request->has('force_update');
        $releaseNotes = trim($request->input('release_notes'));

        $downloadUrl = $request->input('download_url');

        // Handle APK file upload
        if ($request->hasFile('apk_file')) {
            $file = $request->file('apk_file');
            $downloadDir = public_path('download');

            if (!File::exists($downloadDir)) {
                File::makeDirectory($downloadDir, 0755, true);
            }

            $cleanVersion = preg_replace('/[^a-zA-Z0-9\._-]/', '', $versionName);
            $fileName = 'faiilmov-v' . $cleanVersion . '.apk';
            
            $file->move($downloadDir, $fileName);
            $downloadUrl = asset('download/' . $fileName);
        }

        if (empty($downloadUrl)) {
            $downloadUrl = asset('download/faiilmov-v' . $versionName . '.apk');
        }

        // Save config to version.json
        $versionData = [
            'latest_version'      => $versionName,
            'latest_build_number' => $buildNumber,
            'download_url'        => $downloadUrl,
            'force_update'        => $forceUpdate,
            'release_notes'       => $releaseNotes,
            'updated_at'          => date('Y-m-d H:i:s'),
        ];

        File::put(public_path('version.json'), json_encode($versionData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return redirect()->back()->with('success', "Rilis versi {$versionName} (Build {$buildNumber}) berhasil dipublikasikan!");
    }

    /**
     * Delete an uploaded APK file.
     */
    public function destroyFile($filename)
    {
        $filePath = public_path('download/' . basename($filename));
        if (File::exists($filePath)) {
            File::delete($filePath);
            return redirect()->back()->with('success', "File APK {$filename} berhasil dihapus.");
        }

        return redirect()->back()->with('error', 'File tidak ditemukan.');
    }

    private function formatFileSize($bytes)
    {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
}
