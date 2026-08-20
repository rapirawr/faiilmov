<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Badge;

class BadgeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $badges = [
            // Milestones
            [
                'code'           => 'first_watch',
                'name'           => 'First Reel',
                'description'    => 'Memulai petualangan sinematik dengan menonton film pertama di faiilmov.',
                'category'       => 'milestone',
                'icon'           => 'clapperboard',
                'color'          => 'emerald',
                'xp_reward'      => 50,
                'required_count' => 1,
            ],
            [
                'code'           => 'film_century',
                'name'           => 'Century Club',
                'description'    => 'Mencapai rekor menonton lebih dari 100 film atau episode di faiilmov.',
                'category'       => 'milestone',
                'icon'           => 'trophy',
                'color'          => 'amber',
                'xp_reward'      => 500,
                'required_count' => 100,
            ],
            [
                'code'           => 'early_adopter',
                'name'           => 'Pioneer Cinephile',
                'description'    => 'Pengguna awal dan penjelajah setia platform sinema faiilmov.',
                'category'       => 'milestone',
                'icon'           => 'shield-check',
                'color'          => 'cyan',
                'xp_reward'      => 100,
                'required_count' => 1,
            ],

            // Genre Mastery
            [
                'code'           => 'horror_enthusiast',
                'name'           => 'Nightmare Seeker',
                'description'    => 'Menonton minimal 5 film atau serial bergenre horor dan thriller mencekam.',
                'category'       => 'genre',
                'icon'           => 'skull',
                'color'          => 'rose',
                'xp_reward'      => 120,
                'required_count' => 5,
            ],
            [
                'code'           => 'anime_master',
                'name'           => 'Anime Maven',
                'description'    => 'Menonton minimal 5 judul anime pilihan di faiilmov.',
                'category'       => 'genre',
                'icon'           => 'sparkles',
                'color'          => 'purple',
                'xp_reward'      => 120,
                'required_count' => 5,
            ],
            [
                'code'           => 'kdrama_lover',
                'name'           => 'K-Drama Connoisseur',
                'description'    => 'Menonton minimal 5 judul serial drama Asia / Korea.',
                'category'       => 'genre',
                'icon'           => 'heart',
                'color'          => 'pink',
                'xp_reward'      => 120,
                'required_count' => 5,
            ],
            [
                'code'           => 'scifi_voyager',
                'name'           => 'Sci-Fi Voyager',
                'description'    => 'Menonton minimal 5 film bertema fiksi ilmiah, antariksa, atau teknologi masa depan.',
                'category'       => 'genre',
                'icon'           => 'rocket',
                'color'          => 'cyan',
                'xp_reward'      => 120,
                'required_count' => 5,
            ],
            [
                'code'           => 'action_buff',
                'name'           => 'Action Thrillseeker',
                'description'    => 'Menonton minimal 5 film aksi beradrenalin tinggi.',
                'category'       => 'genre',
                'icon'           => 'flame',
                'color'          => 'amber',
                'xp_reward'      => 120,
                'required_count' => 5,
            ],

            // Habits & Streaks
            [
                'code'           => 'midnight_owl',
                'name'           => 'Midnight Owl',
                'description'    => 'Menonton tayangan di jam sunyi tengah malam (00:00 - 04:00 WIB).',
                'category'       => 'habit',
                'icon'           => 'moon',
                'color'          => 'indigo',
                'xp_reward'      => 80,
                'required_count' => 1,
            ],
            [
                'code'           => 'streak_3_days',
                'name'           => 'Streak Explorer',
                'description'    => 'Mempertahankan konsistensi menonton film selama 3 hari berturut-turut.',
                'category'       => 'habit',
                'icon'           => 'flame',
                'color'          => 'amber',
                'xp_reward'      => 150,
                'required_count' => 3,
            ],
            [
                'code'           => 'streak_7_days',
                'name'           => 'Streak Grandmaster',
                'description'    => 'Mempertahankan konsistensi menonton film selama 7 hari berturut-turut tanpa jeda.',
                'category'       => 'habit',
                'icon'           => 'zap',
                'color'          => 'yellow',
                'xp_reward'      => 350,
                'required_count' => 7,
            ],
            [
                'code'           => 'binge_champion',
                'name'           => 'Marathon Binger',
                'description'    => 'Menuntaskan lebih dari 5 episode atau film dalam rentang satu hari.',
                'category'       => 'habit',
                'icon'           => 'tv',
                'color'          => 'emerald',
                'xp_reward'      => 200,
                'required_count' => 5,
            ],

            // Community & Social
            [
                'code'           => 'party_goer',
                'name'           => 'Watch Party Squad',
                'description'    => 'Membuat atau bergabung dalam sesi nonton bersama di ruang Watch Party.',
                'category'       => 'community',
                'icon'           => 'users',
                'color'          => 'violet',
                'xp_reward'      => 100,
                'required_count' => 1,
            ],
            [
                'code'           => 'film_critic',
                'name'           => 'Certified Critic',
                'description'    => 'Membagikan minimal 3 ulasan film untuk membantu komunitas.',
                'category'       => 'community',
                'icon'           => 'pen-tool',
                'color'          => 'blue',
                'xp_reward'      => 150,
                'required_count' => 3,
            ],
            [
                'code'           => 'discussion_starter',
                'name'           => 'Active Commenter',
                'description'    => 'Mengirimkan minimal 5 komentar diskusi pada episode yang ditonton.',
                'category'       => 'community',
                'icon'           => 'message-square',
                'color'          => 'teal',
                'xp_reward'      => 100,
                'required_count' => 5,
            ],
            [
                'code'           => 'collection_curator',
                'name'           => 'Collection Curator',
                'description'    => 'Membuat playlist atau urutan tontonan kustom di fitur Koleksi.',
                'category'       => 'community',
                'icon'           => 'folder-heart',
                'color'          => 'fuchsia',
                'xp_reward'      => 150,
                'required_count' => 1,
            ],
        ];

        foreach ($badges as $badge) {
            Badge::updateOrCreate(['code' => $badge['code']], $badge);
        }
    }
}
