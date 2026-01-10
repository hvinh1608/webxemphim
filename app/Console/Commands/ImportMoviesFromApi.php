<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Movie;

class ImportMoviesFromApi extends Command
{
    protected $signature = 'movies:import-api {type=phim-bo} {startPage=1} {endPage=1} {sort_lang?}';
    protected $description = 'Import movies from external API (multi type, multi page, sort_lang) and save to database';

    public function handle()
    {
        $type = $this->argument('type');
        $startPage = (int)$this->argument('startPage');
        $endPage = (int)$this->argument('endPage');
        $sortLang = $this->argument('sort_lang');
        $total = 0;

        $allTypes = ['phim-bo', 'phim-le', 'hoat-hinh', 'tv-shows', 'phim-vietsub', 'phim-thuyet-minh', 'phim-long-tieng'];
        $typesToImport = ($type === 'all') ? $allTypes : [$type];

        foreach ($typesToImport as $importType) {
            // Lấy tổng số trang từ response trang đầu tiên
            $totalPages = $endPage;
            $sortLangParam = $sortLang ? "&sort_lang={$sortLang}" : "";
            if ($startPage === 1) {
                $url = "https://phimapi.com/v1/api/danh-sach/{$importType}?page=1{$sortLangParam}";
                $response = Http::get($url);
                if ($response->ok()) {
                    $data = $response->json();
                    $apiTotalPages = $data['data']['params']['pagination']['totalPages'] ?? null;
                    if ($apiTotalPages) {
                        $this->info("Tổng số trang của {$importType}: $apiTotalPages");
                        $totalPages = max($totalPages, $apiTotalPages);
                    } else {
                        $this->warn("Không xác định được tổng số trang từ API cho {$importType}!");
                    }
                }
            }
            for ($page = $startPage; $page <= $totalPages; $page++) {
                $url = "https://phimapi.com/v1/api/danh-sach/{$importType}?page={$page}{$sortLangParam}";
                $response = Http::get($url);
                if (!$response->ok()) {
                    $this->error("Failed to fetch data from API: $url");
                    continue;
                }
                $data = $response->json();
                // Tìm key chứa danh sách phim đúng chuẩn
                $list = null;
                if (isset($data['data']['items']) && is_array($data['data']['items'])) {
                    $list = $data['data']['items'];
                } elseif (isset($data['items']) && is_array($data['items'])) {
                    $list = $data['items'];
                } elseif (isset($data['data']) && is_array($data['data'])) {
                    $list = $data['data'];
                } else {
                    foreach ($data as $key => $val) {
                        if (is_array($val) && isset($val[0])) {
                            $list = $val;
                            break;
                        }
                    }
                }
                $this->info("[{$importType}] Các key của data: " . json_encode(array_keys($data)));
                if (!$list) {
                    $this->error("API response invalid: $url");
                    $this->line('Dump API response: ' . json_encode(array_keys($data)));
                    continue;
                }
                $this->info("[{$importType}] Số lượng item lấy được: " . count($list));
                if (count($list) > 0) {
                    $this->info('Item đầu tiên: ' . json_encode($list[0]));
                }
                $count = 0;
                foreach ($list as $item) {
                    $this->info('Slug: ' . ($item['slug'] ?? 'null'));
                    if (empty($item['slug'])) {
                        $this->warn("Skipped item with missing slug: " . json_encode($item));
                        continue;
                    }

                    // Country: join all country names with comma
                    $country = null;
                    if (!empty($item['country']) && is_array($item['country'])) {
                        $countryArr = array_map(function ($c) {
                            return $c['name'] ?? '';
                        }, $item['country']);
                        $country = implode(', ', array_filter($countryArr));
                    }

                    // Genres: join all category names with comma
                    $genres = null;
                    if (!empty($item['category']) && is_array($item['category'])) {
                        $genresArr = array_map(function ($g) {
                            return $g['name'] ?? '';
                        }, $item['category']);
                        $genres = implode(', ', array_filter($genresArr));
                    }

                    // Type: use API type if available, fallback to command type
                    $movieType = $item['type'] ?? $importType;

                    // Chuẩn hóa poster_url và thumb_url thành URL đầy đủ nếu cần
                    $poster_url = $item['poster_url'] ?? null;
                    if ($poster_url && !str_starts_with($poster_url, 'http')) {
                        $poster_url = 'https://img.phimapi.com/' . ltrim($poster_url, '/');
                    }
                    $thumb_url = $item['thumb_url'] ?? null;
                    if ($thumb_url && !str_starts_with($thumb_url, 'http')) {
                        $thumb_url = 'https://img.phimapi.com/' . ltrim($thumb_url, '/');
                    }

                    try {
                        $movie = Movie::updateOrCreate(
                            [
                                'slug' => $item['slug'],
                            ],
                            [
                                'title' => $item['name'] ?? $item['origin_name'] ?? 'No Title',
                                'name' => $item['name'] ?? null,
                                'origin_name' => $item['origin_name'] ?? null,
                                'poster_url' => $poster_url,
                                'thumb_url' => $thumb_url,
                                'year' => $item['year'] ?? null,
                                'country' => $country,
                                'genres' => $genres,
                                'type' => $movieType,
                                'time' => $item['time'] ?? null,
                                'quality' => $item['quality'] ?? null,
                                'lang' => $item['lang'] ?? null,
                                'episode_current' => $item['episode_current'] ?? null,
                                'chieurap' => $item['chieurap'] ?? null,
                                'sub_docquyen' => $item['sub_docquyen'] ?? null,
                            ]
                        );
                        $count++;
                        $this->info("Imported/Updated movie: {$movie->name}");
                    } catch (\Exception $e) {
                        $this->error("Error importing movie with slug {$item['slug']}: " . $e->getMessage());
                    }
                }
                $this->info("Imported/Updated {$count} movies from API type {$importType} page {$page}.");
                $total += $count;
            }
        }
        $this->info("Done. Total movies imported/updated: $total");
        return 0;
    }
}
