<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Episode extends Model
{
    use HasFactory;

    protected $fillable = [
        'movie_id',
        'episode_number',
        'title',
        'slug',
        'video_url',
        'lang',
        'audio',
        'subtitle',
    ];

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }
}
