<?php

declare(strict_types=1);

namespace App\Support;

use GdImage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Riduce in posto un'immagine caricata: stesso percorso, così il database non
 * cambia. Raddrizza le foto del telefono (EXIF) e riporta il lato lungo sotto il
 * massimo. Se non riesce, il file resta com'era.
 */
final class ImageShrinker
{
    public const QUALITY = 80;

    /** Lati lunghi massimi: copertine, sfondi a tutto schermo, ritratti, mappe da ingrandire. */
    public const COVER = 1600;

    public const BACKGROUND = 1920;

    public const PORTRAIT = 800;

    public const MAP = 3000;

    /** Sotto questo peso, se non va ridotta né raddrizzata, non si ricodifica. */
    public const LIGHT_BYTES = 500 * 1024;

    /**
     * @return int|null byte risparmiati, null se il file non è stato toccato
     */
    public function shrink(string $disk, ?string $path, int $maxSide, bool $dryRun = false): ?int
    {
        $storage = Storage::disk($disk);

        if (blank($path) || ! $storage->exists($path)) {
            return null;
        }

        $file = $storage->path($path);
        $info = @getimagesize($file);

        if ($info === false || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            return null;
        }

        [$width, $height, $type] = $info;
        $before = (int) filesize($file);
        $orientation = $this->orientation($file, $type);
        $scale = min(1, $maxSide / max($width, $height));

        if ($scale === 1 && $orientation === 1 && $before <= self::LIGHT_BYTES) {
            return null;
        }

        if (! $this->hasMemoryFor($width, $height)) {
            Log::warning("Immagine troppo grande da ridurre: {$path} ({$width}x{$height}).");

            return null;
        }

        try {
            $out = $this->resized($this->open($file, $type), $width, $height, $scale, $type);
            $out = $this->upright($out, $orientation);

            $tmp = $file.'.tmp';
            $this->save($out, $tmp, $type);
            $after = (int) filesize($tmp);

            // Senza riduzione né rotazione, una ricodifica più pesante non serve.
            if ($scale === 1 && $orientation === 1 && $after >= $before) {
                unlink($tmp);

                return null;
            }

            if ($dryRun) {
                unlink($tmp);
            } else {
                rename($tmp, $file);
            }

            return $before - $after;
        } catch (Throwable $e) {
            Log::warning("Immagine non ridotta: {$path}. {$e->getMessage()}");
            @unlink($file.'.tmp');

            return null;
        }
    }

    private function orientation(string $file, int $type): int
    {
        if ($type !== IMAGETYPE_JPEG || ! function_exists('exif_read_data')) {
            return 1;
        }

        return (int) (@exif_read_data($file)['Orientation'] ?? 1);
    }

    // Due copie in memoria a 4 byte per pixel, più margine.
    private function hasMemoryFor(int $width, int $height): bool
    {
        $needed = $width * $height * 4 * 2.5 + memory_get_usage();
        $limit = $this->bytes((string) ini_get('memory_limit'));

        if ($limit < 0 || $needed < $limit) {
            return true;
        }

        return @ini_set('memory_limit', (string) (int) ceil($needed)) !== false;
    }

    private function bytes(string $value): int
    {
        $number = (int) $value;

        return match (strtolower(substr(trim($value), -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }

    private function open(string $file, int $type): GdImage
    {
        $image = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($file),
            IMAGETYPE_PNG => imagecreatefrompng($file),
            IMAGETYPE_WEBP => imagecreatefromwebp($file),
        };

        if ($image === false) {
            throw new \RuntimeException('formato non leggibile');
        }

        return $image;
    }

    private function resized(GdImage $image, int $width, int $height, float $scale, int $type): GdImage
    {
        if ($scale >= 1) {
            return $image;
        }

        $w = max(1, (int) round($width * $scale));
        $h = max(1, (int) round($height * $scale));
        $out = imagecreatetruecolor($w, $h);

        // PNG e WebP possono essere trasparenti: la trasparenza va tenuta.
        if ($type !== IMAGETYPE_JPEG) {
            imagealphablending($out, false);
            imagesavealpha($out, true);
            imagefill($out, 0, 0, imagecolorallocatealpha($out, 0, 0, 0, 127));
        }

        imagecopyresampled($out, $image, 0, 0, 0, 0, $w, $h, $width, $height);

        return $out;
    }

    private function upright(GdImage $image, int $orientation): GdImage
    {
        $angle = match ($orientation) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : imagerotate($image, $angle, 0);
    }

    private function save(GdImage $image, string $file, int $type): void
    {
        $ok = match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $file, self::QUALITY),
            IMAGETYPE_PNG => imagepng($image, $file, 9),
            IMAGETYPE_WEBP => imagewebp($image, $file, self::QUALITY),
        };

        if (! $ok) {
            throw new \RuntimeException('scrittura fallita');
        }
    }
}
