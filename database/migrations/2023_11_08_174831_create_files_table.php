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
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('mime');
            $table->string('file_key')->unique();
            $table->foreignId('reserved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('path');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete(); //Not possible to create a single file that is included in several groups
            // because this will lead to problems with deletion and modification ... Note GitHub / GitLab
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
