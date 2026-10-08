<?php

namespace App\Services;

use App\Models\Country;
use App\Models\CountryImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Bilder eines Landes ablegen und wieder entfernen. Rasterbilder bekommen
 * eine verkleinerte Fassung (GD), SVG bleibt wie es ist.
 */
class CountryImageService
{
    public const DISK = 'public';

    public const DIRECTORY = 'country-images';

    /** Breite der verkleinerten Fassung in Pixeln */
    public const THUMB_WIDTH = 640;

    public const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];

    public function store(Country $country, UploadedFile $file, string $kind = CountryImage::KIND_GALLERY, ?int $userId = null): CountryImage
    {
        $directory = self::DIRECTORY.'/'.strtolower($country->iso_code ?: 'xx');
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $name = Str::uuid()->toString();
        $path = $file->storeAs($directory, $name.'.'.$extension, self::DISK);

        $mime = (string) $file->getMimeType();
        $dimensions = $mime === 'image/svg+xml' ? null : @getimagesize($file->getRealPath());
        $thumbPath = $dimensions ? $this->makeThumbnail($file->getRealPath(), $mime, $directory.'/'.$name.'_thumb.jpg') : null;

        if ($kind === CountryImage::KIND_HERO) {
            // Es gibt nur ein Titelbild – das bisherige wird zur Galerie.
            $country->images()->where('kind', CountryImage::KIND_HERO)->update(['kind' => CountryImage::KIND_GALLERY]);
        }

        return $country->images()->create([
            'kind' => $kind,
            'disk' => self::DISK,
            'path' => $path,
            'thumb_path' => $thumbPath,
            'original_name' => Str::limit($file->getClientOriginalName(), 255, ''),
            'mime_type' => $mime,
            'width' => $dimensions[0] ?? null,
            'height' => $dimensions[1] ?? null,
            'size' => $file->getSize(),
            'sort_order' => (int) $country->images()->max('sort_order') + 1,
            'created_by' => $userId,
        ]);
    }

    public function delete(CountryImage $image): void
    {
        $disk = Storage::disk($image->disk);

        foreach (array_filter([$image->path, $image->thumb_path]) as $path) {
            $disk->delete($path);
        }

        $image->delete();
    }

    /**
     * Verkleinerte JPEG-Fassung; null, wenn GD das Format nicht lesen kann.
     */
    protected function makeThumbnail(string $sourcePath, string $mime, string $targetPath): ?string
    {
        if (! function_exists('imagecreatefromjpeg')) {
            return null;
        }

        $source = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($sourcePath),
            'image/png' => @imagecreatefrompng($sourcePath),
            'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($sourcePath) : false,
            default => false,
        };

        if (! $source) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);
        $targetWidth = min(self::THUMB_WIDTH, $width);
        $targetHeight = (int) round($height * $targetWidth / max(1, $width));

        $thumb = imagecreatetruecolor($targetWidth, $targetHeight);
        // Transparente Bereiche werden auf Weiss gelegt – JPEG kennt keine Transparenz.
        $white = imagecolorallocate($thumb, 255, 255, 255);
        imagefill($thumb, 0, 0, $white);
        imagecopyresampled($thumb, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        ob_start();
        imagejpeg($thumb, null, 82);
        $binary = (string) ob_get_clean();

        imagedestroy($thumb);
        imagedestroy($source);

        Storage::disk(self::DISK)->put($targetPath, $binary);

        return $targetPath;
    }
}
