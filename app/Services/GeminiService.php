<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    public function enrichMealData(string $rawName)
    {
        $prompt = "Ty si asistent pre jedáleň UKF v Nitre. Tvojou úlohou je analyzovať skrátený názov jedla a vrátiť JSON.
        Vstupné dáta: '{$rawName}'
        
        Pravidlá:
        1. Rozšír slovenské skratky (napr. 'Kur.' -> 'Kurací', 'zemiak.' -> 'zemiaky').
        2. Prelož názov do angličtiny, ukrajinčiny a ruštiny.
        3. Identifikuj zoznam alergénov (čísla).
        4. Urči kategóriu (polievka, hlavné jedlo, dezert).
        5. Vráť len čistý JSON bez markdown formátovania.

        Formát JSON:
        {
            \"name_sk\": \"plný názov\",
            \"name_en\": \"full name\",
            \"name_ua\": \"повна назва\",
            \"name_ru\": \"полное название\",
            \"allergens\": \"1,3,7\",
            \"category\": \"hlavné jedlo\"
        }";

        try {
            $response = Http::post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . config('services.gemini.key'), [
                'contents' => [['parts' => [['text' => $prompt]]]]
            ]);

            $data = json_decode($response->json()['candidates'][0]['content']['parts'][0]['text'], true);

            return $data;
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return null;
        }
    }
}
