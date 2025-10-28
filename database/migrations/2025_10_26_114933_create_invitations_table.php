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
            $table->string('email')->unique();
            $table->string('token')->unique();
            $table->string('role')->default('User');
            $table->timestamp('registered_at')->nullable();
            $table->timestamp('expires_at');
            $table->foreignId('invited_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            $table->index(['email', 'token']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invitations');
    }
};