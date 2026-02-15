<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Owner Model
    |--------------------------------------------------------------------------
    |
    | This is the model that will be used as the owner in ownership relationships.
    | Typically this would be your User model or any other model that can own things.
    |
    */
    'owner_model' => env('OWNABLE_OWNER_MODEL', 'App\Models\User'),

    /*
    |--------------------------------------------------------------------------
    | Ownable Models
    |--------------------------------------------------------------------------
    |
    | This is a list of models that can be owned. The package will automatically
    | detect these models in responses and attach their ownership information.
    |
    */
    'ownable_models' => [
        // 'App\Models\Post',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration controls the package's registration endpoints.
    |
    */
    'routes' => [
        'prefix' => 'api/ownable',
        'middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Automatic Attachment Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration controls the automatic attachment of ownership info
    | to application responses.
    |
    */
    'automatic_attachment' => [
        'enabled' => env('OWNABLE_AUTO_ATTACH', true),
        'key' => 'ownership',
    ],
];