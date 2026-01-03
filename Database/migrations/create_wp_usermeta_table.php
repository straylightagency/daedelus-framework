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
        Schema::create('wp_usermeta', function (Blueprint $table) {
            $table->unsignedBigInteger('umeta_id', true );
	        $table->unsignedBigInteger('user_id' )->default( 0 );
	        $table->string('meta_key', 255 )->nullable();
	        $table->longText('meta_value' )->nullable();

			$table->index('user_id', 'user_id' );
			$table->index('meta_key', 'meta_key' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wp_usermeta');
    }
};
