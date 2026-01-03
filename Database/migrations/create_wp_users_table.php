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
        Schema::create('wp_users', function (Blueprint $table) {
            $table->unsignedBigInteger('ID', true );
            $table->string('user_login', 60 );
            $table->string('user_pass', 255 );
            $table->string('user_nicename', 50 );
            $table->string('user_email', 100 );
            $table->string('user_url', 100 );
            $table->dateTime('user_registered' )->default('0000-00-00 00:00:00');
	        $table->string('user_activation_key', 255 );
	        $table->integer('user_status' )->default( 0 );
	        $table->string('display_name', 250 );

			$table->index('user_login', 'user_login_key' );
			$table->index('user_nicename', 'user_nicename' );
			$table->index('user_email', 'user_email' );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wp_users');
    }
};
