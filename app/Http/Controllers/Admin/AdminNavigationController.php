<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminActivityLog;
use App\Services\NavigationService;
use Illuminate\Http\Request;

class AdminNavigationController extends Controller
{
    /**
     * Display navigation management CMS.
     */
    public function index()
    {
        $sidebarItems  = NavigationService::getSidebarMenu();
        $sidebarWidget = NavigationService::getSidebarWidget();

        $availableIcons = [
            'home', 'tv', 'tv-2', 'clapperboard', 'sparkles', 'flame', 
            'history', 'users', 'smartphone', 'compass', 'heart', 'star', 
            'bookmark', 'film', 'radio', 'bell', 'globe', 'play', 
            'video', 'zap', 'tag', 'shield', 'info', 'help-circle', 
            'gift', 'award', 'camera', 'activity', 'calendar', 'clock', 'laptop'
        ];

        return view('admin.navigation.index', compact(
            'sidebarItems',
            'sidebarWidget',
            'availableIcons'
        ));
    }

    /**
     * Save reordered and edited navigation menu items and widget.
     */
    public function update(Request $request)
    {
        $request->validate([
            'items' => 'required',
        ]);

        $items = is_string($request->items) ? json_decode($request->items, true) : $request->items;

        if (!is_array($items)) {
            return back()->with('error', 'Format data navigasi tidak valid.');
        }

        // Clean & sanitize items array
        $cleanItems = [];
        foreach ($items as $idx => $item) {
            $cleanItems[] = [
                'id'         => $item['id'] ?? ('custom_' . ($idx + 1)),
                'label'      => trim($item['label'] ?? 'Menu Item'),
                'icon'       => trim($item['icon'] ?? 'compass'),
                'url'        => trim($item['url'] ?? '#'),
                'route'      => trim($item['route'] ?? ''),
                'is_active'  => (bool)($item['is_active'] ?? true),
                'badge'      => trim($item['badge'] ?? ''),
                'target'     => in_array($item['target'] ?? '', ['_self', '_blank']) ? $item['target'] : '_self',
                'visibility' => in_array($item['visibility'] ?? '', ['all', 'auth_only', 'guest_only']) ? $item['visibility'] : 'all',
            ];
        }

        NavigationService::saveSidebarMenu($cleanItems);

        // Save bottom widget settings if present
        if ($request->has('widget')) {
            $widget = is_string($request->widget) ? json_decode($request->widget, true) : $request->widget;
            if (is_array($widget)) {
                $cleanWidget = [
                    'is_active'      => (bool)($widget['is_active'] ?? true),
                    'title'          => trim($widget['title'] ?? 'Get faiilmov'),
                    'button_text'    => trim($widget['button_text'] ?? 'Mobile'),
                    'button_url'     => trim($widget['button_url'] ?? '/download-app'),
                    'button_icon'    => trim($widget['button_icon'] ?? 'smartphone'),
                    'button2_active' => (bool)($widget['button2_active'] ?? false),
                    'button2_text'   => trim($widget['button2_text'] ?? 'macOS'),
                    'button2_url'    => trim($widget['button2_url'] ?? '#'),
                    'button2_icon'   => trim($widget['button2_icon'] ?? 'laptop'),
                ];
                NavigationService::saveSidebarWidget($cleanWidget);
            }
        }

        AdminActivityLog::log(
            'update_navigation',
            "Memperbarui susunan menu dan widget sidebar (" . count($cleanItems) . " menu)."
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Susunan menu sidebar berhasil disimpan dan langsung aktif!',
            ]);
        }

        return back()->with('success', '🎉 Susunan menu navigasi sidebar berhasil diperbarui dan disimpan.');
    }

    /**
     * Reset menu navigation to default arrangement.
     */
    public function reset(Request $request)
    {
        NavigationService::resetToDefault();

        AdminActivityLog::log(
            'reset_navigation',
            "Mengembalikan susunan menu navigasi sidebar ke pengaturan default."
        );

        return back()->with('success', "Susunan menu navigasi sidebar berhasil dikembalikan ke default.");
    }
}
