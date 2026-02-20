<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MealResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'badge' => $this->type,
            'allergens' => $this->allergens,
            'price' => number_format($this->price, 2, ',', ' '),
            'name_sk' => $this->name_sk,
            'name_en' => $this->name_en,
            'name_ua' => $this->name_ua,
            'name_ru' => $this->name_ru,
        ];
    }
}
