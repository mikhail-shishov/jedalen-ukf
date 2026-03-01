<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Http\Client\Response;

class GeminiService
{
    protected string $apiKey;
    protected string $baseApiUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    protected string $textModel = 'gemini-3-flash-preview';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
    }

    protected function getUrl(string $model, string $action = 'generateContent'): string
    {
        return "{$this->baseApiUrl}/{$model}:{$action}?key={$this->apiKey}";
    }

    public function enrichMealData(string $rawName)
    {
        $prompt = "Ty si asistent pre jedáleň UKF v Nitre. Tvojou úlohou je analyzovať skrátený názov jedla a vrátiť JSON.
        Vstupné dáta: '{$rawName}'
        
        Pravidlá:
        1. Rozšír slovenské skratky (napr. 'Kur.' -> 'Kurací', 'zemiak.' -> 'zemiakový').
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

        try {
            /** @var Response $response */
            $response = Http::post($this->getUrl($this->textModel), [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['response_mime_type' => 'application/json']
            ]);

            if (!$response->successful()) {
                throw new \Exception("Gemini API error " . $response->body());
            }

            $data = $response->json();
            $rawText = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            $mealData = json_decode($rawText, true);

            if (isset($mealData['name_en'])) {
                $mealData['image_path'] = $this->generateImage($mealData['name_en']);
            }

            return $mealData ?: [];
        } catch (\Exception $e) {
            Log::error("Gemini Service Error " . $e->getMessage());
            return null;
        }
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

            Log::warning("Imagen cannot generate image for {$mealName}");
        } catch (\Exception $e) {
            Log::error("Imagen Error " . $e->getMessage());
        }

        return '/assets/img/default-meal.jpg';
    }

    public function translateBatch(string $text)
    {
        /** @var Response $response */
        // $response = Http::post(...);

        $prompt = "Translate the following text, written in Slovak language, into English, Ukrainian, and Russian. 
                   Return only a JSON object with keys 'en', 'ua', 'ru'. Ignore small grammatic mistakes in the input.
                   Keep HTML tags. Text: " . $text;

        try {
            /** @var Response $response */
            $response = Http::post($this->getUrl($this->textModel), [
                'contents' => [['parts' => [['text' => $prompt]]]],
                'generationConfig' => ['response_mime_type' => 'application/json']
            ]);

            if ($response->failed()) {
                throw new \Exception("Gemini API error " . $response->body());
            }

            $result = $response->json();
            $rawText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '{}';

            return json_decode($rawText, true);
        } catch (\Exception $e) {
            Log::error("Gemini translation error " . $e->getMessage());
            throw $e;
        }
    }
}
