<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'title',
        'name',
        'origin_name',
        'slug',
        'description',
        'poster_url',
        'thumb_url',
        'year',
        'country',
        'genres',
        'type',
        'time',
        'quality',
        'lang',
        'episode_current',
        'chieurap',
        'sub_docquyen',
        'tmdb_id',
        'created_at',
        'updated_at',
    ];

    public function episodes()
    {
        return $this->hasMany(Episode::class);
    }
}
