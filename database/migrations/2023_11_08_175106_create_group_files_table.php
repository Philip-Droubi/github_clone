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
    { //TODO: هاد كلو ماعاد إلو طعمة طالما الملف بينتمي لغروب واحد بس
        Schema::create('group_files', function (Blueprint $table) {
            $table->id();
            // $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            // $table->foreignId('file_id')->constrained('files')->cascadeOnDelete();
            // $table->foreignId('added_by')->constrained('users')->cascadeOnDelete();
            // $table->integer('number_of_edits')->default(0);
            // $table->dateTime('last_edit_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('group_files');
    }
};
