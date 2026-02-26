<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Canteen;
use Illuminate\Http\Request;

class AdminCanteenController extends Controller
{
    public function index()
    {
        $canteens = Canteen::all();
        return view('admin.canteens.index', compact('canteens'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'address' => 'required|string|max:255',
        ]);

        Canteen::create($data);
        return redirect()->back()->with('success', 'Jedáleň bola pridaná.');
    }

    public function update(Request $request, $id)
    {
        $canteen = Canteen::findOrFail($id);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'address' => 'required|string|max:255',
        ]);

        $canteen->update($data);
        return redirect()->back()->with('success', 'Jedáleň bola aktualizovaná.');
    }

    public function destroy($id)
    {
        $canteen = Canteen::findOrFail($id);
        $canteen->delete();
        return redirect()->back()->with('success', 'Jedáleň bola odstránená.');
    }
}
