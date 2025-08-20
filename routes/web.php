<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectsController;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Services\MobileWorkerService;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::get('/prosjekter', [ProjectsController::class, 'index'])->name('projects.index');

Route::get('/mw-auth-test', function (MobileWorkerService $mw) {
    try {
        $token = $mw->getAccessToken();
        return ['success' => true, 'token_sample' => substr($token, 0, 24).'…'];
    } catch (\Throwable $e) {
        return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
    }
});

Route::get('/mw-config', function (MobileWorkerService $mw) {
    return $mw->getConfiguration();
});

Route::get('/mw-orders', function (MobileWorkerService $mw) {
    return $mw->fetchOrdersToProjects();
});

Route::get('/mw-test', function (Request $r) {
    $base  = rtrim(config('services.mobileworker.url'), '/');
    $path  = $r->query('path', config('services.mobileworker.projects')); // defaults to /public/v2/Orders
    $auth  = strtolower($r->query('auth', config('services.mobileworker.auth', 'bearer')));
    $token = config('services.mobileworker.token');

    $req = Http::timeout(20);
    $req = match ($auth) {
        'x-api-key' => $req->withHeaders(['x-api-key' => $token]),
        'raw'       => $req->withHeaders(['Authorization' => $token]),
        default     => $req->withToken($token), // Bearer <token>
    };

    $url = $base . $path;
    $resp = $req->get($url);

    return response()->json([
        'url'     => $url,
        'auth'    => $auth,
        'status'  => $resp->status(),
        'ok'      => $resp->ok(),
        'headers' => $resp->headers(),
        'body'    => mb_strimwidth($resp->body(), 0, 1200, '…'),
    ], $resp->ok() ? 200 : 500);
});

Route::get('/mw-config', function (MobileWorkerService $mw) {
    return response()->json($mw->getConfiguration());
});


Route::get('/mw-config-test', function (Request $r) {
    $base = rtrim(config('services.mobileworker.url'), '/');
    $url  = $base . '/public/v2/Configuration';

    $key  = config('services.mobileworker.key');
    $ext  = config('services.mobileworker.ext');

    $variants = [
        ['x-api-key' => $key, 'ExternalSystemId' => $ext],
        ['x-api-key' => $key, 'externalSystemId' => $ext],
        ['X-API-Key' => $key, 'ExternalSystemId' => $ext],
        ['Authorization' => 'Bearer '.$key, 'ExternalSystemId' => $ext],
        // sometimes only ext is needed:
        ['ExternalSystemId' => $ext],
        // sometimes only api key:
        ['x-api-key' => $key],
    ];

    foreach ($variants as $i => $headers) {
        $resp = Http::timeout(20)->withHeaders($headers)->get($url);
        if ($resp->ok()) {
            return response()->json([
                'note' => '✅ Success with variant #'.($i+1),
                'headers_used' => $headers,
                'status' => $resp->status(),
                'json' => $resp->json(),
            ]);
        }
        $attempts[] = [
            'variant' => $headers,
            'status'  => $resp->status(),
            'www_authenticate' => $resp->header('WWW-Authenticate'),
            'body'    => mb_strimwidth($resp->body(), 0, 400, '…'),
        ];
    }

    return response()->json([
        'note' => '❌ All variants failed. See attempts.',
        'attempts' => $attempts ?? [],
    ], 500);
});

Route::get('/mw-swagger-auth', function () {
    $resp = Http::timeout(20)->get('https://api.mworker.com/swagger/public/swagger.json');
    if ($resp->failed()) {
        return ['ok'=>false, 'status'=>$resp->status(), 'body'=>$resp->body()];
    }
    $sw = $resp->json();

    // OpenAPI v3
    $schemes = $sw['components']['securitySchemes'] ?? [];
    // Fallback to v2
    if (!$schemes && isset($sw['securityDefinitions'])) $schemes = $sw['securityDefinitions'];

    // Find any token URLs / authorization URLs
    $tokenUrls = [];
    foreach ((array)$schemes as $name => $cfg) {
        foreach (['tokenUrl','authorizationUrl','flows'] as $k) {
            if (isset($cfg[$k])) $tokenUrls[$name][$k] = $cfg[$k];
        }
        // if flows present, dig out tokenUrl per flow
        if (isset($cfg['flows']) && is_array($cfg['flows'])) {
            foreach ($cfg['flows'] as $flow => $def) {
                if (!empty($def['tokenUrl']) || !empty($def['authorizationUrl'])) {
                    $tokenUrls[$name]['flows'][$flow] = [
                        'tokenUrl' => $def['tokenUrl'] ?? null,
                        'authorizationUrl' => $def['authorizationUrl'] ?? null,
                        'scopes' => $def['scopes'] ?? null,
                    ];
                }
            }
        }
    }

    // Also list any paths that look like auth/token/login
    $paths = $sw['paths'] ?? [];
    $authish = [];
    foreach ($paths as $p => $methods) {
        if (preg_match('#/(auth|token|login|oauth|connect)#i', $p)) {
            $authish[$p] = array_keys($methods);
        }
    }

    return [
        'ok' => true,
        'securitySchemes' => $schemes,
        'tokenEndpoints' => $tokenUrls,
        'authLikePaths' => $authish,
    ];
});

Route::get('/mw-oidc', function () {
    $urls = [
        'https://api.mworker.com/.well-known/openid-configuration',
        'https://api.mworker.com/.well-known/openid-configuration.json',
        'https://api.mworker.com/identity/.well-known/openid-configuration',
    ];

    $out = [];
    foreach ($urls as $u) {
        $r = Http::timeout(15)->get($u);
        $out[] = [
            'url'    => $u,
            'status' => $r->status(),
            'ok'     => $r->ok(),
            'json'   => $r->ok() ? $r->json() : null,
            'body'   => $r->ok() ? null : mb_strimwidth($r->body(),0,600,'…'),
        ];
        if ($r->ok()) break;
    }
    return $out;
});

Route::get('/mw-swagger-search', function () {
    $resp = Http::timeout(20)->get('https://api.mworker.com/swagger/public/swagger.json');
    if ($resp->failed()) {
        return ['ok'=>false, 'status'=>$resp->status(), 'body'=>$resp->body()];
    }
    $sw = $resp->json();
    $paths = $sw['paths'] ?? [];
    $found = [];
    foreach ($paths as $p => $methods) {
        if (preg_match('#(auth|token|login|autologin|oauth|connect)#i', $p)) {
            $found[$p] = array_keys($methods);
        }
    }
    return ['ok'=>true, 'matches'=>$found, 'count'=>count($found)];
});