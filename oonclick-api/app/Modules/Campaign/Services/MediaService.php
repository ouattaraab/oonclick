<?php

namespace App\Modules\Campaign\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MediaService
{
    /**
     * Uploade un fichier sur le disk R2 et retourne ses métadonnées.
     *
     * @param UploadedFile $file   Fichier uploadé
     * @param string       $folder Dossier de destination (ex: "campaigns/1/media")
     * @return array{path: string, url: string, size: int, mime: string}
     */
    public function upload(UploadedFile $file, string $folder): array
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = $folder . '/' . $filename;

        Storage::disk('r2')->put($path, file_get_contents($file->getRealPath()));

        return [
            'path' => $path,
            'url'  => rtrim(config('oonclick.cdn_url'), '/') . '/' . $path,
            'size' => $file->getSize(),
            'mime' => $file->getMimeType(),
        ];
    }

    /**
     * Supprime un fichier du disk R2.
     *
     * @param string $path Chemin du fichier sur R2
     */
    public function delete(string $path): void
    {
        Storage::disk('r2')->delete($path);
    }

    /**
     * Génère une URL signée temporaire pour un fichier privé sur R2.
     *
     * @param string $path    Chemin du fichier sur R2
     * @param int    $minutes Durée de validité en minutes (défaut : 15)
     * @return string         URL signée
     */
    public function generatePresignedUrl(string $path, int $minutes = 15): string
    {
        return Storage::disk('r2')->temporaryUrl($path, now()->addMinutes($minutes));
    }
}
