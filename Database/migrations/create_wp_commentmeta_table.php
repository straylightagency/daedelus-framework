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
        Schema::create('wp_commentmeta', function (Blueprint $table) {
            $table->unsignedBigInteger('meta_id', true );
	        $table->unsignedBigInteger('comment_id' )->default( 0 );
	        $table->string('meta_key', 255 )->nullable();
	        $table->longText('meta_value' )->nullable();

			$table->index('comment_id', 'comment_id' );
			$table->index('meta_key', 'meta_key' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wp_commentmeta');
    }
};
