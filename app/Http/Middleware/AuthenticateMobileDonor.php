<?php

namespace App\Http\Middleware;

use App\Models\MobileDonorToken;
use Closure;
use Illuminate\Http\Request;

class AuthenticateMobileDonor
{
    public function handle(Request $request, Closure $next)
    {
        $plain = $request->bearerToken();
        abort_unless(is_string($plain) && strlen($plain) === 64, 401, 'Silakan verifikasi WhatsApp kembali.');
        $token = MobileDonorToken::query()->where('token_hash', hash('sha256', $plain))
            ->where('expires_at', '>', now())->first();
        abort_if($token === null, 401, 'Sesi telah berakhir. Silakan verifikasi WhatsApp kembali.');
        $request->attributes->set('mobile_donor', $token);

        return $next($request);
    }
}
