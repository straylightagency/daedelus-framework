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
        Schema::create('wp_term_taxonomy', function (Blueprint $table) {
            $table->unsignedBigInteger('term_taxonomy_id', true );
            $table->unsignedBigInteger('term_id' )->default(0);
	        $table->string('taxonomy', 32 );
	        $table->longText('description' );
	        $table->unsignedBigInteger('parent' )->default(0);
	        $table->bigInteger('count' )->default(0);

			$table->unique(['term_id', 'taxonomy'], 'term_id_taxonomy' );
	        $table->index('taxonomy', 'taxonomy' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wp_term_taxonomy');
    }
};
