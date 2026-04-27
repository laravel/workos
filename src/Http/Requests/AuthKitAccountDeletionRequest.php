<?php

namespace Laravel\WorkOS\Http\Requests;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Laravel\WorkOS\WorkOS;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AuthKitAccountDeletionRequest extends FormRequest
{
    /**
     * Redirect the user to WorkOS for authentication.
     *
     * @return RedirectResponse
     */
    public function delete(Closure $using)
    {
        $user = $this->user();

        if (isset($user->workos_id) && ! app()->runningUnitTests()) {
            WorkOS::client()->userManagement()->deleteUser(
                $user->workos_id,
            );
        }

        Auth::guard('web')->logout();

        $using($user);

        if ($this->hasSession()) {
            $this->session()->invalidate();
            $this->session()->regenerateToken();
        }

        return redirect('/');
    }
}
