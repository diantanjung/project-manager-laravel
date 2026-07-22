<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('refresh_tokens', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedBigInteger('user_id');
            $table->string('hash_token')->unique();
            $table->timestamp('expires_at');
            $table->timestamp('created_at');
            $table->boolean('is_revoked')->nullable()->default(false);

            $table->index('user_id');
            $table->index('expires_at');
            $table->index('is_revoked');
            $table->foreign('user_id', 'refresh_tokens_user_id_users_id_fk')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refresh_tokens');
    }
};
