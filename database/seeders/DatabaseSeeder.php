<?php

namespace Database\Seeders;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'hamid@desnipperaar.nl'],
            [
                'name' => 'Hamid El Abassi',
                'password' => Hash::make(env('ADMIN_SEED_PASSWORD', 'change-me-immediately')),
                'email_verified_at' => now(),
            ]
        );

        Driver::firstOrCreate(
            ['license_last4' => '0000'],
            [
                'name' => 'Placeholder Chauffeur',
                'vog_valid_until' => now()->addYear(),
                'active' => true,
            ]
        );
    }
}
