<?php

namespace App\Console\Commands;

use App\Models\Film;
use App\Models\Genre;
use Illuminate\Console\Command;

class AssignFilmGenres extends Command
{
    protected $signature = 'films:assign-genres 
                            {--limit=100 : Number of films to process}';

    protected $description = 'Auto-assign genres to films based on title/synopsis keywords';

    private array $genreKeywords = [
        'horror' => ['horror', 'zombie', 'ghost', 'haunted', 'demon', 'evil', 'scary', 'terror', 'hantu', 'pocong', 'kuntilanak', 'setan'],
        'action' => ['action', 'fight', 'battle', 'war', 'combat', 'mission', 'agent', 'assassin', 'warrior', 'pertempuran', 'aksi'],
        'comedy' => ['comedy', 'funny', 'humor', 'laugh', 'komedi', 'lucu', 'kocak'],
        'drama' => ['drama', 'emotional', 'family', 'life', 'keluarga', 'kehidupan'],
        'romance' => ['romance', 'love', 'romantic', 'cinta', 'romantis'],
        'sci-fi' => ['sci-fi', 'science fiction', 'space', 'alien', 'future', 'robot', 'android', 'cyber', 'luar angkasa'],
        'thriller' => ['thriller', 'suspense', 'mystery', 'crime', 'detective', 'misteri', 'kriminal'],
        'animation' => ['animation', 'animated', 'cartoon', 'anime', 'animasi'],
        'adventure' => ['adventure', 'journey', 'quest', 'expedition', 'petualangan'],
        'fantasy' => ['fantasy', 'magic', 'wizard', 'dragon', 'sihir', 'fantasi'],
        'crime' => ['crime', 'criminal', 'police', 'detective', 'mafia', 'gang', 'polisi'],
        'documentary' => ['documentary', 'dokumenter', 'real story', 'true story'],
        'sport' => ['sport', 'football', 'basketball', 'boxing', 'racing', 'olahraga'],
    ];

    public function handle(): int
    {
        $limit = (int)$this->option('limit');
        
        $films = Film::doesntHave('genres')
            ->limit($limit)
            ->get();

        if ($films->isEmpty()) {
            $this->info('No films without genres found.');
            return self::SUCCESS;
        }

        $this->info("Processing {$films->count()} films...");
        $bar = $this->output->createProgressBar($films->count());

        $assigned = 0;

        foreach ($films as $film) {
            $detectedGenres = $this->detectGenres($film);
            
            if (!empty($detectedGenres)) {
                $film->genres()->attach($detectedGenres);
                $assigned++;
            }
            
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("✓ Assigned genres to {$assigned} films.");

        return self::SUCCESS;
    }

    private function detectGenres(Film $film): array
    {
        $text = strtolower($film->title . ' ' . $film->synopsis);
        $detectedGenreSlugs = [];

        foreach ($this->genreKeywords as $genreSlug => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, strtolower($keyword))) {
                    $detectedGenreSlugs[] = $genreSlug;
                    break;
                }
            }
        }

        if (empty($detectedGenreSlugs)) {
            $detectedGenreSlugs[] = 'drama';
        }

        $genreIds = Genre::whereIn('slug', $detectedGenreSlugs)->pluck('id')->toArray();
        
        return $genreIds;
    }
}
