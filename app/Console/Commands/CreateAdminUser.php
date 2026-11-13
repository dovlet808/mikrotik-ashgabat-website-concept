<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Creates (or promotes) an administrator for the /admin panel.
 *
 * The password is never accepted as a command-line argument so it does not end
 * up in shell history. Use the interactive prompt, or --generate to create a
 * strong random password that is printed once.
 */
class CreateAdminUser extends Command
{
    protected $signature = 'app:create-admin
        {--email= : Administrator email (login)}
        {--name= : Display name}
        {--locale=ru : Admin panel language (ru or en)}
        {--generate : Generate a random password and print it once}';

    protected $description = 'Create an administrator account for the /admin panel';

    public function handle(): int
    {
        $email = $this->option('email') ?: text('Email (login)', required: true, validate: fn ($v) => filter_var($v, FILTER_VALIDATE_EMAIL) ? null : 'Enter a valid email.');
        $name = $this->option('name') ?: text('Name', default: 'Administrator', required: true);
        $locale = in_array($this->option('locale'), array_keys(config('site.admin_locales')), true) ? $this->option('locale') : 'ru';

        $existing = User::query()->where('email', $email)->first();
        if ($existing && ! $this->option('no-interaction') && ! confirm("User {$email} exists. Reset the password and grant admin access?", default: false)) {
            $this->warn('Nothing changed.');

            return self::FAILURE;
        }

        if ($this->option('generate')) {
            $plain = Str::password(20, symbols: false);
        } else {
            $plain = password('Password (min. 12 characters)', required: true);
            $confirmation = password('Repeat password', required: true);
            if ($plain !== $confirmation) {
                $this->error('Passwords do not match.');

                return self::FAILURE;
            }
        }

        $validator = Validator::make(
            ['email' => $email, 'password' => $plain],
            ['email' => ['required', 'email'], 'password' => ['required', Password::min(12)->letters()->numbers()]],
        );
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $user = $existing ?? new User(['email' => $email]);
        $user->fill(['name' => $name, 'password' => $plain, 'locale' => $locale]);
        $user->is_admin = true;
        $user->email_verified_at ??= now();
        $user->save();

        $this->info(($existing ? 'Updated' : 'Created')." administrator {$email}. Sign in at ".url('/admin'));
        if ($this->option('generate')) {
            $this->newLine();
            $this->line('  Password (shown once, store it in a password manager):');
            $this->line('  '.$plain);
        }

        return self::SUCCESS;
    }
}
