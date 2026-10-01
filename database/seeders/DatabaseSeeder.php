<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // The service catalogue is reference data, not sample data: the landing
        // page, the booking flow and the admin settings screen are all empty
        // without it. It was never called from here, which is why a fresh
        // database came up with no services at all.
        $this->call(ServiceSeeder::class);

        // Sample login — deliberately never created in production.
        if (! app()->environment('production')) {
            User::factory()->create([
                'name'  => 'Test User',
                'email' => 'test@example.com',
            ]);
        }
    }
}
