<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Settings > Resume / CV: the PDF resumes a job seeker keeps on their account, one of them marked as the default
 * (used automatically when applying for a job). Additive only: a new table, nothing existing is touched.
 * The files themselves live on the private disk and are only ever served to their owner.
 */
return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('user_resumes')) {
            return;
        }
        Schema::create('user_resumes', function (Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->id();
            $table->unsignedBigInteger('code')->index();          // the owner's account code (users.code)
            $table->string('original_name', 255);
            $table->string('path', 500);                          // relative path on the private ("local") disk
            $table->unsignedInteger('size')->default(0);          // bytes
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_resumes');
    }
};
