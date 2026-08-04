<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_id')->constrained()->onDelete('cascade');

            // Campos del CSV
            $table->string('order_id');
            $table->date('date');
            $table->string('customer_id');
            $table->string('customer_name');
            $table->string('product_id');
            $table->string('product_name');
            $table->string('category');
            $table->integer('quantity');
            $table->decimal('unit_price', 10, 2);
            $table->decimal('discount', 5, 2)->default(0);
            $table->string('country');

            // Campo calculado
            $table->decimal('total', 10, 2);

            $table->timestamps();

            // Índices para mejorar el rendimiento
            $table->index(['import_id', 'product_name']);
            $table->index(['import_id', 'category']);
            $table->index(['import_id', 'country']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};