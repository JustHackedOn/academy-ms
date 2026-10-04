<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DeployController extends Controller
{
    /**
     * Post-deploy webhook for shared hosting without SSH.
     *
     * Hostinger's Git auto-deploy only syncs files — it never runs Composer,
     * migrations or Artisan. After each deployment, hit this URL to apply
     * pending migrations and refresh the config/route/view caches:
     *
     *   /_deploy/<token>            → migrate + optimize
     *   /_deploy/<token>?seed=1     → also seed the portal users (first run)
     *   /_deploy/<token>?clear=1    → clear all caches (config/route/view)
     *
     * Inert (404) unless APP_ENV=production and DEPLOY_TOKEN is configured.
     */
    public function __invoke(Request $request, string $token): JsonResponse
    {
        $configured = (string) config('deploy.token');

        abort_unless(
            config('app.env') === 'production'
                && $configured !== ''
                && hash_equals($configured, $token),
            404
        );

        if ($request->boolean('clear')) {
            Artisan::call('optimize:clear');

            return response()->json([
                'ok' => true,
                'action' => 'optimize:clear',
                'output' => Artisan::output(),
            ]);
        }

        Artisan::call('migrate', ['--force' => true]);
        $output = Artisan::output();

        if ($request->boolean('seed')) {
            Artisan::call('db:seed', [
                '--class' => 'Database\\Seeders\\UserSeeder',
                '--force' => true,
            ]);
            $output .= Artisan::output();
        }

        Artisan::call('optimize');

        return response()->json([
            'ok' => true,
            'migrate' => $output,
            'optimize' => Artisan::output(),
        ]);
    }
}
