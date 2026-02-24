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
        // Загружаем статьи вместе с автором и столовой
        $articles = Article::with(['user', 'canteen'])
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
            'canteens_id' => 'required|exists:canteens,id',
            'image'      => 'nullable|image|max:2048',
            'content_sk' => 'required|string',
            'content_en' => 'nullable|string',
            'content_ua' => 'nullable|string',
            'content_ru' => 'nullable|string',
        ]);

        $article = new Article($data);
        $article->users_id = Auth::id();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('articles', 'public');
            $article->image_path = $path;
        }

        $article->save();

        return redirect()->route('admin.articles')->with('success', 'Článok bol vytvorený!');
    }
}
