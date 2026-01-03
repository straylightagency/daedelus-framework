<?php

namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Actions;
use Daedelus\Support\Filters;

/**
 *
 */
class RegisterAdminColumns extends Hook
{
	/**
	 * @return void
	 */
	public function register(): void
	{
		$this->registerInit();
		$this->registerManageMediaColumns();
		$this->registerManageColumns();
		$this->registerManageCustomColumn();
	}

	/**
	 * Remove columns in list view
	 *
	 * @return void
	 */
	protected function registerInit():void
	{
		Actions::add( 'admin_init', function () {
			remove_post_type_support( 'post', 'comments' );
			remove_post_type_support( 'post', 'author' );
			remove_post_type_support( 'page', 'comments' );
			remove_post_type_support( 'page', 'author' );
		} );
	}

	/**
	 *
	 *
	 * @return void
	 */
	protected function registerManageMediaColumns():void
	{
		Filters::add( 'manage_media_columns', function ( $columns ) {
			unset( $columns['author'] );
			unset( $columns['comments'] );

			return $columns;
		} );
	}

	/**
	 * Add Template column in pages list
	 * @see https://codex.wordpress.org/Plugin_API/Action_Reference/manage_pages_columns
	 *
	 * @return void
	 */
	protected function registerManageColumns():void
	{
		Filters::add( 'manage_pages_columns', function ( $defaults ) {
			$defaults[ 'page-layout' ] = 'Template';

			return $defaults;
		} );
	}

	/**
	 * @see https://codex.wordpress.org/Plugin_API/Action_Reference/manage_pages_custom_column
	 *
	 * @return void
	 */
	protected function registerManageCustomColumn():void
	{
		Actions::add( 'manage_pages_custom_column', function ( $column_name ) {
			if ($column_name === 'page-layout') {
				$set_template = get_post_meta( get_the_ID(), '_wp_page_template', true );

				if ( $set_template === 'default' ) {
					echo 'Default';
				}

				$templates = get_page_templates();

				ksort( $templates );

				foreach ( array_keys( $templates ) as $template ) {
					if ( $set_template === $templates[ $template ] ) {
						echo $template;
					}
				}
			}
		} );
	}
}