<?php

namespace App\Libraries;

use CodeIgniter\HTTP\Files\UploadedFile;
use RuntimeException;

/**
 * Re-encodes an uploaded image as WebP, trying progressively smaller
 * dimensions/quality until it fits under the size budget. Throws if
 * nothing in the ladder gets under budget - callers should reject the
 * upload rather than accept an oversized or overly-degraded image.
 */
class ImageCompressor
{
    private const MAX_BYTES     = 500 * 1024;
    private const QUALITY_STEPS = [82, 72, 62, 52, 42];
    private const SCALE_STEPS   = [null, 1600, 1280, 1024, 800, 640];

    public function compress(UploadedFile $file): UploadedFile
    {
        if (!function_exists('imagewebp')) {
            throw new RuntimeException('Server tidak mendukung kompresi WebP.');
        }

        $bytes  = file_get_contents($file->getTempName());
        $source = $bytes === false ? false : @imagecreatefromstring($bytes);

        if ($source === false) {
            throw new RuntimeException('File "' . $file->getClientName() . '" bukan gambar yang valid.');
        }

        imagepalettetotruecolor($source);
        imagealphablending($source, true);
        imagesavealpha($source, true);

        $originalWidth  = imagesx($source);
        $originalHeight = imagesy($source);

        $best = null;

        foreach (self::SCALE_STEPS as $maxDim) {
            $image = $this->resized($source, $originalWidth, $originalHeight, $maxDim);

            foreach (self::QUALITY_STEPS as $quality) {
                $encoded = $this->encodeWebp($image, $quality);

                if ($encoded !== false && strlen($encoded) <= self::MAX_BYTES) {
                    $best = $encoded;
                    break 2;
                }
            }

            if ($image !== $source) {
                imagedestroy($image);
            }
        }

        imagedestroy($source);

        if ($best === null) {
            throw new RuntimeException('Gambar "' . $file->getClientName() . '" tidak bisa dikompres di bawah 500KB tanpa kualitas terlalu rendah. Silakan upload gambar yang lebih kecil/sederhana.');
        }

        $tempPath = WRITEPATH . 'uploads/' . uniqid('compressed_', true) . '.webp';
        if (!is_dir(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }
        file_put_contents($tempPath, $best);

        $webpName = pathinfo($file->getClientName(), PATHINFO_FILENAME) . '.webp';

        return new UploadedFile($tempPath, $webpName, 'image/webp', strlen($best), 0, null);
    }

    /**
     * @param resource|\GdImage $source
     * @return resource|\GdImage
     */
    private function resized($source, int $originalWidth, int $originalHeight, ?int $maxDim)
    {
        if ($maxDim === null || ($originalWidth <= $maxDim && $originalHeight <= $maxDim)) {
            return $source;
        }

        $ratio     = min($maxDim / $originalWidth, $maxDim / $originalHeight);
        $newWidth  = max(1, (int) round($originalWidth * $ratio));
        $newHeight = max(1, (int) round($originalHeight * $ratio));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $source, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

        return $resized;
    }

    /**
     * @param resource|\GdImage $image
     * @return string|false
     */
    private function encodeWebp($image, int $quality)
    {
        ob_start();
        $ok = imagewebp($image, null, $quality);
        $data = ob_get_clean();

        return $ok ? $data : false;
    }
}
