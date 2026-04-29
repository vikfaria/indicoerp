<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        if (!Schema::hasTable('ai_prompts')) {
            Schema::create('ai_prompts', function (Blueprint $table) {
                $table->id();
                $table->string('module')->nullable(); // hrm, account, project, cmms, etc.
                $table->string('submodule')->nullable(); // workorder, component, preventive_maintenance, etc.
                $table->string('field_type'); // title, description, award_title, etc.
                $table->text('prompt_template');
                $table->string('description')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();

                $table->index(['module', 'submodule', 'field_type']);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('ai_prompts');
    }
};
