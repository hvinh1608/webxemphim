<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use Illuminate\Http\Request;

class EpisodeController extends Controller
{
    public function index()
    {
        return Episode::all();
    }

    public function store(Request $request)
    {
        $episode = Episode::create($request->all());
        return response()->json($episode, 201);
    }

    public function show($id)
    {
        return Episode::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $episode = Episode::findOrFail($id);
        $episode->update($request->all());
        return response()->json($episode);
    }

    public function destroy($id)
    {
        Episode::destroy($id);
        return response()->json(null, 204);
    }
}
