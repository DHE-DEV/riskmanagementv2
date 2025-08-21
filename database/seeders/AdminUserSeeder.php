<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Check if user already exists
        $existingUser = User::where('email', 'dh@passolution.de')->first();
        
        if (!$existingUser) {
            User::create([
                'name' => 'DH Admin',
                'email' => 'dh@passolution.de',
                'password' => Hash::make('Passolution2025!'),
                'is_admin' => true,
                'email_verified_at' => now(),
            ]);
            
            $this->command->info('Admin user created successfully!');
            $this->command->info('Email: dh@passolution.de');
            $this->command->info('Password: Passolution2025!');
        } else {
            // Update existing user to be admin
            $existingUser->update([
                'is_admin' => true,
                'password' => Hash::make('Passolution2025!'),
            ]);
            
            $this->command->info('Existing user updated to admin!');
            $this->command->info('Email: dh@passolution.de');
            $this->command->info('Password: Passolution2025!');
        }
    }
}
