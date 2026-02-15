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
    | Ownable Model
    |--------------------------------------------------------------------------
    |
    | This is the default model that can be owned. You can override this
    | on a per-model basis by implementing the Ownable contract.
    |
    */
    'ownable_model' => env('OWNABLE_OWNABLE_MODEL', 'App\Models\Model'),

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
];