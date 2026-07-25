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
        Schema::create('exports', function (Blueprint $table): void {
            $table->id();
            $table->string('type');
            $table->string('status')->default('completed');
            $table->string('file_name');
            $table->text('file_url');
            $table->json('filters')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exports');
    }
};
