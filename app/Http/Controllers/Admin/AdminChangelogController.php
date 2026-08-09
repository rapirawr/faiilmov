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

        $changelogs = $query->orderByDesc('release_date')->orderByDesc('id')->paginate(15)->withQueryString();
        $nextVersion = $this->calculateNextVersion();

        return view('admin.changelogs.index', compact('changelogs', 'nextVersion'));
    }

    public function create()
    {
        $nextVersion = $this->calculateNextVersion();
        return view('admin.changelogs.create', compact('nextVersion'));
    }

    private function calculateNextVersion(): string
    {
        $latest = Changelog::orderBy('release_date', 'desc')->orderBy('id', 'desc')->first();
        if (!$latest || !$latest->version) {
            return 'v1.0.0';
        }

        $raw = trim($latest->version);
        $hasV = str_starts_with(strtolower($raw), 'v');
        $cleanNum = ltrim($raw, 'vV');

        $parts = explode('.', $cleanNum);
        if (count($parts) >= 3) {
            $parts[1] = (int)$parts[1] + 1; // e.g. 1.1.0 -> 1.2.0
            $parts[2] = 0;
            return ($hasV ? 'v' : '') . implode('.', array_slice($parts, 0, 3));
        } elseif (count($parts) === 2) {
            $parts[1] = (int)$parts[1] + 1; // e.g. 1.1 -> 1.2.0
            return ($hasV ? 'v' : '') . implode('.', $parts) . '.0';
        } else {
            $num = (int)$cleanNum + 1;
            return ($hasV ? 'v' : '') . $num . '.0.0';
        }
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

    /**
     * Import changelog data directly from AI (JSON or Markdown format)
     */
    public function import(Request $request)
    {
        $request->validate([
            'format' => 'required|in:json,markdown',
            'content' => 'required|string',
            'auto_publish' => 'nullable|boolean',
        ]);

        $format = $request->input('format');
        $content = trim($request->input('content'));
        $autoPublish = $request->boolean('auto_publish', true);

        $parsedItems = [];

        if ($format === 'json') {
            // Clean markdown block wrappers ```json ... ``` if present
            $cleanJson = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', $content);
            $decoded = json_decode(trim($cleanJson), true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->with('error', 'Format JSON tidak valid: ' . json_last_error_msg());
            }

            $items = isset($decoded['version']) ? [$decoded] : (is_array($decoded) ? $decoded : []);

            foreach ($items as $item) {
                if (!empty($item['version']) && !empty($item['title'])) {
                    $changes = [];
                    if (isset($item['changes']) && is_array($item['changes'])) {
                        foreach ($item['changes'] as $c) {
                            if (is_string($c)) {
                                $changes[] = ['type' => 'feature', 'text' => $c];
                            } elseif (is_array($c) && !empty($c['text'])) {
                                $changes[] = [
                                    'type' => in_array($c['type'] ?? '', ['feature', 'improvement', 'fix', 'security']) ? $c['type'] : 'feature',
                                    'text' => $c['text'],
                                ];
                            }
                        }
                    }

                    $parsedItems[] = [
                        'version' => $item['version'],
                        'title' => $item['title'],
                        'type' => in_array($item['type'] ?? '', ['major', 'minor', 'patch', 'security']) ? $item['type'] : 'minor',
                        'release_date' => $item['release_date'] ?? date('Y-m-d'),
                        'summary' => $item['summary'] ?? null,
                        'changes' => $changes,
                    ];
                }
            }
        } else {
            // Markdown format parser
            $parsedItems = $this->parseMarkdownChangelog($content);
        }

        if (empty($parsedItems)) {
            return back()->with('error', 'Tidak ada data catatan rilis changelog valid yang berhasil diekstrak.');
        }

        $createdCount = 0;
        foreach ($parsedItems as $item) {
            $changelog = Changelog::create([
                'version' => $item['version'],
                'title' => $item['title'],
                'type' => $item['type'],
                'release_date' => $item['release_date'],
                'summary' => $item['summary'],
                'changes' => $item['changes'],
                'is_published' => $autoPublish,
                'published_at' => $autoPublish ? now() : null,
                'created_by' => Auth::id(),
            ]);

            AdminActivityLog::log('imported_changelog', "Import rilis changelog baru dari AI: {$changelog->version} - {$changelog->title}", 'Changelog', $changelog->id);
            $createdCount++;
        }

        return redirect()->route('admin.changelogs.index')->with('success', "Berhasil meng-import {$createdCount} catatan rilis changelog.");
    }

    private function parseMarkdownChangelog(string $markdown): array
    {
        // Split by # v or ## v for multi-version markdown releases
        $sections = preg_split('/(?=^#+\s+v?\d)/m', $markdown);
        $result = [];

        foreach ($sections as $sec) {
            $sec = trim($sec);
            if (empty($sec)) continue;

            $lines = explode("\n", $sec);
            $firstLine = array_shift($lines);

            $version = 'v1.0.0';
            $title = 'Catatan Rilis Pembaruan';

            if (preg_match('/^#+\s*(v?\d+\.\d+(?:\.\d+)?)(?:\s*[:-]\s*(.+))?/i', $firstLine, $m)) {
                $version = $m[1];
                $title = isset($m[2]) ? trim($m[2]) : "Rilis {$version}";
            }

            $type = 'minor';
            $releaseDate = date('Y-m-d');
            $summaryLines = [];
            $changes = [];

            $currentChangeType = 'feature';

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                if (preg_match('/(?:\*\*|\*)?(?:tanggal|date)(?:\*\*|\*)?\s*:\s*(\d{4}-\d{2}-\d{2})/i', $line, $mDate)) {
                    $releaseDate = $mDate[1];
                    continue;
                }
                if (preg_match('/(?:\*\*|\*)?(?:tipe|type)(?:\*\*|\*)?\s*:\s*(major|minor|patch|security)/i', $line, $mType)) {
                    $type = strtolower($mType[1]);
                    continue;
                }
                if (preg_match('/(?:\*\*|\*)?(?:ringkasan|summary)(?:\*\*|\*)?\s*:\s*(.+)/i', $line, $mSum)) {
                    $summaryLines[] = trim($mSum[1]);
                    continue;
                }

                if (preg_match('/^#+\s*(fitur|feature|peningkatan|improvement|perbaikan|fix|bug|keamanan|security)/i', $line, $mSub)) {
                    $subName = strtolower($mSub[1]);
                    if (str_contains($subName, 'fix') || str_contains($subName, 'perbaikan') || str_contains($subName, 'bug')) {
                        $currentChangeType = 'fix';
                    } elseif (str_contains($subName, 'improve') || str_contains($subName, 'peningkatan')) {
                        $currentChangeType = 'improvement';
                    } elseif (str_contains($subName, 'sec') || str_contains($subName, 'keamanan')) {
                        $currentChangeType = 'security';
                    } else {
                        $currentChangeType = 'feature';
                    }
                    continue;
                }

                if (preg_match('/^[-*+]\s+(?:\[(feature|improvement|fix|security)\]\s*)?(.+)/i', $line, $mBullet)) {
                    $itemType = !empty($mBullet[1]) ? strtolower($mBullet[1]) : $currentChangeType;
                    $itemText = trim($mBullet[2]);

                    if (!empty($itemText)) {
                        $changes[] = [
                            'type' => in_array($itemType, ['feature', 'improvement', 'fix', 'security']) ? $itemType : 'feature',
                            'text' => $itemText,
                        ];
                    }
                } elseif (!str_starts_with($line, '#')) {
                    if (count($changes) === 0) {
                        $summaryLines[] = $line;
                    }
                }
            }

            $result[] = [
                'version' => $version,
                'title' => $title,
                'type' => $type,
                'release_date' => $releaseDate,
                'summary' => implode("\n", $summaryLines) ?: null,
                'changes' => $changes,
            ];
        }

        return $result;
    }
}
