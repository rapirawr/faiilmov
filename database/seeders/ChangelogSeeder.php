<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Changelog;

class ChangelogSeeder extends Seeder
{
    public function run(): void
    {
        $changelogs = [
            [
                'version' => 'v2.4.0',
                'title' => 'Pembaruan Komprehensif: Admin Panel CMS, Monitoring Watch Party & Multi-Rating Usia',
                'type' => 'major',
                'release_date' => '2026-08-09',
                'summary' => 'Rilis besar v2.4.0 menghadirkan perombakan total Admin CMS, sistem pengawasan Watch Party real-time, manajemen episode & season series inline, editor rating usia massal, serta halaman legal Kebijakan Privasi & Syarat Ketentuan.',
                'changes' => [
                    ['type' => 'feature', 'text' => 'Peluncuran Admin CMS Kompleks dengan analitik real-time, grafik distribusi tipe konten & rating usia.'],
                    ['type' => 'feature', 'text' => 'Fitur Inline Season & Episode Manager untuk penambahan durasi dan sumber video tayangan per episode.'],
                    ['type' => 'feature', 'text' => 'Sistem monitoring Watch Party real-time dengan opsi Tutup Paksa (Force Close) dan Kirim Pengumuman Admin.'],
                    ['type' => 'feature', 'text' => 'Editor Bulk Content Rating untuk kategorisasi rating usia (SU, G, PG, 13+, 16+, 18+) secara massal.'],
                    ['type' => 'feature', 'text' => 'Peluncuran halaman resmi Kebijakan Privasi & Data Pribadi (UU PDP/GDPR Compliant) dan Syarat & Ketentuan.'],
                    ['type' => 'improvement', 'text' => 'Peningkatan sinkronisasi otomatis Aktor & Cast asli dari MovieBox API.'],
                    ['type' => 'fix', 'text' => 'Perbaikan penanganan pengecekan batasan usia pada Profil Anak.'],
                ],
                'is_published' => true,
            ],
            [
                'version' => 'v2.3.0',
                'title' => 'App Mobile Download Page & Dual Mockup Preview',
                'type' => 'minor',
                'release_date' => '2026-08-05',
                'summary' => 'Menambahkan halaman khusus unduhan aplikasi Android & iOS faiilmov mobile dengan fitur pratinjau antarmuka ganda (Dual Phone Mockup) dan langganan notifikasi peluncuran.',
                'changes' => [
                    ['type' => 'feature', 'text' => 'Halaman Download App Mobile responsif dengan unduhan APK langsung.'],
                    ['type' => 'feature', 'text' => 'Sistem notifikasi pendaftaran minat rilis aplikasi mobile.'],
                    ['type' => 'improvement', 'text' => 'Visualisasi dual-screen phone mockup sinematik.'],
                ],
                'is_published' => true,
            ],
            [
                'version' => 'v2.2.0',
                'title' => 'Isolasi Sub-Profil & PIN Parental Control 4-Digit',
                'type' => 'minor',
                'release_date' => '2026-07-28',
                'summary' => 'Memungkinkan satu akun memiliki hingga 5 sub-profil keluarga independen dengan isolasi riwayat tontonan dan pengamanan PIN Parental Control.',
                'changes' => [
                    ['type' => 'feature', 'text' => 'Dukungan hingga 5 sub-profil per akun utama dengan avatar kustom.'],
                    ['type' => 'feature', 'text' => 'Fitur PIN Parental Control 4-digit untuk mengunci profil dewasa.'],
                    ['type' => 'feature', 'text' => 'Mode Profil Anak (Kids Mode) yang secara otomatis menyaring konten bernilai 13+, 16+, 18+.'],
                    ['type' => 'improvement', 'text' => 'Isolasi mutlak data Lanjut Nonton (Continue Watching) dan rekomendasi antar profil.'],
                ],
                'is_published' => true,
            ],
            [
                'version' => 'v2.0.0',
                'title' => 'Nonton Bareng (Watch Party) & Sync Real-Time Multi-User',
                'type' => 'major',
                'release_date' => '2026-07-10',
                'summary' => 'Memungkinkan pemutaran film secara bersamaan dalam ruangan virtual dengan obrolan teks real-time dan kontrol sinkronisasi otomatis.',
                'changes' => [
                    ['type' => 'feature', 'text' => 'Sistem Watch Party dengan kode ruang unik 6-karakter.'],
                    ['type' => 'feature', 'text' => 'Sinkronisasi posisi detik tayang, play/pause, dan kecepatan pemutaran otomatis.'],
                    ['type' => 'feature', 'text' => 'Ruang obrolan langsung dan reaksi emotikon saat menonton.'],
                ],
                'is_published' => true,
            ],
            [
                'version' => 'v1.0.0',
                'title' => 'Peluncuran Perdana Platform Streaming faiilmov',
                'type' => 'major',
                'release_date' => '2026-06-01',
                'summary' => 'Rilis awal platform faiilmov dengan pemutar video HLS/MP4, pencarian pintar berbasis AI, dan katalog film/series lengkap.',
                'changes' => [
                    ['type' => 'feature', 'text' => 'Pemutar video HTML5 kustom dengan pilihan resolusi hingga 4K Ultra HD.'],
                    ['type' => 'feature', 'text' => 'Pencarian pintar AI Autocomplete dan pencarian bahasa alami.'],
                    ['type' => 'feature', 'text' => 'Katalog film dan series TV dengan data sinopsis dan rating IMDb.'],
                ],
                'is_published' => true,
            ],
        ];

        foreach ($changelogs as $data) {
            Changelog::updateOrCreate(
                ['version' => $data['version']],
                $data
            );
        }
    }
}
