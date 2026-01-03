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
        Schema::create('wp_links', function (Blueprint $table) {
            $table->unsignedBigInteger('link_id', true );
	        $table->string('link_url', 255 );
	        $table->string('link_name', 255 );
	        $table->string('link_image', 255 );
	        $table->string('link_target', 25 );
	        $table->string('link_description', 255 );
	        $table->string('link_visible', 20 )->default('Y');
	        $table->unsignedBigInteger('link_owner' )->default( 1 );
	        $table->bigInteger('link_rating' )->default( 0 );
	        $table->dateTime('link_updated' )->default( '0000-00-00 00:00:00' );
	        $table->string('link_rel', 255 );
	        $table->mediumText('link_notes');
	        $table->string('link_rss', 255 );

			$table->index('link_visible', 'link_visible' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wp_links');
    }
};
