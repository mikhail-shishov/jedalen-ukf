<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GeminiService
{
    protected string $apiKey;
    protected ?string $pollinationsApiKey;
    protected string $baseApiUrl = 'https://generativelanguage.googleapis.com/v1beta/models';
    protected string $pollinationsApiUrl = 'https://gen.pollinations.ai/image';

    protected array $models = [
        'gemini-3-flash-preview',
        'gemini-2.5-flash',
        'gemini-2.5-flash-lite'
    ];

    public function __construct()
    {
        $this->apiKey = config('services.gemini.key');
        $this->pollinationsApiKey = config('services.pollinations.key');
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
                $imagePath = $this->generateImage($data['name_en']);

                if ($imagePath !== '') {
                    $data['image_path'] = $imagePath;
                }
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

    public function suggestAllergens(string $rawName): array
    {
        $prompt = "Ty si asistent pre jedáleň. Podľa názvu jedla navrhni možné alergény.
                   Vstup: '{$rawName}'

                   Vráť len JSON vo formáte:
                   {\"allergens\": [1,3,7]}

                   Ak si si neistý, vráť menej položiek. Nepíš žiadny iný text.";

        $data = $this->callWithFallback($prompt, ['temperature' => 0.1]);

        if (empty($data['allergens'])) {
            return [];
        }

        $allergens = $data['allergens'];
        if (is_string($allergens)) {
            $allergens = preg_split('/\s*,\s*/', $allergens);
        }

        if (!is_array($allergens)) {
            return [];
        }

        return collect($allergens)
            ->map(fn ($item) => preg_replace('/\D+/', '', (string) $item))
            ->filter(fn ($item) => $item !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function generateImage(string $mealNameEn): string
    {
        if (empty(trim($mealNameEn))) {
            return '';
        }

        if (empty($this->pollinationsApiKey)) {
            Log::warning('Pollinations API key is not configured.');
            return '';
        }

        $prompt = rawurlencode("food photography of {$mealNameEn}, appetizing");
        $seed   = rand(1, 9999);
        $models = ['flux', 'gptimage'];

        foreach ($models as $model) {
            try {
                $url = "{$this->pollinationsApiUrl}/{$prompt}?width=800&height=600&nologo=true&seed={$seed}&model={$model}";

                $response = Http::timeout(45)
                    ->withToken($this->pollinationsApiKey)
                    ->withOptions(['allow_redirects' => true])
                    ->get($url);

                $contentType   = $response->header('Content-Type') ?? '';
                $imageContents = $response->body();

                if ($response->successful() && str_starts_with($contentType, 'image/') && strlen($imageContents) > 5000) {
                    $fileName = 'meals/' . Str::slug($mealNameEn) . '_' . time() . '.jpg';
                    Storage::disk('public')->put($fileName, $imageContents);
                    Log::info("Image generated via Pollinations model={$model} for: {$mealNameEn}");
                    return $fileName;
                }

                Log::warning("Pollinations model={$model} failed (HTTP {$response->status()}, type={$contentType}, size=" . strlen($imageContents) . ") for: {$mealNameEn}. Body: {$imageContents}");
            } catch (\Exception $e) {
                Log::error("Pollinations model={$model} exception: " . $e->getMessage());
            }
        }

        return '';
    }
}
