<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GeminiService
{
    protected string $apiKey;
    protected string $baseApiUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    
    protected array $models = [
        'gemini-3-flash-preview',
        'gemini-2.5-flash',
        'gemini-2.5-flash-lite'
    ];

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    protected function callWithFallback(string $prompt, array $config = [])
    {
        foreach ($this->models as $model) {
            try {
                $url = "{$this->baseApiUrl}/{$model}:generateContent?key={$this->apiKey}";

                $response = Http::timeout(30)->post($url, [
                    'contents' => [['parts' => [['text' => $prompt]]]],
                    'generationConfig' => array_merge([
                        'response_mime_type' => 'application/json'
                    ], $config)
                ]);

                // if Gemini limit exceeded
                if ($response->status() === 429) {
                    Log::warning("Gemini quota exceeded for {$model}. Trying next...");
                    continue;
                }

                if (!$response->successful()) {
                    Log::error("Gemini API error ({$model}) - " . $response->body());
                    continue;
                }

                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($text) {
                    return json_decode($text, true);
                }
            } catch (\Exception $e) {
                Log::error("Error with model {$model} - " . $e->getMessage());
                continue;
            }
        }

        return null;
    }

    public function enrichMealData(string $rawName)
    {
        $prompt = "Ty si asistent pre jedáleň UKF v Nitre. Tvojou úlohou je analyzovať skrátený názov jedla a vrátiť JSON.
        Vstupné dáta: '{$rawName}'
        
        Pravidlá:
        1. Rozšír slovenské skratky (napr. 'Kur.' -> 'Kurací', 'zemiak.pol.' -> 'zemiaková polievka') tak, aby dávali zmysel.
        2. Prelož názov do angličtiny, ukrajinčiny a ruštiny.
        3. Identifikuj zoznam alergénov (čísla).
        4. Vráť len čistý JSON bez markdown formátovania.

        Formát JSON:
        {
            \"name_sk\": \"plný názov\",
            \"name_en\": \"full name\",
            \"name_ua\": \"повна назва\",
            \"name_ru\": \"полное название\",
            \"allergens\": \"1,3,7\"
        }";

        $data = $this->callWithFallback($prompt);

        if ($data) {
            if (!empty($data['name_en'])) {
                $data['image_path'] = $this->generateImage($data['name_en']);
            }
            return $data;
        }

        return null;
    }

    public function translateBatch(string $text)
    {
        $prompt = "Translate the following text, written in Slovak language, into English, Ukrainian, and Russian. 
                   Return only a JSON object with keys 'en', 'ua', 'ru'. Ignore small grammatic mistakes in the input.
                   Keep HTML tags. Text: " . $text;

        return $this->callWithFallback($prompt);
    }

    protected function generateImage(string $mealName): string
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/imagen-3.0-generate-001:predict?key=" . $this->apiKey;

        try {
            $imagePrompt = "Classic food photography of {$mealName}, plated as in Slovak or Czech canteens, natural lighting, wooden table background.";

            $response = Http::post($url, [
                'instances' => [['prompt' => $imagePrompt]],
                'parameters' => ['sampleCount' => 1, 'aspectRatio' => '4:3']
            ]);

            if ($response->successful()) {
                $imageData = $response->json();
                if (isset($imageData['predictions'][0]['bytesBase64Encoded'])) {
                    $imageContent = base64_decode($imageData['predictions'][0]['bytesBase64Encoded']);
                    $fileName = 'meals/' . Str::slug($mealName) . '_' . time() . '.png';

                    Storage::disk('public')->put($fileName, $imageContent);

                    return '/storage/' . $fileName;
                }
            }
        } catch (\Exception $e) {
            Log::error("Imagen Error: " . $e->getMessage());
        }

        return '/assets/img/default-meal.jpg';
    }
}