<?php
namespace Daedelus\Foundation\Console\Commands\Comment;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Symfony\Component\Console\Attribute\AsCommand;

/**
 *
 */
#[AsCommand(name: 'comment:create')]
class CreateCommand extends Command
{
	/** @var string */
	protected $signature = 'comment:create {--comment_agent=} {--comment_approved=} {--comment_author=} {--comment_author_email=}
	 {--comment_author_IP=} {--comment_author_url=} {--comment_content=} {--comment_date=} {--comment_date_gmt=} {--comment_karma=}
	  {--comment_parent=} {--comment_post_ID=} {--comment_type=} {--comment_meta=} {--user_id=}';

	/** @var string */
	protected $description = 'Creates a new comment.';

	/**
	 * @return void
	 */
	public function handle():void
	{
		$args = $this->options();
		$args = Arr::except( $args, ['help', 'quiet', 'verbose', 'version', 'ansi', 'no-interaction', 'env'] );
		$args = array_filter( $args );
		$args = array_map( function ( $v ) {
			if ( ctype_digit( $v ) ) {
				return (int) $v;
			}

			return $v;
		}, $args );

		if ( isset( $args['comment_post_ID'] ) ) {
			$post_id = $args['comment_post_ID'];
			$post = get_post( $post_id );
			if ( ! $post ) {
				$this->error( "Can't find post $post_id." );
			}
		} else {
			// Make sure it's set for older WP versions else get undefined PHP notice.
			$args['comment_post_ID'] = 0;
		}

		// We use wp_insert_comment() instead of wp_new_comment() to stay at a low level and
		// avoid wp_die() formatted messages or notifications
		$comment_id = wp_insert_comment( $args );

		if ( ! $comment_id ) {
			$this->error( 'Could not create comment.' );
		}
	}
}