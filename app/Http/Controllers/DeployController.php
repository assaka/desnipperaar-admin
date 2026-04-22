<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class DeployController extends Controller
{
    public function handle(Request $request)
    {
        $expected = env('DEPLOY_HOOK_SECRET');
        $provided = $request->header('X-Hub-Signature-256')
            ?? $request->query('token')
            ?? $request->input('token');

        if (!$expected || !$provided) {
            abort(401, 'missing token');
        }

        // GitHub sends sha256=<hmac>; accept raw secret too.
        $valid = hash_equals($expected, $provided)
              || ($request->header('X-Hub-Signature-256')
                  && hash_equals(
                      'sha256=' . hash_hmac('sha256', $request->getContent(), $expected),
                      $request->header('X-Hub-Signature-256')
                  ));

        if (!$valid) {
            abort(401, 'invalid token');
        }

        $script = base_path('deploy.sh');
        $p = Process::fromShellCommandline("cd " . base_path() . " && git pull --quiet && php artisan optimize:clear");
        $p->setTimeout(120)->run();

        return response()->json([
            'ok' => $p->isSuccessful(),
            'output' => trim($p->getOutput()),
            'errors' => trim($p->getErrorOutput()),
        ]);
    }
}
