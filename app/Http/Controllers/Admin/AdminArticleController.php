<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleRevision;
use App\Models\Canteen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AdminArticleController extends Controller
{
    public function index(Request $request)
    {
        $searchQuery = trim((string) $request->get('q', ''));

        $articlesQuery = Article::with(['user', 'canteens'])
            ->orderBy('created_at', 'desc');

        if ($searchQuery !== '') {
            $safe = '%' . addcslashes($searchQuery, '%_\\') . '%';
            $articlesQuery->where(function ($query) use ($safe, $searchQuery) {
                $query->where('title_sk', 'like', $safe)
                    ->orWhere('title_en', 'like', $safe)
                    ->orWhere('title_ua', 'like', $safe)
                    ->orWhere('title_ru', 'like', $safe)
                    ->orWhere('slug', 'like', $safe)
                    ->orWhereHas('user', function ($userQuery) use ($safe) {
                        $userQuery->where('login_id', 'like', $safe)
                            ->orWhere('first_name', 'like', $safe)
                            ->orWhere('last_name', 'like', $safe)
                            ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", [$safe]);
                    })
                    ->orWhereHas('canteens', function ($canteenQuery) use ($safe) {
                        $canteenQuery->where('name', 'like', $safe);
                    });

                if (ctype_digit($searchQuery)) {
                    $query->orWhere('id', (int) $searchQuery);
                }
            });
        }

        $articles = $articlesQuery->paginate(20)->withQueryString();

        return view('admin.articles', compact('articles', 'searchQuery'));
    }

    public function create()
    {
        $canteens = Canteen::all();
        return view('admin.articles_create', compact('canteens'));
    }

    public function destroy($id)
    {
        $article = Article::findOrFail($id);
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
            'canteens.*' => 'exists:canteens,id,is_active,1',
            'image'      => 'nullable|image|max:2048',
            'content_sk' => 'required|string',
            'content_en' => 'nullable|string',
            'content_ua' => 'nullable|string',
            'content_ru' => 'nullable|string',
        ]);

        $article = new Article($request->except(['canteens', 'image', 'slug']));

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
            'canteens.*' => 'exists:canteens,id,is_active,1',
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

        $inactiveCanteenIds = $article->canteens()
            ->where('is_active', false)
            ->pluck('canteens.id')
            ->values()
            ->all();

        if ($request->has('canteens')) {
            $syncIds = array_values(array_unique(array_merge(
                array_map('intval', (array) $request->canteens),
                array_map('intval', $inactiveCanteenIds)
            )));

            $article->canteens()->sync($syncIds);
        } else {
            if (!empty($inactiveCanteenIds)) {
                $article->canteens()->sync($inactiveCanteenIds);
            } else {
                $article->canteens()->detach();
            }
        }

        return redirect()->route('admin.articles')->with('success', 'Článok bol aktualizovaný');
    }

    public function upload(Request $request)
    {
        if (!$request->hasFile('upload')) {
            return response()->json([
                'error' => [
                    'message' => 'No file uploaded'
                ]
            ], 400);
        }

        $file = $request->file('upload');

        if (!$file->isValid()) {
            return response()->json([
                'error' => [
                    'message' => 'Invalid file'
                ]
            ], 400);
        }

        $path = $file->store('articles/editor', 'public');

        return response()->json([
            'url' => asset('storage/' . $path)
        ]);
    }
}
