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
        Schema::create('wp_term_relationships', function (Blueprint $table) {
            $table->unsignedBigInteger('object_id' );
            $table->unsignedBigInteger('term_taxonomy_id' );
	        $table->integer('term_order' )->default( 0 );

			$table->primary(['object_id', 'term_taxonomy_id'])->unique();
			$table->index('term_taxonomy_id', 'term_taxonomy_id' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wp_term_relationships');
    }
};
