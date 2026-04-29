<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('ticket_field_values')) {
            Schema::create('ticket_field_values', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('record_id');
                $table->unsignedBigInteger('field_id');
                $table->string('value');
                $table->timestamps();

                $table->foreign('field_id')->references('id')->on('ticket_fields')->onDelete('cascade');
                $table->unique(['record_id', 'field_id']);

            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_field_values');
    }
};