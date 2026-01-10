<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->string('name')->nullable()->after('title');
            $table->string('origin_name')->nullable()->after('name');
            $table->string('thumb_url')->nullable()->after('poster_url');
            $table->string('time')->nullable()->after('type');
            $table->string('quality')->nullable()->after('time');
            $table->string('lang')->nullable()->after('quality');
            $table->string('episode_current')->nullable()->after('lang');
            $table->boolean('chieurap')->nullable()->after('episode_current');
            $table->boolean('sub_docquyen')->nullable()->after('chieurap');
        });
    }

    public function down(): void
    {
        Schema::table('movies', function (Blueprint $table) {
            $table->dropColumn([
                'name',
                'origin_name',
                'thumb_url',
                'time',
                'quality',
                'lang',
                'episode_current',
                'chieurap',
                'sub_docquyen',
            ]);
        });
    }
};
