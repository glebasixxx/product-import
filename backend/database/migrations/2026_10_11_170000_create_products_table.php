<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('external_code')->unique();
            $table->string('name', 512)->index();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2)->index();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->decimal('discount', 7, 2)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
