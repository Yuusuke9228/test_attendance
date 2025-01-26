<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function __invoke()
    {
        if(User::isAdmin()) {
            return redirect()->intended(route('admin.record.special.index'));
        } else {
            return redirect()->intended(route('user.attendance.today.syukkin'));
        }
    }
}
