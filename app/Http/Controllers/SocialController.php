<?php

namespace App\Http\Controllers;

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialController extends Controller
{
    public function redirect($provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    public function callback($provider)
    {
        $socialUser = Socialite::driver($provider)->user();

        // Ищем, существует ли уже запись в social_accounts
        $socialAccount = SocialAccount::where('provider_name', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($socialAccount) {
            // Если аккаунт существует, логиним пользователя
            $user = $socialAccount->user;
            Auth::login($user);
        } else {
            // Проверяем, существует ли пользователь с таким email
            $user = User::where('email', $socialUser->getEmail())->first();

            if (!$user) {
                // Если пользователя нет, создаём нового
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'password' => bcrypt(Str::random(8)),
                ]);
            }

            // Привязываем социальный аккаунт к пользователю
            $user->socialAccounts()->create([
                'provider_name' => $provider,
                'provider_id' => $socialUser->getId(),
            ]);

            Auth::login($user);
        }

        // Редирект на предыдущую страницу или dashboard
        return redirect()->intended('/dashboard');
    }
}
