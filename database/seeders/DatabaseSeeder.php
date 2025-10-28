<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // 1. Créer les permissions et rôles
        $this->call(RolesAndPermissionsSeeder::class);

        

        User::factory()->create([
            'name' => 'Ghost User',
            'email' => 'ghost@user.com',
        ])->assignRole('Ghost');
    }
}
