<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->session()->forget('password_retry_at');
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if (in_array($status, [Password::RESET_LINK_SENT, Password::RESET_THROTTLED], true)) {
            $config = config('auth.passwords.'.config('auth.defaults.passwords'));
            $user = Password::broker()->getUser($request->only('email'));
            $createdAt = DB::connection($config['connection'] ?? null)
                ->table($config['table'])
                ->where('email', $user->getEmailForPasswordReset())
                ->value('created_at');

            if ($createdAt !== null) {
                $request->session()->put('password_retry_at', Carbon::parse($createdAt)
                    ->addSeconds($config['throttle'])->timestamp);
            }
        }

        if ($status === Password::RESET_LINK_SENT) {
            return back()->withInput($request->only('email'))->with('status', __($status));
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($status)]);
    }
}
