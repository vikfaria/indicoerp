<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('contract_comments')) {
            Schema::create('contract_comments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('contract_id')->constrained('contracts')->onDelete('cascade');
                $table->text('comment');
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->boolean('is_edited')->default(false);
                $table->foreignId('creator_id')->nullable()->constrained('users')->onDelete('set null');
                $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_comments');
    }
};