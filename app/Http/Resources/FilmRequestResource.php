<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FilmRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'year' => $this->year,
            'status' => $this->status,
            'status_label' => match($this->status) {
                'pending' => 'Pending',
                'searching' => 'Sedang Dicari',
                'added' => 'Ditemukan',
                'rejected' => 'Ditolak',
                default => ucfirst($this->status),
            },
            'request_count' => $this->request_count,
            'rejection_reason' => $this->rejection_reason,
            'matched_film' => $this->whenLoaded('matchedFilm', function () {
                if (!$this->matchedFilm) return null;
                return [
                    'id' => $this->matchedFilm->id,
                    'title' => $this->matchedFilm->title,
                    'slug' => $this->matchedFilm->slug,
                    'poster_url' => $this->matchedFilm->poster_url,
                    'url' => route('film.show', $this->matchedFilm->slug, false),
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
