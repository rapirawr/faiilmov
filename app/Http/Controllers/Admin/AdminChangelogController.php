<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Changelog;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminChangelogController extends Controller
{
    public function index(Request $request)
    {
        $query = Changelog::with('creator');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('version', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        $changelogs = $query->orderBy('release_date', 'desc')->paginate(15)->withQueryString();

        return view('admin.changelogs.index', compact('changelogs'));
    }

    public function create()
    {
        return view('admin.changelogs.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'version' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'type' => 'required|in:major,minor,patch,security',
            'release_date' => 'required|date',
            'summary' => 'nullable|string',
            'changes' => 'nullable|array',
            'changes.*.type' => 'required|in:feature,improvement,fix,security',
            'changes.*.text' => 'required|string|max:500',
            'is_published' => 'nullable|boolean',
        ]);

        $changelog = Changelog::create([
            'version' => $validated['version'],
            'title' => $validated['title'],
            'type' => $validated['type'],
            'release_date' => $validated['release_date'],
            'summary' => $validated['summary'] ?? null,
            'changes' => array_values($validated['changes'] ?? []),
            'is_published' => $request->has('is_published'),
            'published_at' => $request->has('is_published') ? now() : null,
            'created_by' => Auth::id(),
        ]);

        AdminActivityLog::log('created_changelog', "Rilis changelog baru: {$changelog->version} - {$changelog->title}", 'Changelog', $changelog->id);

        return redirect()->route('admin.changelogs.index')->with('success', "Catatan rilis {$changelog->version} berhasil ditambahkan.");
    }

    public function edit(Changelog $changelog)
    {
        return view('admin.changelogs.edit', compact('changelog'));
    }

    public function update(Request $request, Changelog $changelog)
    {
        $validated = $request->validate([
            'version' => 'required|string|max:50',
            'title' => 'required|string|max:255',
            'type' => 'required|in:major,minor,patch,security',
            'release_date' => 'required|date',
            'summary' => 'nullable|string',
            'changes' => 'nullable|array',
            'changes.*.type' => 'required|in:feature,improvement,fix,security',
            'changes.*.text' => 'required|string|max:500',
            'is_published' => 'nullable|boolean',
        ]);

        $changelog->update([
            'version' => $validated['version'],
            'title' => $validated['title'],
            'type' => $validated['type'],
            'release_date' => $validated['release_date'],
            'summary' => $validated['summary'] ?? $changelog->summary,
            'changes' => array_values($validated['changes'] ?? []),
            'is_published' => $request->has('is_published'),
            'published_at' => $request->has('is_published') ? ($changelog->published_at ?: now()) : null,
        ]);

        AdminActivityLog::log('updated_changelog', "Mengubah rilis changelog: {$changelog->version}", 'Changelog', $changelog->id);

        return redirect()->route('admin.changelogs.index')->with('success', "Catatan rilis {$changelog->version} berhasil diperbarui.");
    }

    public function destroy(Changelog $changelog)
    {
        $version = $changelog->version;
        $id = $changelog->id;
        $changelog->delete();

        AdminActivityLog::log('deleted_changelog', "Menghapus catatan rilis: {$version}", 'Changelog', $id);

        return redirect()->route('admin.changelogs.index')->with('success', "Catatan rilis {$version} berhasil dihapus.");
    }

    public function togglePublish(Changelog $changelog)
    {
        $newStatus = !$changelog->is_published;
        $changelog->update([
            'is_published' => $newStatus,
            'published_at' => $newStatus ? now() : null,
        ]);

        $actionText = $newStatus ? "dipublikasikan" : "disembunyikan";
        AdminActivityLog::log('toggled_changelog_status', "Status rilis {$changelog->version} diubah menjadi {$actionText}", 'Changelog', $changelog->id);

        return back()->with('success', "Catatan rilis {$changelog->version} berhasil {$actionText}.");
    }
}
