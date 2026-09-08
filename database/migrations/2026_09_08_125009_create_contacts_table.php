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
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('first_name');
            $table->string('last_name');
            // gender: 1=男性, 2=女性, 3=その他
            $table->unsignedTinyInteger('gender');
            $table->string('email');
            // tel: ハイフンなし10〜11桁
            $table->string('tel', 11);
            $table->string('address');
            $table->string('building')->nullable();
            $table->string('detail', 120);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contacts');
    }
};
