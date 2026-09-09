<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class PassportClientSeeder extends Seeder
{
    /**
     * Both the "admins" and "users" guards use Passport's password-less
     * personal access grant (AdminAuthController/UserAuthController call
     * $model->createToken() directly after checking the password
     * themselves). That call fails at runtime with "Personal access client
     * not found" unless a personal access client exists for that specific
     * guard's provider — Passport's default installer only ever creates one
     * for the app's single default provider, which doesn't cover either of
     * this app's two custom guards. Idempotent: safe to run on every deploy.
     */
    public function run(): void
    {
        $clients = app(ClientRepository::class);

        foreach (['users', 'admins'] as $provider) {

            $exists = \Laravel\Passport\Client::query()
                ->where('provider', $provider)
                ->where('revoked', false)
                ->get()
                ->contains(fn ($client) => $client->hasGrantType('personal_access'));

            if (!$exists) {
                $clients->createPersonalAccessGrantClient(
                    ucfirst($provider) . ' Personal Access Client',
                    $provider
                );
            }
        }
    }
}
