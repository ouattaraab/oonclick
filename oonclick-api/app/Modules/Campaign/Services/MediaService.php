<?php

namespace App\Modules\Campaign\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Disk used for media storage.
     * Uses MEDIA_DISK env var: 'r2' for production CDN, 'public' for local.
     */
    private function disk(): string
    {
        return config('filesystems.disks.' . config('oonclick.media_disk', 'public'))
            ? config('oonclick.media_disk', 'public')
            : 'public';
    }

    /**
     * Uploade un fichier et retourne ses métadonnées.
     */
    public function upload(UploadedFile $file, string $folder): array
    {
        $filename = Str::uuid() . '.' . ($file->guessExtension() ?: $file->getClientOriginalExtension());
        $path     = $folder . '/' . $filename;
        $disk     = $this->disk();

        Storage::disk($disk)->put($path, file_get_contents($file->getRealPath()));

        // Build URL based on disk type
        if ($disk === 'public') {
            $url = url('storage/' . $path);
        } else {
            $url = rtrim(config('oonclick.cdn_url'), '/') . '/' . $path;
        }

        return [
            'path' => $path,
            'url'  => $url,
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ];
    }

    /**
     * Supprime un fichier du disk.
     */
    public function delete(string $path): void
    {
        Storage::disk($this->disk())->delete($path);
    }

    /**
     * Génère une URL pour accéder au fichier.
     * Sur le disk 'public', retourne une URL directe.
     * Sur R2, retourne une URL signée temporaire.
     */
    public function generatePresignedUrl(string $path, int $minutes = 15): string
    {
        $disk = $this->disk();

        if ($disk === 'public') {
            return url('storage/' . $path);
        }

        return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes($minutes));
    }
}
