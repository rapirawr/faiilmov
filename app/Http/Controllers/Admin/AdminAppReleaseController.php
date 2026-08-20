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
            'download_url' => url('/apk-files/faiilmov-release.apk'),
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

        if (isset($versionData['download_url'])) {
            $versionData['download_url'] = preg_replace('/^https?:\/\/(127\.0\.0\.1|localhost)(:\d+)?/', request()->root(), $versionData['download_url']);
        }

        // List files in public/apk-files folder
        $downloadDir = public_path('apk-files');
        $uploadedFiles = [];

        if (File::exists($downloadDir)) {
            $files = File::files($downloadDir);
            $latestMtime = 0;
            $latestFile = null;

            foreach ($files as $file) {
                if ($file->getFilename() === '.gitkeep') continue;

                if (strtolower($file->getExtension()) === 'apk') {
                    if ($file->getMTime() > $latestMtime) {
                        $latestMtime = $file->getMTime();
                        $latestFile = $file;
                    }
                }

                $fileUrl = asset('apk-files/' . $file->getFilename());
                $fileUrl = preg_replace('/^https?:\/\/(127\.0\.0\.1|localhost)(:\d+)?/', request()->root(), $fileUrl);
                $uploadedFiles[] = [
                    'name' => $file->getFilename(),
                    'size' => $this->formatFileSize($file->getSize()),
                    'size_bytes' => $file->getSize(),
                    'modified_at' => date('d M Y H:i', $file->getMTime()),
                    'url' => $fileUrl,
                ];
            }

            if ($latestFile) {
                $apkMeta = \App\Services\ApkParser::parse($latestFile->getRealPath());
                if (!empty($apkMeta['version_name'])) {
                    $versionData['latest_version'] = $apkMeta['version_name'];
                }
                if (!empty($apkMeta['build_number'])) {
                    $versionData['latest_build_number'] = (int) $apkMeta['build_number'];
                }
            }
        }

        return view('admin.app_release.index', compact('versionData', 'uploadedFiles'));
    }

    /**
     * Store and update APK release information.
     */
    public function store(Request $request)
    {
        // 1. Detect if payload exceeded PHP post_max_size before reaching Laravel
        $contentLength = (int) ($request->server('CONTENT_LENGTH') ?? 0);
        if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
            $postMaxSize = ini_get('post_max_size');
            $uploadMaxFilesize = ini_get('upload_max_filesize');
            
            \Illuminate\Support\Facades\Log::error('APK Upload Gagal: Payload melebihi post_max_size PHP.', [
                'content_length_bytes' => $contentLength,
                'content_length_mb'    => round($contentLength / 1048576, 2) . 'MB',
                'post_max_size'        => $postMaxSize,
                'upload_max_filesize'  => $uploadMaxFilesize,
            ]);

            $errorMsg = "Ukuran total request (" . round($contentLength / 1048576, 1) . "MB) melebihi batas post_max_size PHP ({$postMaxSize}) pada server.";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errorMsg, 'errors' => ['apk_file' => [$errorMsg]]], 422);
            }
            return redirect()->back()->with('error', $errorMsg);
        }

        // 2. Validate request
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'version_name' => 'nullable|string|max:20',
            'build_number' => 'nullable|integer|min:1',
            'apk_file'     => ['nullable', 'file', 'max:204800', function ($attribute, $value, $fail) {
                if ($value instanceof \Illuminate\Http\UploadedFile) {
                    $ext = strtolower($value->getClientOriginalExtension());
                    if (!in_array($ext, ['apk', 'zip'], true)) {
                        $fail('File yang diunggah harus berekstensi .apk atau .zip.');
                        return;
                    }
                    // Validate ZIP/APK container magic bytes (starts with 'PK\x03\x04' or 'PK')
                    $handle = @fopen($value->getRealPath(), 'rb');
                    if ($handle) {
                        $header = fread($handle, 4);
                        fclose($handle);
                        if (!str_starts_with($header, "PK")) {
                            $fail('Struktur file APK/ZIP tidak valid atau rusak.');
                            return;
                        }
                    }
                }
            }],
            'release_notes'=> 'required|string|max:2000',
        ], [
            'apk_file.file'         => 'File yang diunggah bukan file valid.',
            'apk_file.max'          => 'Ukuran file APK maksimal adalah 200MB.',
            'release_notes.required'=> 'Catatan pembaruan wajib diisi.',
        ]);

        if ($validator->fails()) {
            $firstError = $validator->errors()->first();

            // Detailed check if upload error code exists on uploaded file
            if ($request->hasFile('apk_file')) {
                $file = $request->file('apk_file');
                if (!$file->isValid()) {
                    $errCode = $file->getError();
                    $errMap = [
                        1 => 'Ukuran file APK melebihi upload_max_filesize (' . ini_get('upload_max_filesize') . ') di php.ini server.',
                        2 => 'Ukuran file APK melebihi batas form HTML.',
                        3 => 'File APK hanya terunggah sebagian (koneksi terputus).',
                        4 => 'Tidak ada file APK yang terunggah.',
                        6 => 'Folder temporary PHP (sys_temp_dir) tidak ditemukan di server.',
                        7 => 'Gagal menulis file APK ke disk server (periksa sisa storage / write permission).',
                        8 => 'Pengunggahan file dihentikan oleh ekstensi PHP.',
                    ];
                    $firstError = $errMap[$errCode] ?? "Gagal mengunggah file APK (Error Code: {$errCode}).";

                    \Illuminate\Support\Facades\Log::error("APK Upload Gagal: UploadedFile invalid with code {$errCode}", [
                        'upload_error_code' => $errCode,
                        'file_name'         => $file->getClientOriginalName(),
                        'client_size'       => $file->getSize(),
                        'upload_max_size'   => ini_get('upload_max_filesize'),
                        'post_max_size'     => ini_get('post_max_size'),
                    ]);
                }
            } else {
                \Illuminate\Support\Facades\Log::warning('APK Upload Validation Failed', [
                    'errors' => $validator->errors()->toArray(),
                ]);
            }

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $firstError,
                    'errors'  => $validator->errors()->toArray()
                ], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput();
        }

        try {
            $versionFilePath = public_path('version.json');
            $existingData = [
                'latest_version' => '1.0.0',
                'latest_build_number' => 1,
                'download_url' => url('/apk-files/faiilmov-release.apk'),
            ];
            if (File::exists($versionFilePath)) {
                $jsonContent = File::get($versionFilePath);
                $decoded = json_decode($jsonContent, true);
                if (is_array($decoded)) {
                    $existingData = array_merge($existingData, $decoded);
                }
            }

            $versionName = trim($request->input('version_name') ?? '');
            $buildNumber = $request->filled('build_number') ? (int) $request->input('build_number') : null;
            $forceUpdate = $request->has('force_update');
            $releaseNotes = trim($request->input('release_notes'));
            $downloadUrl = $request->input('download_url');

            // Handle APK file upload and auto-extract version & build number
            if ($request->hasFile('apk_file')) {
                $file = $request->file('apk_file');
                $downloadDir = public_path('apk-files');

                if (!File::exists($downloadDir)) {
                    File::makeDirectory($downloadDir, 0755, true);
                }

                // Extract versionName and versionCode directly from uploaded APK binary metadata
                $apkMeta = [];
                try {
                    $apkMeta = \App\Services\ApkParser::parse($file->getRealPath());
                } catch (\Throwable $eParser) {
                    \Illuminate\Support\Facades\Log::warning('APK Parser Notice: ' . $eParser->getMessage());
                }
                
                if (!empty($apkMeta['version_name'])) {
                    $versionName = $apkMeta['version_name'];
                }
                if (!empty($apkMeta['build_number'])) {
                    $buildNumber = (int) $apkMeta['build_number'];
                }

                // Fallback if APK parser didn't find versionName
                if (empty($versionName)) {
                    $versionName = $existingData['latest_version'];
                }
                if (empty($buildNumber)) {
                    $buildNumber = $existingData['latest_build_number'] + 1;
                }

                $cleanVersion = preg_replace('/[^a-zA-Z0-9\._-]/', '', $versionName);
                $fileName = 'faiilmov-v' . $cleanVersion . '.apk';
                
                $file->move($downloadDir, $fileName);
                $downloadUrl = asset('apk-files/' . $fileName);

                \Illuminate\Support\Facades\Log::info("APK Upload Sukses: {$fileName}", [
                    'original_name' => $file->getClientOriginalName(),
                    'saved_file'    => $fileName,
                    'version_name'  => $versionName,
                    'build_number'  => $buildNumber,
                    'file_size'     => $this->formatFileSize(File::size($downloadDir . '/' . $fileName)),
                ]);
            }

            // Final fallback if no file was uploaded and inputs were left empty
            if (empty($versionName)) {
                $versionName = $existingData['latest_version'];
            }
            if (empty($buildNumber)) {
                $buildNumber = $existingData['latest_build_number'];
            }

            if (empty($downloadUrl)) {
                $downloadUrl = asset('apk-files/faiilmov-v' . $versionName . '.apk');
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

            $msg = "Rilis versi {$versionName} (Build {$buildNumber}) berhasil dipublikasikan!";

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => $msg,
                    'version' => $versionName,
                    'build'   => $buildNumber,
                ]);
            }

            return redirect()->back()->with('success', $msg);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('APK Upload Storage Exception: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            $errMessage = 'Gagal menyimpan file APK ke server: ' . $e->getMessage();

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => $errMessage], 500);
            }

            return redirect()->back()->with('error', $errMessage);
        }
    }

    /**
     * Delete an uploaded APK file.
     */
    public function destroyFile($filename)
    {
        $filePath = public_path('apk-files/' . basename($filename));
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
