<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $query = Movie::query();
        if ($request->has('type')) {
            $type = strtolower($request->type);
            if ($type === 'phim-bo') {
                $type = 'series';
            } elseif ($type === 'phim-le') {
                $type = 'single';
            } elseif ($type === 'hoat-hinh' || $type === 'hoathinh') {
                $type = 'hoathinh';
            }
            $query->whereRaw('LOWER(type) = ?', [$type]);
        }
        if ($request->has('country')) {
            $query->where('country', 'like', '%' . $request->country . '%');
        }
        if ($request->has('genres')) {
            $genres = explode(',', $request->genres);
            $query->where(function($q) use ($genres) {
                foreach ($genres as $genre) {
                    $q->orWhere('genres', 'like', '%' . trim($genre) . '%');
                }
            });
        }
        if ($request->has('year')) {
            $query->where('year', $request->year);
        }
        $perPage = $request->input('per_page', 20);
        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $movie = Movie::create($request->all());
        return response()->json($movie, 201);
    }

    public function show($id)
    {
        return Movie::with('episodes')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $movie = Movie::findOrFail($id);
        $movie->update($request->all());
        return response()->json($movie);
    }

    public function destroy($id)
    {
        Movie::destroy($id);
        return response()->json(null, 204);
    }

    public function related($id, Request $request)
    {
        $movie = Movie::findOrFail($id);
        $limit = (int) $request->input('limit', 10);
        $query = Movie::where('id', '!=', $movie->id);
        // Ưu tiên cùng thể loại
        if ($movie->genres) {
            $genres = array_map('trim', explode(',', $movie->genres));
            $query->where(function ($q) use ($genres) {
                foreach ($genres as $genre) {
                    $q->orWhere('genres', 'LIKE', "%$genre%");
                }
            });
        }
        // Ưu tiên cùng quốc gia
        if ($movie->country) {
            $query->orWhere('country', 'LIKE', "%{$movie->country}%");
        }
        // Ưu tiên cùng năm
        if ($movie->year) {
            $query->orWhere('year', $movie->year);
        }
        // Random kết quả
        $related = $query->inRandomOrder()->limit($limit)->get();
        return response()->json($related);
    }

    public function episodes($id)
    {
        $movie = Movie::with(['episodes' => function($q) {
            $q->orderBy('episode_number');
        }])->findOrFail($id);
        return response()->json($movie->episodes);
    }

    public function search(Request $request)
    {
        $q = $request->input('q');
        if (!$q) {
            return response()->json([], 400);
        }
        $movies = Movie::where(function($query) use ($q) {
            $query->where('name', 'like', "%$q%")
                  ->orWhere('title', 'like', "%$q%");
        })->limit(30)->get();
        return response()->json(['data' => $movies]);
    }
}
