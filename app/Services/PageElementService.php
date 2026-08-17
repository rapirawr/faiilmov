<?php

namespace App\Services;

use App\Models\PageElement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class PageElementService
{
    /**
     * Get all active elements applicable to the current page request
     */
    public function getActiveElementsForPage(string $pageName = 'all', ?string $currentPath = null): array
    {
        $isLoggedIn = Auth::check();
        $cacheKey = "page_elements_v2_{$pageName}_" . ($isLoggedIn ? 'user' : 'guest');

        return Cache::remember($cacheKey, 60, function () use ($pageName, $currentPath, $isLoggedIn) {
            $elements = PageElement::active()
                ->forPage($pageName, $currentPath)
                ->forAudience($isLoggedIn)
                ->orderBy('order', 'asc')
                ->orderBy('created_at', 'desc')
                ->get();

            // Group by visual position & type using safe array serialization
            $grouped = [
                'top_bars'         => [],
                'bottom_bars'      => [],
                'floating_widgets' => [],
                'popup_modals'     => [],
                'content_top'      => [],
                'content_bottom'   => [],
                'custom_blocks'    => [],
                'all'              => [],
            ];

            foreach ($elements as $el) {
                if (!$el->isScheduleActive()) continue;

                $data = $el->toArray();
                $data['type_label'] = $el->type_label;
                $data['is_schedule_active'] = true;

                $grouped['all'][] = $data;

                if ($el->type === 'broadcast_bar') {
                    if ($el->position === 'bottom') {
                        $grouped['bottom_bars'][] = $data;
                    } else {
                        $grouped['top_bars'][] = $data;
                    }
                } elseif ($el->type === 'floating_widget') {
                    $grouped['floating_widgets'][] = $data;
                } elseif ($el->type === 'popup_modal') {
                    $grouped['popup_modals'][] = $data;
                } elseif ($el->type === 'promo_banner') {
                    if ($el->position === 'content_bottom') {
                        $grouped['content_bottom'][] = $data;
                    } else {
                        $grouped['content_top'][] = $data;
                    }
                } elseif ($el->type === 'custom_block') {
                    $grouped['custom_blocks'][] = $data;
                }
            }

            return $grouped;
        });
    }

    /**
     * Clear page elements cache
     */
    public function clearCache(): void
    {
        $pages = ['all', 'home', 'watch', 'detail'];
        $audiences = ['user', 'guest'];

        foreach ($pages as $p) {
            foreach ($audiences as $a) {
                Cache::forget("page_elements_{$p}_{$a}");
                Cache::forget("page_elements_v2_{$p}_{$a}");
            }
        }
    }
}
