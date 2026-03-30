<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AiService;
use Illuminate\Http\Request;

class GeminiController extends Controller
{
    public function translate(Request $request, AiService $gemini)
    {
        $request->validate([
            'text' => 'required|string'
        ]);

        try {
            $translations = $gemini->translateBatch($request->text);
            return response()->json($translations);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}