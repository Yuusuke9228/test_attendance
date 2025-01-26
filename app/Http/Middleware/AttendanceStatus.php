<?php

namespace App\Http\Middleware;

use App\Models\DakouData;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class AttendanceStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, $role)
    {
        $syukin_status = User::syukin();
        $taikin_status = User::taikin();
        if ($role == 'uncomplete' && $taikin_status)
            return redirect()->route('user.attendance.today.complete');
        if ($role == 'complete' && !$syukin_status) {
            return redirect()->route('user.attendance.today.syukkin');
        }
        return $next($request);
    }
}
