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
        Schema::create('wp_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('comment_ID', true );
	        $table->unsignedBigInteger('comment_post_ID' )->default( 0 );
	        $table->tinyText('comment_author' );
	        $table->string('comment_author_email', 100 );
	        $table->string('comment_author_url', 200 );
	        $table->string('comment_author_ip', 100 );
	        $table->dateTime('comment_date' )->default( '0000-00-00 00:00:00' );
	        $table->dateTime('comment_date_gmt' )->default( '0000-00-00 00:00:00' );
	        $table->text('comment_content' );
	        $table->integer('comment_karma' )->default( 0 );
	        $table->string('comment_approved', 20 )->default( 1 );
	        $table->string('comment_agent', 255 );
	        $table->string('comment_type', 20 )->default( 'comment' );
	        $table->unsignedBigInteger('comment_parent' )->default( 0 );
	        $table->unsignedBigInteger('user_id' )->default( 0 );

			$table->index('comment_post_ID', 'comment_post_ID' );
			$table->index([
				'comment_post_ID', 'comment_approved', 'comment_date_gmt'
			], 'comment_approved_date_gmt' );
			$table->index('comment_date_gmt', 'comment_date_gmt' );
			$table->index('comment_parent', 'comment_parent' );
			$table->index('comment_author_email', 'comment_author_email' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wp_comments');
    }
};
