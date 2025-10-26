// database/migrations/xxxx_xx_xx_xxxxxx_create_invitations_table.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->comment('Who sent the invitation')->constrained()->onDelete('cascade');
            $table->string('email')->unique();
            $table->string('token', 32)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};