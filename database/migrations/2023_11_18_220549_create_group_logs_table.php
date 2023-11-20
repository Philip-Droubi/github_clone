<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('groups_log', function (Blueprint $table) {
            $table->id();
            $table->string("action");
            $table->text("additional_info")->nullable();
            $table->tinyInteger("importance")->default(1); // 1->5
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('groups_log');
    }
};
