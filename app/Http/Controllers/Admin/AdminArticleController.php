<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\Canteen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminArticleController extends Controller
{
    public function index()
    {
        $articles = Article::with(['user', 'canteens'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('admin.articles', compact('articles'));
    }

    public function create()
    {
        $canteens = Canteen::all();
        return view('admin.articles_create', compact('canteens'));
    }

    public function destroy($id)
    {
        $article = Article::findOrFail($id);

        if ($article->image_path) {
            Storage::disk('public')->delete($article->image_path);
        }

        $article->canteens()->detach();
        $article->delete();

        return redirect()->route('admin.articles')->with('success', 'Článok bol úspešne zmazaný.');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'slug'       => 'nullable|string|unique:articles,slug',
            'title_sk'   => 'required|string|max:255',
            'title_en'   => 'nullable|string|max:255',
            'title_ua'   => 'nullable|string|max:255',
            'title_ru'   => 'nullable|string|max:255',
            'canteens'   => 'nullable|array',
            'canteens.*' => 'exists:canteens,id',
            'image'      => 'nullable|image|max:2048',
            'content_sk' => 'required|string',
            'content_en' => 'nullable|string',
            'content_ua' => 'nullable|string',
            'content_ru' => 'nullable|string',
        ]);

        $article = new Article($request->except(['canteens', 'image', 'slug'])); // Исключаем slug из массового заполнения

        $slugSource = $request->filled('slug') ? $request->slug : $request->title_sk;
        $article->slug = Str::slug($slugSource);

        if (Article::where('slug', $article->slug)->exists()) {
            $article->slug .= '-' . time();
        }

        $article->users_id = Auth::id();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('articles', 'public');
            $article->image_path = $path;
        }

        $article->save();

        if ($request->has('canteens')) {
            $article->canteens()->sync($request->canteens);
        }

        return redirect()->route('admin.articles')->with('success', 'Článok bol vytvorený');
    }

    public function edit($id)
    {
        $article = Article::with('canteens')->findOrFail($id);
        $canteens = Canteen::all();
        return view('admin.articles_edit', compact('article', 'canteens'));
    }

    public function update(Request $request, $id)
    {
        $article = Article::findOrFail($id);

        ArticleRevision::create([
            'article_id' => $article->id,
            'users_id'   => Auth::id(),
            'title_sk'   => $article->title_sk,
            'content_sk' => $article->content_sk,
            'payload'    => [
                'title_en' => $article->title_en,
                'title_ua' => $article->title_ua,
                'title_ru' => $article->title_ru,
                'content_en' => $article->content_en,
                'content_ua' => $article->content_ua,
                'content_ru' => $article->content_ru,
            ]
        ]);

        $data = $request->validate([
            'slug'       => 'nullable|string|unique:articles,slug,' . $id,
            'title_sk'   => 'required|string|max:255',
            'title_en'   => 'nullable|string|max:255',
            'title_ua'   => 'nullable|string|max:255',
            'title_ru'   => 'nullable|string|max:255',
            'canteens'   => 'nullable|array',
            'canteens.*' => 'exists:canteens,id',
            'image'      => 'nullable|image|max:2048',
            'content_sk' => 'required|string',
            'content_en' => 'nullable|string',
            'content_ua' => 'nullable|string',
            'content_ru' => 'nullable|string',
        ]);

        $article->fill($request->except(['canteens', 'image', 'slug']));

        $slugSource = $request->filled('slug') ? $request->slug : $request->title_sk;
        $article->slug = Str::slug($slugSource);

        $article->is_published = $request->has('is_published');

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('articles', 'public');
            $article->image_path = $path;
        }

        $article->save();

        if ($request->has('canteens')) {
            $article->canteens()->sync($request->canteens);
        } else {
            $article->canteens()->detach();
        }

        return redirect()->route('admin.articles')->with('success', 'Článok bol aktualizovaný');
    }
}
