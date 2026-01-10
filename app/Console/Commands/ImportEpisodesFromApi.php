<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Movie;
use App\Models\Episode;

class ImportEpisodesFromApi extends Command
{
    protected $signature = 'episodes:import-api {movie_id?}';
    protected $description = 'Import episodes for movies from external API and save to database';

    public function handle()
    {
        $movieArg = $this->argument('movie_id');
        if ($movieArg) {
            if (is_numeric($movieArg)) {
                $movies = Movie::where('id', $movieArg)->get();
            } else {
                $movies = Movie::where('slug', $movieArg)->get();
            }
        } else {
            // Lấy tất cả phim trong database (mọi thể loại)
            $movies = Movie::all();
        }
        $total = 0;
        foreach ($movies as $movie) {
            $slug = $movie->slug;
            $url = "https://phimapi.com/phim/{$slug}";
            $response = Http::get($url);
            if (!$response->ok()) {
                $this->error("Failed to fetch: $slug");
                continue;
            }
            $data = $response->json();
            if (!isset($data['episodes']) || !is_array($data['episodes'])) {
                $this->error("No episodes for: $slug");
                continue;
            }
            $count = 0;
            foreach ($data['episodes'] as $server) {
                if (!isset($server['server_data']) || !is_array($server['server_data'])) continue;
                foreach ($server['server_data'] as $ep) {
                    // Lấy số tập
                    $epNum = $ep['episode'] ?? null;
                    if (!$epNum && isset($ep['name'])) {
                        if (preg_match('/\d+/', $ep['name'], $matches)) {
                            $epNum = (int)$matches[0];
                        } elseif (strtolower($ep['name']) === 'full') {
                            $epNum = 0;
                        }
                    }
                    // Nếu vẫn chưa xác định được số tập, gán 0 để tránh null
                    if ($epNum === null) {
                        $epNum = 0;
                    }
                    // Nhận diện lang/audio/subtitle từ tên tập hoặc thông tin server
                    $title = $ep['name'] ?? $ep['episode_name'] ?? '';
                    $audio = null;
                    $lang = null;
                    $subtitle = null;
                    $titleLower = strtolower($title);
                    $langs = [];
                    // Nhận diện từ title
                    if (strpos($titleLower, 'vietsub') !== false) $langs[] = 'vietsub';
                    if (strpos($titleLower, 'thuyết minh') !== false || strpos($titleLower, 'thuyet minh') !== false) $langs[] = 'thuyet-minh';
                    if (strpos($titleLower, 'long tiếng') !== false || strpos($titleLower, 'long tieng') !== false) $langs[] = 'long-tieng';
                    // Nhận diện từ audio (nếu có)
                    $audioRaw = strtolower($ep['audio'] ?? '');
                    if (!$audioRaw && isset($movie->audio)) {
                        $audioRaw = strtolower($movie->audio);
                    }
                    if ($audioRaw) {
                        if (strpos($audioRaw, 'vietsub') !== false && !in_array('vietsub', $langs)) $langs[] = 'vietsub';
                        if ((strpos($audioRaw, 'thuyết minh') !== false || strpos($audioRaw, 'thuyet minh') !== false) && !in_array('thuyet-minh', $langs)) $langs[] = 'thuyet-minh';
                        if ((strpos($audioRaw, 'long tiếng') !== false || strpos($audioRaw, 'long tieng') !== false) && !in_array('long-tieng', $langs)) $langs[] = 'long-tieng';
                    }
                    $lang = implode(',', $langs);
                    // Audio detection (nếu có trường audio hoặc trong title)
                    if (isset($ep['audio'])) {
                        $audio = $ep['audio'];
                    } elseif (strpos($titleLower, 'audio') !== false) {
                        $audio = 'audio';
                    }
                    // Subtitle detection (nếu có trường subtitle hoặc trong title)
                    if (isset($ep['subtitle'])) {
                        $subtitle = $ep['subtitle'];
                    } elseif (strpos($titleLower, 'sub') !== false) {
                        $subtitle = 'sub';
                    }
                    // Fallback mặc định nếu không nhận diện được
                    if (!$lang) $lang = 'vietsub';
                    if (!$audio) $audio = 'stereo';
                    if (!$subtitle) $subtitle = 'none';
                    Episode::updateOrCreate(
                        [
                            'movie_id' => $movie->id,
                            'episode_number' => $epNum,
                        ],
                        [
                            'title' => $title,
                            'slug' => $ep['slug'] ?? null,
                            'video_url' => $ep['link_embed'] ?? $ep['link_m3u8'] ?? null,
                            'lang' => $lang,
                            'audio' => $audio,
                            'subtitle' => $subtitle,
                        ]
                    );
                    $count++;
                }
            }
            $this->info("Imported/Updated $count episodes for movie: $movie->title");
            $total += $count;
        }
        $this->info("Done. Total episodes imported/updated: $total");
        return 0;
    }
}
