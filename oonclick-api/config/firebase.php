<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Firebase Projects
    |--------------------------------------------------------------------------
    |
    | Each key in this array is an alias for a Firebase project.
    | The 'app' key is the default project used by the package.
    |
    */

    'projects' => [
        'app' => [
            'credentials' => storage_path('firebase-service-account.json'),
        ],
    ],

];
