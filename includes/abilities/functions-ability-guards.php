<?php
/**
 * Shared object-level authorization guard for post-bound abilities.
 *
 * A wp_register_ability() permission_callback only proves the caller holds
 * some sitewide capability (e.g. 'read' or 'edit_posts'); it never sees the
 * specific post_id the caller supplied as input. Without a second check
 * inside the execute callback, a Contributor who can edit their own drafts
 * could pass another user's post_id and read or change its format anyway.
 * Every post-bound ability execute callback must resolve its post through
 * this helper instead of a bare get_post().
 *
 * @package PostFormatsBlockThemes
 * @since 1.1.7
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'pfbt_ability_post_or_error' ) ) {
	/**
	 * Resolve a post and confirm the current user holds the given
	 * capability on that specific post.
	 *
	 * @since 1.1.7
	 *
	 * @param int    $post_id    Caller-supplied post ID.
	 * @param string $capability Meta capability to check against the post,
	 *                           'read_post' or 'edit_post'.
	 * @return \WP_Post|\WP_Error The post on success, WP_Error otherwise.
	 */
	function pfbt_ability_post_or_error( int $post_id, string $capability ) {
		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post || 'post' !== $post->post_type ) {
			return new \WP_Error(
				'pfbt_not_found',
				__( 'Post not found.', 'post-formats-for-block-themes' ),
				array( 'status' => 404 )
			);
		}

		if ( ! current_user_can( $capability, $post->ID ) ) {
			return new \WP_Error(
				'pfbt_forbidden',
				__( 'You cannot access this post.', 'post-formats-for-block-themes' ),
				array( 'status' => 403 )
			);
		}

		return $post;
	}
}
