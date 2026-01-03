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
        Schema::create('wp_terms', function (Blueprint $table) {
            $table->unsignedBigInteger('term_id', true );
	        $table->string('name', 250 );
	        $table->string('slug', 250 );
	        $table->bigInteger('term_group' )->default( 0 );

			$table->index('slug', 'slug' );
			$table->index('name', 'name' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wp_terms');
    }
};
