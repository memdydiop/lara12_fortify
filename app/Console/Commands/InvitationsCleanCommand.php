<?php

namespace App\Console\Commands;

use App\Models\Invitation;
use Illuminate\Console\Command;

class InvitationsCleanCommand extends Command
{
    /**
     * Le nom et la signature de la commande.
     * @var string
     */
    protected $signature = 'invitations:clean {--force : Forcer l\'exécution sans confirmation}';

    /**
     * La description de la commande.
     * @var string
     */
    protected $description = 'Supprime toutes les invitations expirées de la base de données';

    /**
     * Exécute la commande.
     */
    public function handle(): void
    {
        $this->info('Recherche des invitations expirées...');

        // On utilise le scope 'expired' que vous avez déjà créé !
        $query = Invitation::expired();
        $count = $query->count();

        if ($count === 0) {
            $this->info('Aucune invitation expirée à supprimer.');
            return;
        }

        $this->warn("{$count} invitation(s) expirée(s) trouvée(s).");

        // On demande confirmation sauf si l'option --force est utilisée
        if ($this->option('force') || $this->confirm('Voulez-vous vraiment les supprimer définitivement ?')) {
            $query->delete();
            $this->info("{$count} invitation(s) ont été supprimées avec succès.");
        } else {
            $this->info('Opération annulée.');
        }
    }
}