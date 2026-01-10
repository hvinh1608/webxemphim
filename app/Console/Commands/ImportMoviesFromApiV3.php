<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Movie;
use App\Models\Episode;

class ImportMoviesFromApiV3 extends Command
{
    protected $signature = 'movies:import-api-v3 {--page=1}';
    protected $description = 'Import movies and episodes from phimapi.com V3';

    public function handle()
    {
        $pageOption = $this->option('page');
        if ($pageOption) {
            $this->importPage($pageOption);
        } else {
            // Lấy totalPages từ trang 1
            $url = "https://phimapi.com/danh-sach/phim-moi-cap-nhat-v3?page=1";
            $this->info("Fetching first page to get totalPages: $url");
            $response = Http::get($url);
            if (!$response->ok()) {
                $this->error('Failed to fetch movie list.');
                return 1;
            }
            $data = $response->json();
            $totalPages = $data['pagination']['totalPages'] ?? 1;
            $this->info("Total pages: $totalPages");
            for ($page = 1; $page <= $totalPages; $page++) {
                $this->importPage($page);
            }
        }
        $this->info('Done!');
        return 0;
    }

    protected function importPage($page)
    {
        $url = "https://phimapi.com/danh-sach/phim-moi-cap-nhat-v3?page={$page}";
        $this->info("Fetching movies from: $url");
        $response = Http::get($url);
        if (!$response->ok()) {
            $this->error("Failed to fetch movie list at page $page.");
            return;
        }
        $data = $response->json();
        $movies = $data['data'] ?? [];
        $this->info("[Page $page] Movies fetched: " . count($movies));
        if (empty($movies)) {
            $this->warn("[Page $page] No movies found! Raw response:");
            $this->line(print_r($data, true));
        }
        foreach ($movies as $movieData) {
            $tmdb = $movieData['tmdb'] ?? [];
            $tmdb_id = $tmdb['id'] ?? null;
            $tmdb_type = $tmdb['type'] ?? null;
            $categories = isset($movieData['category']) ? json_encode($movieData['category']) : null;
            $countries = isset($movieData['country']) ? json_encode($movieData['country']) : null;
            $movie = Movie::updateOrCreate(
                ['slug' => $movieData['slug']],
                [
                    'title' => $movieData['name'] ?? $movieData['title'] ?? null,
                    'name' => $movieData['name'] ?? null,
                    'origin_name' => $movieData['origin_name'] ?? null,
                    'description' => $movieData['content'] ?? null,
                    'poster_url' => $movieData['poster_url'] ?? null,
                    'thumb_url' => $movieData['thumb_url'] ?? null,
                    'year' => $movieData['year'] ?? null,
                    'country' => $countries,
                    'genres' => $categories,
                    'type' => $movieData['type'] ?? $tmdb_type,
                    'time' => $movieData['time'] ?? null,
                    'quality' => $movieData['quality'] ?? null,
                    'lang' => $movieData['lang'] ?? null,
                    'episode_current' => $movieData['episode_current'] ?? null,
                    'chieurap' => $movieData['chieurap'] ?? null,
                    'sub_docquyen' => $movieData['sub_docquyen'] ?? null,
                    'tmdb_id' => $tmdb_id,
                ]
            );
            $this->info("Imported movie: {$movie->title}");
            // Fetch episodes
            $this->importEpisodes($movie, $movieData);
        }
    }


    protected function importEpisodes(Movie $movie, $movieData)
    {
        $slug = $movie->slug;
        $type = $movie->type;
        $tmdb_type = null;
        if (in_array($type, ['series', 'phimbo', 'hoathinh'])) {
            $tmdb_type = 'tv';
        } elseif ($type === 'single' || $type === 'phimle') {
            $tmdb_type = 'movie';
        }
        // Ưu tiên lấy tập qua TMDB nếu là tv/hoạt hình/phim bộ
        if (!empty($movie->tmdb_id) && $tmdb_type === 'tv') {
            $tmdbUrl = "https://phimapi.com/tmdb/tv/{$movie->tmdb_id}";
            $tmdbRes = Http::get($tmdbUrl);
            if ($tmdbRes->ok()) {
                $tmdbDetail = $tmdbRes->json();
                $episodes = $tmdbDetail['episodes'] ?? [];
                if (!empty($episodes)) {
                    $this->saveEpisodes($movie, $episodes);
                    $this->info("Imported episodes for {$movie->title} from TMDB API (tv)");
                    return;
                }
            }
        }
        // Nếu không phải tv hoặc không có tập từ TMDB, thử lấy từ API chính
        $detailUrl = "https://phimapi.com/phim/{$slug}";
        $detailRes = Http::get($detailUrl);
        if ($detailRes->ok()) {
            $detail = $detailRes->json();
            $episodes = $detail['episodes'] ?? [];
            if (!empty($episodes)) {
                $this->saveEpisodes($movie, $episodes);
                $this->info("Imported episodes for {$movie->title} from main API");
                return;
            }
        }
        // Nếu là phim lẻ có TMDB, thử lấy tập qua TMDB (movie)
        if (!empty($movie->tmdb_id) && $tmdb_type === 'movie') {
            $tmdbUrl = "https://phimapi.com/tmdb/movie/{$movie->tmdb_id}";
            $tmdbRes = Http::get($tmdbUrl);
            if ($tmdbRes->ok()) {
                $tmdbDetail = $tmdbRes->json();
                $episodes = $tmdbDetail['episodes'] ?? [];
                if (!empty($episodes)) {
                    $this->saveEpisodes($movie, $episodes);
                    $this->info("Imported episodes for {$movie->title} from TMDB API (movie)");
                    return;
                }
            }
        }
        $this->warn("No episodes found for {$movie->title} (slug: {$slug}, tmdb_id: {$movie->tmdb_id}, type: {$type})");
    }

    protected function saveEpisodes(Movie $movie, $episodes)
    {
        foreach ($episodes as $ep) {
            Episode::updateOrCreate(
                [
                    'movie_id' => $movie->id,
                    'server' => $ep['server_name'] ?? 'default',
                    'episode' => $ep['name'] ?? null,
                ],
                [
                    'slug' => $ep['slug'] ?? null,
                    'url' => $ep['url'] ?? null,
                ]
            );
        }
    }
}
