<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminApiTesterController extends Controller
{
    /**
     * Get array of all API endpoint specifications
     */
    private function getEndpoints(): array
    {
        $baseUrl = url('/api/v1');

        return [
            [
                'group' => 'Authentication & User Account',
                'name' => 'User Login',
                'method' => 'POST',
                'path' => '/login',
                'description' => 'Autentikasi pengguna dan menghasilkan token akses Base64.',
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['email' => 'support@faiilmov.my.id', 'password' => 'password123'], JSON_PRETTY_PRINT),
                'queryParams' => [],
                'auth' => false,
            ],
            [
                'group' => 'Authentication & User Account',
                'name' => 'User Register',
                'method' => 'POST',
                'path' => '/register',
                'description' => 'Mendaftarkan akun pengguna baru.',
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['name' => 'Pengguna Baru', 'email' => 'user' . rand(100, 999) . '@faiilmov.my.id', 'password' => 'password123'], JSON_PRETTY_PRINT),
                'queryParams' => [],
                'auth' => false,
            ],
            [
                'group' => 'Authentication & User Account',
                'name' => 'Current User Profile',
                'method' => 'GET',
                'path' => '/user',
                'description' => 'Mendapatkan data profil dan statistik pengguna saat ini.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => true,
            ],
            [
                'group' => 'Authentication & User Account',
                'name' => 'Update Profile',
                'method' => 'POST',
                'path' => '/user/profile',
                'description' => 'Memperbarui nama, bio, nomor telepon, atau avatar pengguna.',
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['name' => 'Admin Faiilmov', 'bio' => 'Movie Enthusiast', 'phone' => '081234567890'], JSON_PRETTY_PRINT),
                'queryParams' => [],
                'auth' => true,
            ],
            [
                'group' => 'Authentication & User Account',
                'name' => 'Change Password',
                'method' => 'POST',
                'path' => '/user/change-password',
                'description' => 'Mengubah kata sandi pengguna.',
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['current_password' => 'password123', 'password' => 'newpassword123', 'password_confirmation' => 'newpassword123'], JSON_PRETTY_PRINT),
                'queryParams' => [],
                'auth' => true,
            ],
            [
                'group' => 'Authentication & User Account',
                'name' => 'List All Users (Admin)',
                'method' => 'GET',
                'path' => '/users',
                'description' => 'Mendapatkan daftar seluruh pengguna (paginated).',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => ['limit' => '20', 'page' => '1'],
                'auth' => true,
            ],

            [
                'group' => 'Multi-Profile Management',
                'name' => 'List Sub-Profiles',
                'method' => 'GET',
                'path' => '/profiles',
                'description' => 'Mendapatkan daftar profil anak/sub-profil milik pengguna.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => true,
            ],
            [
                'group' => 'Multi-Profile Management',
                'name' => 'Create Sub-Profile',
                'method' => 'POST',
                'path' => '/profiles',
                'description' => 'Membuat profil baru (misal: profil anak-anak).',
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['name' => 'Profil Anak', 'is_child' => true, 'pin' => '1234'], JSON_PRETTY_PRINT),
                'queryParams' => [],
                'auth' => true,
            ],

            [
                'group' => 'Catalog & Media',
                'name' => 'List Movies & Series',
                'method' => 'GET',
                'path' => '/movies',
                'description' => 'Mendapatkan katalog film/series dengan filter type, genre, & limit.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => ['type' => 'all', 'genre' => 'All', 'limit' => '20'],
                'auth' => false,
            ],
            [
                'group' => 'Catalog & Media',
                'name' => 'Featured Movies',
                'method' => 'GET',
                'path' => '/movies/featured',
                'description' => 'Mendapatkan daftar film unggulan / banner utama.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],
            [
                'group' => 'Catalog & Media',
                'name' => 'Trending Movies',
                'method' => 'GET',
                'path' => '/movies/trending',
                'description' => 'Mendapatkan film yang sedang populer/trending.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],
            [
                'group' => 'Catalog & Media',
                'name' => 'Popular Series',
                'method' => 'GET',
                'path' => '/movies/popular-series',
                'description' => 'Mendapatkan serial TV terpopuler.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],
            [
                'group' => 'Catalog & Media',
                'name' => 'Movie Detail',
                'method' => 'GET',
                'path' => '/movies/1',
                'description' => 'Mendapatkan rincian film berdasarkan ID beserta genre & aktor.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],

            [
                'group' => 'Seasons & Episodes',
                'name' => 'Movie Seasons List',
                'method' => 'GET',
                'path' => '/movies/1/seasons',
                'description' => 'Mendapatkan daftar musim (seasons) untuk serial film ID 1.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],
            [
                'group' => 'Seasons & Episodes',
                'name' => 'Season Detail & Episodes',
                'method' => 'GET',
                'path' => '/seasons/1',
                'description' => 'Mendapatkan rincian season ID 1 beserta episode di dalamnya.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],
            [
                'group' => 'Seasons & Episodes',
                'name' => 'Episode Detail',
                'method' => 'GET',
                'path' => '/episodes/1',
                'description' => 'Mendapatkan detail episode ID 1 beserta link episode berikutnya.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],

            [
                'group' => 'Genres & Actors',
                'name' => 'List Genres',
                'method' => 'GET',
                'path' => '/genres',
                'description' => 'Mendapatkan seluruh genre film yang ada.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],
            [
                'group' => 'Genres & Actors',
                'name' => 'List Actors',
                'method' => 'GET',
                'path' => '/actors',
                'description' => 'Mendapatkan daftar aktor / cast terdaftar.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => ['search' => '', 'limit' => '20'],
                'auth' => false,
            ],
            [
                'group' => 'Genres & Actors',
                'name' => 'Actor Detail & Movies',
                'method' => 'GET',
                'path' => '/actors/1',
                'description' => 'Mendapatkan rincian aktor ID 1 dan filmografinya.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],

            [
                'group' => 'Search & Analytics',
                'name' => 'Search Movies',
                'method' => 'GET',
                'path' => '/search',
                'description' => 'Pencarian kata kunci film dengan pencatatan log otomatis.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => ['q' => 'action'],
                'auth' => false,
            ],
            [
                'group' => 'Search & Analytics',
                'name' => 'Popular Search Queries',
                'method' => 'GET',
                'path' => '/search/popular',
                'description' => 'Mendapatkan istilah pencarian yang paling sering dicari.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],

            [
                'group' => 'Watchlist & History',
                'name' => 'Get Watchlist',
                'method' => 'GET',
                'path' => '/watchlist',
                'description' => 'Mendapatkan daftar simpanan watchlist pengguna.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => true,
            ],
            [
                'group' => 'Watchlist & History',
                'name' => 'Add to Watchlist',
                'method' => 'POST',
                'path' => '/watchlist',
                'description' => 'Menambahkan film ke watchlist.',
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['film_id' => 1], JSON_PRETTY_PRINT),
                'queryParams' => [],
                'auth' => true,
            ],
            [
                'group' => 'Watchlist & History',
                'name' => 'Get Watch History',
                'method' => 'GET',
                'path' => '/watch-history',
                'description' => 'Mendapatkan riwayat dan progress tontonan film pengguna.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => true,
            ],
            [
                'group' => 'Watchlist & History',
                'name' => 'Update Watch Progress',
                'method' => 'POST',
                'path' => '/watch-history',
                'description' => 'Memperbarui posisi waktu tontonan film pengguna (detik).',
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['film_id' => 1, 'progress_seconds' => 320, 'completed' => false], JSON_PRETTY_PRINT),
                'queryParams' => [],
                'auth' => true,
            ],

            [
                'group' => 'Reviews & Moderation',
                'name' => 'Get Movie Reviews',
                'method' => 'GET',
                'path' => '/movies/1/reviews',
                'description' => 'Mendapatkan ulasan pengguna untuk film ID 1.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],
            [
                'group' => 'Reviews & Moderation',
                'name' => 'Post Movie Review',
                'method' => 'POST',
                'path' => '/movies/1/reviews',
                'description' => 'Mengirim ulasan & rating bintang untuk film ID 1.',
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['rating' => 5, 'comment' => 'Film yang luar biasa dan sangat seru!'], JSON_PRETTY_PRINT),
                'queryParams' => [],
                'auth' => true,
            ],
            [
                'group' => 'Reviews & Moderation',
                'name' => 'Report Review',
                'method' => 'POST',
                'path' => '/reviews/1/report',
                'description' => 'Melaporkan ulasan yang tidak pantas atau mengandung spoiler.',
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['reason' => 'Mengandung kata-kata kasar / spoiler.'], JSON_PRETTY_PRINT),
                'queryParams' => [],
                'auth' => true,
            ],
            [
                'group' => 'Reviews & Moderation',
                'name' => 'List Review Reports (Admin)',
                'method' => 'GET',
                'path' => '/admin/reviews/reports',
                'description' => 'Mendapatkan daftar laporan ulasan terdaftar untuk admin.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => ['limit' => '20'],
                'auth' => true,
            ],

            [
                'group' => 'Notifications & Announcements',
                'name' => 'Get Notifications',
                'method' => 'GET',
                'path' => '/notifications',
                'description' => 'Mendapatkan daftar notifikasi milik pengguna saat ini.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => ['limit' => '20'],
                'auth' => true,
            ],
            [
                'group' => 'Notifications & Announcements',
                'name' => 'App Launch Notifications',
                'method' => 'GET',
                'path' => '/app-launch-notifications',
                'description' => 'Mendapatkan daftar pendaftar notifikasi peluncuran aplikasi.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],

            [
                'group' => 'System Settings & Changelogs',
                'name' => 'App Settings',
                'method' => 'GET',
                'path' => '/settings',
                'description' => 'Mendapatkan variabel & konfigurasi aplikasi publik.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],
            [
                'group' => 'System Settings & Changelogs',
                'name' => 'App Changelogs',
                'method' => 'GET',
                'path' => '/changelogs',
                'description' => 'Mendapatkan daftar rilis catatan perubahan (release notes).',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],
            [
                'group' => 'System Settings & Changelogs',
                'name' => 'Latest Changelog',
                'method' => 'GET',
                'path' => '/changelogs/latest',
                'description' => 'Mendapatkan versi rilis changelog terbaru.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],
            [
                'group' => 'System Settings & Changelogs',
                'name' => 'Admin Activity Audit Logs',
                'method' => 'GET',
                'path' => '/admin/activity-logs',
                'description' => 'Mendapatkan riwayat audit log aktivitas admin.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => true,
            ],

            [
                'group' => 'Watch Party API',
                'name' => 'Create Watch Party Room',
                'method' => 'POST',
                'path' => '/watch-party/create',
                'description' => 'Membuat room nonton bareng baru.',
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['film_id' => 1, 'guest_name' => 'Host Room'], JSON_PRETTY_PRINT),
                'queryParams' => [],
                'auth' => true,
            ],
            [
                'group' => 'Watch Party API',
                'name' => 'Get Room Detail',
                'method' => 'GET',
                'path' => '/watch-party/ROOMCODE',
                'description' => 'Mendapatkan status room & daftar pesertanya.',
                'headers' => ['Accept' => 'application/json'],
                'body' => '',
                'queryParams' => [],
                'auth' => false,
            ],
            [
                'group' => 'Watch Party API',
                'name' => 'Send Room Chat Message',
                'method' => 'POST',
                'path' => '/watch-party/ROOMCODE/message',
                'description' => 'Mengirim pesan obrolan di room nonton bareng.',
                'headers' => ['Content-Type' => 'application/json'],
                'body' => json_encode(['message' => 'Halo semua! Selamat menonton bersama!'], JSON_PRETTY_PRINT),
                'queryParams' => [],
                'auth' => true,
            ],
        ];
    }

    public function index()
    {
        $endpoints = $this->getEndpoints();

        // Group endpoints by group name
        $grouped = [];
        foreach ($endpoints as $ep) {
            $grouped[$ep['group']][] = $ep;
        }

        return view('admin.api_tester.index', [
            'endpoints' => $endpoints,
            'groupedEndpoints' => $grouped,
            'baseUrl' => url('/api/v1'),
        ]);
    }

    /**
     * Download Postman Collection v2.1 JSON file
     */
    public function exportPostman()
    {
        $endpoints = $this->getEndpoints();
        $baseUrl = url('/api/v1');

        $collection = [
            'info' => [
                'name' => 'Faiilmov API Collection v1',
                '_postman_id' => 'faiilmov-api-collection-v1',
                'description' => 'Kumpulan REST API lengkap untuk aplikasi Faiilmov (Katalog, Auth, Season/Episode, Multi-Profile, Watchlist, Watch Party, Notifikasi, dsb).',
                'schema' => 'https://schema.getpostman.com/json/collection/v2.1.0/collection.json',
            ],
            'item' => [],
        ];

        $grouped = [];
        foreach ($endpoints as $ep) {
            $grouped[$ep['group']][] = $ep;
        }

        foreach ($grouped as $groupName => $groupEndpoints) {
            $folderItems = [];

            foreach ($groupEndpoints as $ep) {
                $fullUrl = $baseUrl . $ep['path'];
                $pathSegments = array_values(array_filter(explode('/', $ep['path'])));

                $queryVars = [];
                foreach ($ep['queryParams'] as $k => $v) {
                    $queryVars[] = ['key' => $k, 'value' => $v];
                }

                $headerList = [];
                foreach ($ep['headers'] as $hk => $hv) {
                    $headerList[] = ['key' => $hk, 'value' => $hv];
                }

                if ($ep['auth']) {
                    $headerList[] = [
                        'key' => 'Authorization',
                        'value' => 'Bearer {{API_TOKEN}}',
                        'type' => 'text',
                    ];
                }

                $requestData = [
                    'method' => $ep['method'],
                    'header' => $headerList,
                    'url' => [
                        'raw' => $fullUrl,
                        'protocol' => parse_url($baseUrl, PHP_URL_SCHEME) ?? 'http',
                        'host' => explode('.', parse_url($baseUrl, PHP_URL_HOST) ?? 'localhost'),
                        'path' => array_merge(['api', 'v1'], $pathSegments),
                        'query' => $queryVars,
                    ],
                    'description' => $ep['description'],
                ];

                if ($ep['method'] !== 'GET' && !empty($ep['body'])) {
                    $requestData['body'] = [
                        'mode' => 'raw',
                        'raw' => $ep['body'],
                        'options' => [
                            'raw' => [
                                'language' => 'json',
                            ],
                        ],
                    ];
                }

                $folderItems[] = [
                    'name' => $ep['name'],
                    'request' => $requestData,
                    'response' => [],
                ];
            }

            $collection['item'][] = [
                'name' => $groupName,
                'item' => $folderItems,
            ];
        }

        $fileName = 'faiilmov_api_postman_collection.json';
        $jsonOutput = json_encode($collection, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        return response($jsonOutput, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
