<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['info', 'warning', 'maintenance', 'critical'])->default('info');
            $table->boolean('is_active')->default(false);
            $table->json('target_servers')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->timestamp('scheduled_start')->nullable();
            $table->timestamp('scheduled_end')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->index(['is_active', 'scheduled_start', 'scheduled_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcements');
    }
};
