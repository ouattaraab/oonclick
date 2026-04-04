<?php

namespace Database\Seeders;

use App\Models\AudienceCriterion;
use Illuminate\Database\Seeder;

class AudienceCriteriaSeeder extends Seeder
{
    public function run(): void
    {
        $criteria = [
            // Built-in criteria (mapped to physical columns on subscriber_profiles)
            ['name' => 'gender',    'label' => 'Genre',              'type' => 'select',      'options' => ['male', 'female', 'other'],  'category' => 'Démographie',         'storage_column' => 'gender',    'is_active' => true,  'is_required_for_profile' => false, 'sort_order' => 1],
            ['name' => 'age',       'label' => 'Tranche d\'âge',     'type' => 'range',       'options' => null,                         'category' => 'Démographie',         'storage_column' => 'date_of_birth', 'is_active' => true,  'is_required_for_profile' => false, 'sort_order' => 2],
            ['name' => 'city',      'label' => 'Ville',              'type' => 'text',        'options' => null,                         'category' => 'Localisation',        'storage_column' => 'city',      'is_active' => true,  'is_required_for_profile' => false, 'sort_order' => 3],
            ['name' => 'operator',  'label' => 'Opérateur mobile',   'type' => 'select',      'options' => ['mtn', 'moov', 'orange', 'other'], 'category' => 'Connectivité', 'storage_column' => 'operator',  'is_active' => true,  'is_required_for_profile' => false, 'sort_order' => 4],
            ['name' => 'interests', 'label' => 'Centres d\'intérêt', 'type' => 'multiselect', 'options' => ['Technologie', 'Sport', 'Mode', 'Cuisine', 'Finance', 'Musique', 'Éducation', 'Santé', 'Voyage', 'Auto', 'Gaming', 'Beauté', 'Cinéma', 'Business'], 'category' => 'Style de vie', 'storage_column' => 'interests', 'is_active' => true,  'is_required_for_profile' => false, 'sort_order' => 5],

            // Dynamic criteria (stored in subscriber_profiles.custom_fields JSON)
            ['name' => 'profession',    'label' => 'Profession',           'type' => 'select',      'options' => ['Étudiant', 'Salarié', 'Entrepreneur', 'Fonctionnaire', 'Artisan', 'Commerçant', 'Agriculteur', 'Sans emploi', 'Retraité', 'Autre'], 'category' => 'Vie professionnelle', 'storage_column' => null, 'is_active' => false, 'is_required_for_profile' => false, 'sort_order' => 10],
            ['name' => 'income_range',  'label' => 'Tranche de revenus',   'type' => 'select',      'options' => ['Moins de 50 000 FCFA', '50 000 - 150 000 FCFA', '150 000 - 500 000 FCFA', 'Plus de 500 000 FCFA'], 'category' => 'Vie professionnelle', 'storage_column' => null, 'is_active' => false, 'is_required_for_profile' => false, 'sort_order' => 11],
            ['name' => 'education',     'label' => 'Niveau d\'études',     'type' => 'select',      'options' => ['Primaire', 'Secondaire', 'Universitaire', 'Master / Doctorat'], 'category' => 'Éducation', 'storage_column' => null, 'is_active' => false, 'is_required_for_profile' => false, 'sort_order' => 12],
            ['name' => 'marital_status','label' => 'Situation familiale',  'type' => 'select',      'options' => ['Célibataire', 'Marié(e)', 'Divorcé(e)', 'Veuf/Veuve'], 'category' => 'Démographie', 'storage_column' => null, 'is_active' => false, 'is_required_for_profile' => false, 'sort_order' => 13],
            ['name' => 'ethnicity',     'label' => 'Ethnie',               'type' => 'select',      'options' => ['Akan', 'Krou', 'Mandé du Nord', 'Mandé du Sud', 'Gur / Voltaïque', 'Autre'], 'category' => 'Démographie', 'storage_column' => null, 'is_active' => false, 'is_required_for_profile' => false, 'sort_order' => 14],
            ['name' => 'birth_place',   'label' => 'Lieu de naissance',    'type' => 'text',        'options' => null, 'category' => 'Localisation', 'storage_column' => null, 'is_active' => false, 'is_required_for_profile' => false, 'sort_order' => 15],
            ['name' => 'favorite_food', 'label' => 'Plat préféré',         'type' => 'select',      'options' => ['Attiéké', 'Foutou', 'Alloco', 'Riz', 'Garba', 'Placali', 'Autre'], 'category' => 'Style de vie', 'storage_column' => null, 'is_active' => false, 'is_required_for_profile' => false, 'sort_order' => 16],
        ];

        foreach ($criteria as $c) {
            AudienceCriterion::updateOrCreate(['name' => $c['name']], $c);
        }
    }
}
