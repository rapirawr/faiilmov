<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Actor;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminActorController extends Controller
{
    public function index(Request $request)
    {
        $query = Actor::withCount('films');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $actors = $query->orderBy('name')->paginate(20)->withQueryString();

        return view('admin.actors.index', compact('actors'));
    }

    public function searchApi(Request $request)
    {
        $q = trim($request->input('q', ''));
        if (strlen($q) < 2) {
            return response()->json([]);
        }

        $actors = Actor::where('name', 'like', "%{$q}%")
            ->select(['id', 'name', 'photo_url'])
            ->orderBy('name')
            ->limit(15)
            ->get();

        return response()->json($actors);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'photo_url' => 'nullable|url',
        ]);

        $actor = Actor::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
            'photo_url' => $validated['photo_url'] ?? null,
        ]);

        AdminActivityLog::log('created_actor', "Menambahkan aktor baru: {$actor->name}", 'Actor', $actor->id);

        return redirect()->route('admin.actors.index')->with('success', "Aktor '{$actor->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, Actor $actor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'photo_url' => 'nullable|url',
        ]);

        $actor->update([
            'name' => $validated['name'],
            'photo_url' => $validated['photo_url'] ?? $actor->photo_url,
        ]);

        AdminActivityLog::log('updated_actor', "Mengubah aktor: {$actor->name}", 'Actor', $actor->id);

        return redirect()->route('admin.actors.index')->with('success', "Aktor '{$actor->name}' berhasil diperbarui.");
    }

    public function destroy(Actor $actor)
    {
        if ($actor->films()->count() > 0) {
            return redirect()->route('admin.actors.index')->with('error', "Gagal menghapus aktor '{$actor->name}' karena masih terikat pada {$actor->films()->count()} film.");
        }

        $name = $actor->name;
        $id = $actor->id;
        $actor->delete();

        AdminActivityLog::log('deleted_actor', "Menghapus aktor: {$name}", 'Actor', $id);

        return redirect()->route('admin.actors.index')->with('success', "Aktor '{$name}' berhasil dihapus.");
    }

    public function syncApi(Request $request)
    {
        $purgeDummy = $request->boolean('purge_dummy', false);

        \App\Jobs\SyncActorsJob::dispatch(\Illuminate\Support\Facades\Auth::id(), $purgeDummy);

        AdminActivityLog::log('sync_api_actors_triggered', "Memulai job sinkronisasi aktor dari MovieBox API dalam background queue.");

        if ($request->wantsJson()) {
            return response()->json([
                'status' => 'ok',
                'message' => 'Proses sinkronisasi data aktor dari API eksternal telah dimulai di latar belakang.'
            ]);
        }

        return redirect()->route('admin.actors.index')->with('success', 'Proses sinkronisasi data aktor dari API eksternal telah dimulai di latar belakang.');
    }

    public function merge(Request $request)
    {
        $validated = $request->validate([
            'source_actor_id' => 'required|exists:actors,id',
            'target_actor_id' => 'required|exists:actors,id|different:source_actor_id',
        ]);

        $source = Actor::with('films')->findOrFail($validated['source_actor_id']);
        $target = Actor::with('films')->findOrFail($validated['target_actor_id']);

        $targetFilmIds = $target->films->pluck('id')->toArray();

        foreach ($source->films as $film) {
            if (!in_array($film->id, $targetFilmIds)) {
                $target->films()->attach($film->id, [
                    'character_name' => $film->pivot->character_name,
                    'role_type' => $film->pivot->role_type,
                ]);
            }
        }

        $sourceName = $source->name;
        $targetName = $target->name;

        $source->films()->detach();
        $source->delete();

        AdminActivityLog::log('merged_actors', "Menggabungkan aktor '{$sourceName}' ke '{$targetName}'", 'Actor', $target->id);

        return redirect()->route('admin.actors.index')->with('success', "Aktor '{$sourceName}' berhasil digabungkan ke '{$targetName}'.");
    }
}
