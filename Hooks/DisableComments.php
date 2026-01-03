<?php
namespace Daedelus\Foundation\Hooks;

use Daedelus\Support\Actions;
use Daedelus\Support\Filters;

/**
 *
 */
class DisableComments extends Hook
{
	/**
	 * @return void
	 */
	public function register():void
	{
		Actions::add('widgets_init', function () {
			unregister_widget('WP_Widget_Recent_Comments');

			Filters::add('show_recent_comments_widget_style', $this->noop() );
		} );

		Actions::add('template_redirect', function () {
			if ( is_comment_feed() ) {
				wp_die( 'Comments are closed.', '', [ 'response' => 403 ] );
			}
		}, 9 );

		Actions::add('admin_init', function () {
			if ( is_admin_bar_showing() ) {
				Actions::remove('admin_bar_menu', 'wp_admin_bar_comments_menu', 60 );
			}
		} );

		Filters::add('rest_endpoints', function (array $endpoints) {
			if ( isset( $endpoints['comments'] ) ) {
				unset( $endpoints['comments'] );
			}

			if ( isset( $endpoints['/wp/v2/comments'] ) ) {
				unset( $endpoints['/wp/v2/comments'] );
			}

			if ( isset( $endpoints['/wp/v2/comments/(?P<id>[\d]+)'] ) ) {
				unset( $endpoints['/wp/v2/comments/(?P<id>[\d]+)'] );
			}

			return $endpoints;
		} );

		Filters::add('xmlrpc_methods', function (array $methods) {
			unset( $methods['wp.newComment'] );

			return $methods;
		} );

		Actions::add('wp_loaded', function () {
			Filters::add('comments_array', '__return_empty_array', 20, 2);

			Filters::add( [ 'comments_open', 'pings_open' ], $this->noop(), 20, 2);

			Filters::add('get_comments_number', '__return_zero', 20, 2);

            if ( is_admin() ) {
	            Actions::add('admin_menu', function () {
		            global $pagenow;

		            if ( in_array( $pagenow, [ 'comment.php', 'edit-comments.php', 'options-discussion.php' ] ) ) {
			            wp_die( 'Comments are closed.', '', [ 'response' => 403 ] );
		            }

		            remove_menu_page('edit-comments.php');
		            remove_submenu_page('options-general.php', 'options-discussion.php');
                } );

	            Actions::add('admin_menu', function () {
		            Actions::add( [ 'admin_print_styles-index.php', 'admin_print_styles-profile.php' ], function () {
			            echo '<style>
                            #dashboard_right_now .comment-count,
                            #dashboard_right_now .comment-mod-count,
                            #latest-comments,
                            #welcome-panel .welcome-comments,
                            .user-comment-shortcuts-wrap {
                                display: none !important;
                            }
                        </style>';
                    } );
	            }, 9999 );

	            Actions::add('wp_dashboard_setup', function () {
		            remove_meta_box('dashboard_recent_comments', 'dashboard', 'normal');
                } );

	            Filters::add('pre_option_default_pingback_flag', '__return_zero');
            } else {
	            Actions::add('template_redirect', function () {
		            wp_deregister_script('comment-reply');

		            Actions::remove('wp_head', 'feed_links_extra', 3 );
                } );

	            Filters::add('feed_links_show_comments_feed', $this->noop() );
            }
		} );

		Actions::add('enqueue_block_editor_assets', function () {
			Actions::add( 'wp_head', function () {
				?>
				<script>
                    wp.domReady(function () {
                        if ( wp.blocks ) {
                            wp.blocks.unregisterBlockType("core/latest-comments");
                        }
                    });
				</script>
				<?php
			} );
		} );
	}
}