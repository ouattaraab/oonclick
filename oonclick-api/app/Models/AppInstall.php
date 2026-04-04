<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppInstall extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'install_id',
        'platform',
        'app_version',
        'os_version',
        'device_model',
        'country',
        'first_seen_at',
        'last_seen_at',
        'launch_count',
    ];

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at'  => 'datetime',
            'launch_count'  => 'integer',
        ];
    }

    public static function registerOrUpdate(string $installId, array $data): self
    {
        $install = static::where('install_id', $installId)->first();

        if ($install) {
            // Mise à jour de last_seen_at et incrément du launch_count
            $install->last_seen_at = now();
            $install->launch_count += 1;

            if (isset($data['app_version'])) {
                $install->app_version = $data['app_version'];
            }

            // Sauvegarde directe sans passer par update() pour éviter tout override
            static::where('id', $install->id)->update([
                'last_seen_at' => now(),
                'launch_count' => $install->launch_count,
                'app_version'  => $install->app_version,
            ]);

            $install->refresh();

            return $install;
        }

        return static::create(array_merge($data, [
            'install_id'    => $installId,
            'first_seen_at' => now(),
        ]));
    }
}
