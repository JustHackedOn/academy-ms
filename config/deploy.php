<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Deploy Webhook Token
    |--------------------------------------------------------------------------
    |
    | A long, random secret protecting the post-deploy endpoint at
    | /_deploy/{token}. On shared hosting (e.g. Hostinger) the Git deployment
    | syncs files but cannot run Composer or Artisan, so this endpoint applies
    | pending migrations and refreshes the caches after a deploy.
    |
    | Generate one with: php -r "echo bin2hex(random_bytes(24));"
    | Leave it empty to disable the endpoint entirely (recommended locally).
    |
    */

    'token' => env('DEPLOY_TOKEN'),

];
