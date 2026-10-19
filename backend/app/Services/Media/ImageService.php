<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Product images through PHP's bundled GD (BE-16): verify the real file type,
 * re-encode to WebP (which also strips metadata and any embedded payload) and
 * write three widths. Returns the public-disk path of the 800px rendition.
 */
final class ImageService
{
    public const WIDTHS = [400, 800, 1600];

    private const MAX_PIXELS = 40_000_000;

    public function storeProductImage(UploadedFile $file, int $productId): string
    {
        $info = @getimagesize($file->getRealPath());
        $types = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];
        if (! $info || ! isset($types[$info[2]]) || $info[0] * $info[1] > self::MAX_PIXELS) {
            throw ValidationException::withMessages(['image' => ['ارفع صورة JPG أو PNG أو WebP سليمة وبأبعاد معقولة.']]);
        }
        $src = @$types[$info[2]]($file->getRealPath());
        if (! $src) {
            throw ValidationException::withMessages(['image' => ['تعذّر قراءة الصورة.']]);
        }

        $base = "products/{$productId}/".Str::lower(Str::random(16));
        foreach (self::WIDTHS as $w) {
            $width = min($w, imagesx($src));
            $height = (int) round(imagesy($src) * $width / imagesx($src));
            $dst = imagecreatetruecolor($width, $height);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, imagesx($src), imagesy($src));
            ob_start();
            imagewebp($dst, null, 82);
            Storage::disk('public')->put("{$base}-{$w}.webp", ob_get_clean());
            imagedestroy($dst);
        }
        imagedestroy($src);

        return "{$base}-800.webp";
    }

    public function delete(string $path): void
    {
        $base = preg_replace('/-\d+\.webp$/', '', $path);
        Storage::disk('public')->delete(array_map(fn ($w) => "{$base}-{$w}.webp", self::WIDTHS));
    }
}
