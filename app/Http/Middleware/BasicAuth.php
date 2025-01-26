<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class BasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $username = $request->getUser();
        $password = $request->getPassword();
        if($username == 'superadmin0001' && $password == 'superadmin0001') {
            return $next($request);
        } else {
            abort(404, "Enter username and password.", [
                header('WWW-Authenticate: Basic realm="Sample Private Page"'),
                header('Content-Type: text/plain; charset=utf-8')
            ]);
        }
    }
}
