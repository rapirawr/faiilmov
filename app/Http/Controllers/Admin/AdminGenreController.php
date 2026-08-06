<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Genre;
use App\Models\AdminActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminGenreController extends Controller
{
    public function index()
    {
        $genres = Genre::withCount('films')->orderBy('name')->paginate(20);
        return view('admin.genres.index', compact('genres'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:genres,name',
        ]);

        $genre = Genre::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        AdminActivityLog::log('created_genre', "Menambahkan genre baru: {$genre->name}", 'Genre', $genre->id);

        return redirect()->route('admin.genres.index')->with('success', "Genre '{$genre->name}' berhasil ditambahkan.");
    }

    public function update(Request $request, Genre $genre)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100|unique:genres,name,' . $genre->id,
        ]);

        $genre->update([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']),
        ]);

        AdminActivityLog::log('updated_genre', "Mengubah genre: {$genre->name}", 'Genre', $genre->id);

        return redirect()->route('admin.genres.index')->with('success', "Genre '{$genre->name}' berhasil diperbarui.");
    }

    public function destroy(Genre $genre)
    {
        if ($genre->films()->count() > 0) {
            return redirect()->route('admin.genres.index')->with('error', "Gagal menghapus genre '{$genre->name}' karena masih terikat pada {$genre->films()->count()} film.");
        }

        $name = $genre->name;
        $id = $genre->id;
        $genre->delete();

        AdminActivityLog::log('deleted_genre', "Menghapus genre: {$name}", 'Genre', $id);

        return redirect()->route('admin.genres.index')->with('success', "Genre '{$name}' berhasil dihapus.");
    }
}
