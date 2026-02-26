<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Canteen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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

    public function store(Request $request)
    {
        $data = $request->validate([
            'title_sk'   => 'required|string|max:255',
            'title_en'   => 'nullable|string|max:255',
            'title_ua'   => 'nullable|string|max:255',
            'title_ru'   => 'nullable|string|max:255',
            'canteens'    => 'nullable|array',
            'canteens.*'  => 'exists:canteens,id',
            'image'      => 'nullable|image|max:2048',
            'content_sk' => 'required|string',
            'content_en' => 'nullable|string',
            'content_ua' => 'nullable|string',
            'content_ru' => 'nullable|string',
        ]);

        $article = new Article($request->except(['canteens', 'image']));
        $article->users_id = Auth::id();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('articles', 'public');
            $article->image_path = $path;
        }

        $article->save();

        if ($request->has('canteens')) {
            $article->canteens()->sync($request->canteens);
        }

        return redirect()->route('admin.articles')->with('success', 'Článok bol vytvorený!');
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

        $data = $request->validate([
            'title_sk'   => 'required|string|max:255',
            'title_en'   => 'nullable|string|max:255',
            'title_ua'   => 'nullable|string|max:255',
            'title_ru'   => 'nullable|string|max:255',
            'canteens'    => 'nullable|array',
            'canteens.*'  => 'exists:canteens,id',
            'image'      => 'nullable|image|max:2048',
            'content_sk' => 'required|string',
            'content_en' => 'nullable|string',
            'content_ua' => 'nullable|string',
            'content_ru' => 'nullable|string',
        ]);

        $article->fill($request->except(['canteens', 'image']));
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

        return redirect()->route('admin.articles')->with('success', 'Článok bol aktualizovaný!');
    }
}
