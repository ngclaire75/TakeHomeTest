<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->uuid('external_id')->nullable()->unique();
            $table->string('name');
            $table->decimal('price', 18, 2)->nullable();
            $table->string('provider_type', 50)->nullable();
            $table->string('location')->nullable();
            $table->string('vendor')->nullable();
            $table->integer('unit_sold')->nullable();
            $table->decimal('tkdn_value', 6, 2)->nullable();
            $table->jsonb('dynamic_attributes')->nullable();
            $table->string('detail_url')->nullable();
            $table->timestamps();

            $table->index('price');
            $table->index('provider_type');
        });

        DB::statement(
            "ALTER TABLE products ADD COLUMN search_vector tsvector " .
            "GENERATED ALWAYS AS (to_tsvector('simple', coalesce(name, ''))) STORED"
        );
        DB::statement('CREATE INDEX products_search_vector_idx ON products USING GIN (search_vector)');
        DB::statement('CREATE INDEX products_dynamic_attributes_idx ON products USING GIN (dynamic_attributes)');
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
