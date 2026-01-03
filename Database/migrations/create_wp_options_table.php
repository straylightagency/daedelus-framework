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
        Schema::create('wp_options', function (Blueprint $table) {
            $table->unsignedBigInteger('option_id', true );
	        $table->string('option_name', 191 );
	        $table->longText('option_value' );
	        $table->string('autoload', 20 )->default('yes');

			$table->index('option_name', 'option_name' );
			$table->index('autoload', 'autoload' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wp_options');
    }
};
