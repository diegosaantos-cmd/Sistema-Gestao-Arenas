<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Password;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use Laravel\Fortify\Contracts\LoginResponse;
use Laravel\Fortify\Contracts\FailedPasswordResetLinkRequestResponse;
use Laravel\Fortify\Http\Responses\SuccessfulPasswordResetLinkRequestResponse;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Anti-enumeração de e-mail: ao pedir "recuperar senha", a resposta é a
        // MESMA exista ou não uma conta com aquele e-mail (e também sob throttle).
        // Assim ninguém descobre quais e-mails têm cadastro. A mensagem exibida é
        // neutra ("se houver uma conta, você receberá um link" — ver passwords.sent).
        $this->app->bind(FailedPasswordResetLinkRequestResponse::class, function () {
            return new SuccessfulPasswordResetLinkRequestResponse(Password::RESET_LINK_SENT);
        });

        Fortify::authenticateUsing(function (Request $request) {
            $user = User::where('email', mb_strtolower(trim((string) $request->email)))
                ->where('active', true)
                ->first();

            return $user && Hash::check($request->password, $user->password_hash)
                ? $user
                : null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())) . '|' . $request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // O limitador 'passkeys' foi removido junto com o recurso (ver config/fortify.php).

        $this->app->instance(LoginResponse::class, new class implements LoginResponse {

            public function toResponse($request)
            {
                $user = auth()->user();

                if ($user->type === 'admin') {
                    return redirect('/admin');
                }

                if ($user->type === 'proprietario') {
                    return redirect('/proprietario');
                }

                return redirect('/dashboard');
            }
        });
    }
}
