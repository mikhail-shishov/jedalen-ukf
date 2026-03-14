<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::query()
            ->where('is_published', true)
            ->orderByDesc('created_at')
            ->limit(12)
            ->get([
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
            ]);

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

        return response()->json($article);
    }
}
