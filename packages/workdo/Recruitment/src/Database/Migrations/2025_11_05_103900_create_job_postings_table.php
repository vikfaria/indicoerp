<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('job_postings')) {
            Schema::create('job_postings', function (Blueprint $table) {
                $table->id();
                $table->string('code');
                $table->string('posting_code')->unique();
                $table->string('title');
                $table->integer('position')->nullable();
                $table->string('priority')->default('0'); // 0=Low, 1=Medium, 2=High
                $table->decimal('min_experience', 10, 1)->nullable();
                $table->decimal('max_experience', 10, 1)->nullable();
                $table->decimal('min_salary', 10, 2)->nullable();
                $table->decimal('max_salary', 10, 2)->nullable();
                $table->longText('description')->nullable();
                $table->longText('requirements')->nullable();
                $table->text('skills')->nullable();
                $table->longText('benefits')->nullable();
                $table->longText('terms_condition')->nullable();
                $table->boolean('show_terms_condition')->default(false);
                $table->string('application_deadline')->nullable();
                $table->boolean('is_published')->default(false); // 0=Not Published, 1=Published
                $table->string('publish_date')->nullable();
                $table->boolean('is_featured')->default(false);
                $table->string('status')->default('0'); // 0=Draft, 1=Published/Active, 2=Closed
                $table->string('job_application')->default('existing'); // existing, custom
                $table->string('application_url')->nullable();
                $table->json('applicant')->nullable();
                $table->json('visibility')->nullable();
                $table->json('custom_questions')->nullable();

                $table->foreignId('branch_id')->nullable()->index();
                $table->foreignId('job_type_id')->nullable()->index();
                $table->foreignId('location_id')->nullable()->index();
                $table->foreignId('creator_id')->nullable()->index();
                $table->foreignId('created_by')->nullable()->index();

                $table->foreign('branch_id')->references('id')->on('branches')->onDelete('set null');
                $table->foreign('job_type_id')->references('id')->on('job_types')->onDelete('set null');
                $table->foreign('location_id')->references('id')->on('job_locations')->onDelete('set null');
                $table->foreign('creator_id')->references('id')->on('users')->onDelete('set null');
                $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('job_postings');
    }
};
