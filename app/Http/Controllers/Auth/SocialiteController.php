<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SysConfig1\ConfigUser;
use Illuminate\Auth\Events\Verified;
use Laravel\Socialite\Facades\Socialite;

class SocialiteController extends Controller
{
    public function redirect($provider)
    {
        // redirect from social site
        if (request()->input('state')) {
            // already logged in
            // get user info from social site
            $user = Socialite::driver($provider)->stateless()->user();

            // check for existing user
            $existingUser = ConfigUser::where('code', $user->code)->first();

            if ($existingUser) {
                auth()->login($existingUser, true);

                return redirect()->to('/');
            }

            $newUser = $this->createUser($user);
            auth()->login($newUser, true);
        }

        // request login from social site
        return Socialite::driver($provider)->redirect();
    }


    function createUser($user)
    {
        $user = ConfigUser::updateOrCreate([
            'code' => $user->code,
        ], [
            'code'     => $user->code,
            'password' => '',
            'avatar'   => $user->getAvatar(),
        ]);

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return $user;
    }
}
