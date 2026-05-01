<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('passkeys', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('Unnamed device');
            $table->string('credential_id')->unique();
            $table->longText('public_key');
            $table->unsignedBigInteger('sign_count')->default(0);
            $table->string('transports')->nullable();
            $table->uuid('aaguid')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'last_used_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('passkeys');
    }
};
