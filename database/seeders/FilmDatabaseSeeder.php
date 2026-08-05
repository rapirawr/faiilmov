<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Genre;
use App\Models\Film;
use App\Models\Actor;
use App\Models\User;
use App\Models\Review;
use Illuminate\Support\Str;

class FilmDatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Genres
        $genresData = [
            'Action', 'Adventure', 'Animation', 'Comedy', 'Crime',
            'Drama', 'Fantasy', 'Horror', 'Mystery', 'Sci-Fi', 'Thriller'
        ];

        $genres = [];
        foreach ($genresData as $name) {
            $genres[$name] = Genre::firstOrCreate([
                'name' => $name,
                'slug' => Str::slug($name),
            ]);
        }

        // 2. Create Actors
        $actorsData = [
            ['name' => 'Sam Worthington', 'photo' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?q=80&w=300'],
            ['name' => 'Zoe Saldana', 'photo' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=300'],
            ['name' => 'Leonardo DiCaprio', 'photo' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?q=80&w=300'],
            ['name' => 'Christian Bale', 'photo' => 'https://images.unsplash.com/photo-1492562080023-ab3db95bfbce?q=80&w=300'],
            ['name' => 'Cillian Murphy', 'photo' => 'https://images.unsplash.com/photo-1522075469751-3a6694fb2f61?q=80&w=300'],
        ];

        $actors = [];
        foreach ($actorsData as $a) {
            $actors[$a['name']] = Actor::firstOrCreate([
                'name' => $a['name'],
                'slug' => Str::slug($a['name']),
                'photo_url' => $a['photo'],
            ]);
        }

        // 3. Create Sample Films with MovieBox Subject IDs
        $filmsData = [
            [
                'moviebox_subject_id' => '3568681204117373120',
                'title' => 'Avatar: The Way of Water',
                'synopsis' => 'Jake Sully lives with his newfound family formed on the extrasolar moon Pandora. Once a familiar threat returns to finish what was previously started, Jake must work with Neytiri and the army of the Na\'vi race to protect their home.',
                'release_year' => 2022,
                'duration_minutes' => 192,
                'rating' => 4.8,
                'poster_url' => 'https://images.unsplash.com/photo-1536440136628-849c177e76a1?q=80&w=600',
                'backdrop_url' => 'https://images.unsplash.com/photo-1518676599602-2170de9df05d?q=80&w=1200',
                'trailer_url' => 'https://www.youtube.com/watch?v=d9MyW72ELq0',
                'subject_type' => 'movie',
                'genres' => ['Action', 'Adventure', 'Sci-Fi', 'Fantasy'],
                'cast' => ['Sam Worthington', 'Zoe Saldana'],
            ],
            [
                'moviebox_subject_id' => '12839120491023',
                'title' => 'Inception',
                'synopsis' => 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O., but his tragic past may doom the project and his team to disaster.',
                'release_year' => 2010,
                'duration_minutes' => 148,
                'rating' => 4.9,
                'poster_url' => 'https://images.unsplash.com/photo-1440404653325-ab127d49abc1?q=80&w=600',
                'backdrop_url' => 'https://images.unsplash.com/photo-1478760329108-5c3ed9d495a0?q=80&w=1200',
                'trailer_url' => 'https://www.youtube.com/watch?v=YoHD9XEInc0',
                'subject_type' => 'movie',
                'genres' => ['Action', 'Sci-Fi', 'Thriller'],
                'cast' => ['Leonardo DiCaprio', 'Cillian Murphy'],
            ],
            [
                'moviebox_subject_id' => '9823019481230',
                'title' => 'The Dark Knight',
                'synopsis' => 'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.',
                'release_year' => 2008,
                'duration_minutes' => 152,
                'rating' => 5.0,
                'poster_url' => 'https://images.unsplash.com/photo-1509198397868-475647b2a1e5?q=80&w=600',
                'backdrop_url' => 'https://images.unsplash.com/photo-1534447677768-be436bb09401?q=80&w=1200',
                'trailer_url' => 'https://www.youtube.com/watch?v=EXeTwQWrcwY',
                'subject_type' => 'movie',
                'genres' => ['Action', 'Crime', 'Drama'],
                'cast' => ['Christian Bale', 'Cillian Murphy'],
            ],
            [
                'moviebox_subject_id' => '7129381029381',
                'title' => 'Oppenheimer',
                'synopsis' => 'The story of American scientist J. Robert Oppenheimer and his role in the development of the atomic bomb.',
                'release_year' => 2023,
                'duration_minutes' => 180,
                'rating' => 4.7,
                'poster_url' => 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?q=80&w=600',
                'backdrop_url' => 'https://images.unsplash.com/photo-1506703719100-a0f3a48c0f86?q=80&w=1200',
                'trailer_url' => 'https://www.youtube.com/watch?v=uYPbbksJxIg',
                'subject_type' => 'movie',
                'genres' => ['Drama', 'Mystery'],
                'cast' => ['Cillian Murphy'],
            ],
        ];

        foreach ($filmsData as $f) {
            $film = Film::create([
                'moviebox_subject_id' => $f['moviebox_subject_id'],
                'title' => $f['title'],
                'slug' => Str::slug($f['title']),
                'synopsis' => $f['synopsis'],
                'release_year' => $f['release_year'],
                'duration_minutes' => $f['duration_minutes'],
                'rating' => $f['rating'],
                'poster_url' => $f['poster_url'],
                'backdrop_url' => $f['backdrop_url'],
                'trailer_url' => $f['trailer_url'],
                'subject_type' => $f['subject_type'],
            ]);

            // Sync Genres
            $genreIds = array_map(fn($gName) => $genres[$gName]->id, $f['genres']);
            $film->genres()->sync($genreIds);

            // Sync Cast
            $actorIds = array_map(fn($aName) => $actors[$aName]->id, $f['cast']);
            $film->actors()->sync($actorIds);
        }

        // 4. Create Demo User & Reviews
        $demoUser = User::firstOrCreate([
            'email' => 'user@cinestream.com',
        ], [
            'name' => 'Demo User',
            'password' => bcrypt('password123'),
        ]);

        $film1 = Film::first();
        if ($film1) {
            Review::create([
                'user_id' => $demoUser->id,
                'film_id' => $film1->id,
                'rating' => 5,
                'comment' => 'Visual efeknya luar biasa! Alur cerita sangat emosional dan penuh aksi.',
            ]);
        }
    }
}
