<?php

namespace Database\Seeders;

use App\Models\CampaignFormat;
use Illuminate\Database\Seeder;

class CampaignFormatSeeder extends Seeder
{
    public function run(): void
    {
        $formats = [
            ['slug' => 'video',   'label' => 'Vidéo',    'description' => 'Vidéo publicitaire classique. L\'abonné regarde la vidéo complète.', 'icon' => '▶️', 'multiplier' => 1.0,  'default_duration' => 30, 'accepted_media' => ['video'],          'is_active' => true,  'sort_order' => 1],
            ['slug' => 'flash',   'label' => 'Flash',    'description' => 'Format court et percutant. Idéal pour la notoriété.',                 'icon' => '⚡', 'multiplier' => 1.2,  'default_duration' => 15, 'accepted_media' => ['image'],          'is_active' => true,  'sort_order' => 2],
            ['slug' => 'quiz',    'label' => 'Quiz',     'description' => 'Question interactive après la vidéo. +30% d\'engagement.',            'icon' => '❓', 'multiplier' => 1.3,  'default_duration' => 30, 'accepted_media' => ['video', 'image'], 'is_active' => true,  'sort_order' => 3],
            ['slug' => 'scratch', 'label' => 'Grattage', 'description' => 'L\'abonné gratte pour révéler la récompense. +50% mémorisation.',    'icon' => '🎰', 'multiplier' => 1.5,  'default_duration' => 20, 'accepted_media' => ['image'],          'is_active' => true,  'sort_order' => 4],
            ['slug' => 'photo',   'label' => 'Photo',    'description' => 'Image publicitaire statique avec call-to-action.',                   'icon' => '📸', 'multiplier' => 1.1,  'default_duration' => 10, 'accepted_media' => ['image'],          'is_active' => false, 'sort_order' => 5],
            ['slug' => 'audio',   'label' => 'Audio',    'description' => 'Spot audio publicitaire. Idéal pour les podcasts.',                   'icon' => '🎧', 'multiplier' => 0.9,  'default_duration' => 30, 'accepted_media' => ['audio'],          'is_active' => false, 'sort_order' => 6],
            ['slug' => 'text',    'label' => 'Texte',    'description' => 'Message texte promotionnel avec lien.',                               'icon' => '📝', 'multiplier' => 0.8,  'default_duration' => 10, 'accepted_media' => [],                 'is_active' => false, 'sort_order' => 7],
        ];

        foreach ($formats as $format) {
            CampaignFormat::updateOrCreate(['slug' => $format['slug']], $format);
        }
    }
}
