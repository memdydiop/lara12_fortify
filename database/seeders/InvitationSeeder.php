<?php

namespace Database\Seeders;

use App\Models\Invitation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class InvitationSeeder extends Seeder
{
    public function run(): void
    {
        // Créer des rôles de test si ils n'existent pas
        $roles = ['Ghost', 'Admin', 'User'];
        
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }

        // Récupérer un utilisateur existant
        $inviter = User::first() ?? User::factory()->create();

        // Invitations en attente
        Invitation::factory()
            ->count(5)
            ->pending()
            //->withRole('User')
            ->state(['invited_by' => $inviter->id])
            ->create();

        // Invitations avec rôles
        Invitation::factory()
            ->count(3)
            ->pending()
            //->withRole('Admin')
            ->state(['invited_by' => $inviter->id])
            ->create();

        // Invitations acceptées
        Invitation::factory()
            ->count(4)
            ->registered()
            //->withRole('User')
            ->state(['invited_by' => $inviter->id])
            ->create();

        // Invitations expirées
        Invitation::factory()
            ->count(2)
            ->expired()
            //->withRole('User')
            ->state(['invited_by' => $inviter->id])
            ->create();
    }
}