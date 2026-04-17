<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Support\Str;

class ArticleController extends Controller
{
    private function formatImagePath(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return asset('storage/' . $path);
    }

    public function index()
    {
        $canteenId = (int) request()->query('canteen_id', 0);

        $query = Article::query()
            ->where('is_published', true)
            ->orderByDesc('created_at');

        if ($canteenId > 0) {
            $query->whereHas('canteens', function ($q) use ($canteenId) {
                $q->where('canteens.id', $canteenId);
            });
        }

        $articles = $query->limit(12)->get([
            'id',
            'slug',
            'title_sk',
            'title_en',
            'title_ua',
            'title_ru',
            'content_sk',
            'content_en',
            'content_ua',
            'content_ru',
            'image_path',
        ]);

        $articles->each(function ($article) {
            $article->image_path = $this->formatImagePath($article->image_path);
        });

        return response()->json($articles);
    }

    public function show(string $slug)
    {
        $article = Article::query()
            ->where('is_published', true)
            ->where('slug', $slug)
            ->first([
                'id',
                'slug',
                'title_sk',
                'title_en',
                'title_ua',
                'title_ru',
                'content_sk',
                'content_en',
                'content_ua',
                'content_ru',
                'image_path',
                'created_at',
            ]);

        if (!$article) {
            return response()->json(['message' => 'Article not found'], 404);
        }

        $article->image_path = $this->formatImagePath($article->image_path);

        return response()->json($article);
    }
}
