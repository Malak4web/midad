<?php

namespace App\Http\Resources;

use App\Models\CategorySpotlight;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CategorySpotlight */
class SpotlightResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'sample' => $this->is_sample,
            'tagline' => $this->tagline,
            'palette' => $this->palette,
            'texture' => $this->texture,
            'ingredients' => $this->ingredients ?? [],
            'sceneImage' => $this->scene_image,
            'bottleImage' => $this->bottle_image,
            'pyramid' => ProductDetailResource::pyramid($this->product),
            'product' => new ProductResource($this->product),
        ];
    }
}
