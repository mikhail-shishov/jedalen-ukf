<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ArticleRevisionSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('article_revisions') || !Schema::hasTable('articles')) {
            return;
        }

        $articles = DB::table('articles')->limit(5)->get(['id', 'users_id', 'title_sk', 'content_sk']);

        foreach ($articles as $article) {
            DB::table('article_revisions')->updateOrInsert(
                [
                    'article_id' => (int) $article->id,
                    'users_id' => (int) $article->users_id,
                    'title_sk' => (string) $article->title_sk,
                ],
                [
                    'content_sk' => (string) $article->content_sk,
                    'payload' => json_encode([
                        'title_sk' => $article->title_sk,
                        'content_sk' => $article->content_sk,
                    ]),
                    'created_at' => now()->subDays(1),
                ]
            );
        }
    }
}
