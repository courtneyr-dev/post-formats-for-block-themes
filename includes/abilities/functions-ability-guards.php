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

/**
 * Resolve a post and confirm the current user holds the given
 * capability on that specific post.
 *
 * No function_exists() guard: this file is require_once'd exactly once
 * by pfbt_include_files(), so the only thing a guard here could do is
 * let some earlier-loaded, same-named function silently stand in for
 * this security check instead — the opposite of what a guard function
 * should allow.
 *
 * @since 1.1.7
 *
 * @param int    $post_id    Caller-supplied post ID.
 * @param string $capability Meta capability to check against the post,
 *                            'read_post' or 'edit_post'.
 * @return \WP_Post|\WP_Error The post on success, WP_Error otherwise.
 */
function pfbt_ability_post_or_error( int $post_id, string $capability ) {
	// get_post( 0 ) falls back to the global $post (e.g. the current
	// loop post in a template context), which is not what a caller
	// passing an invalid ID meant to resolve.
	if ( $post_id < 1 ) {
		return new \WP_Error(
			'pfbt_not_found',
			__( 'Post not found.', 'post-formats-for-block-themes' ),
			array( 'status' => 404 )
		);
	}

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

	// current_user_can( 'read_post' ) maps to the primitive 'read'
	// capability for any published, public post — it says nothing
	// about a password the post itself requires. Without this, any
	// logged-in user could read a password-protected post's content
	// through an ability, bypassing the password wall entirely.
	//
	// post_password_required() itself has no edit_post exemption — it
	// only reports whether the post is password-protected and the
	// visitor hasn't supplied the password cookie. The exemption below
	// instead mirrors WP_REST_Posts_Controller::can_access_password_
	// content(), which grants a password-protected post to anyone who
	// holds edit_post on it when the request context is 'edit'; an
	// ability reading a post's content for a caller who can also edit
	// it is that same situation.
	if (
		'read_post' === $capability
		&& post_password_required( $post )
		&& ! current_user_can( 'edit_post', $post->ID )
	) {
		return new \WP_Error(
			'pfbt_forbidden',
			__( 'You cannot access this post.', 'post-formats-for-block-themes' ),
			array( 'status' => 403 )
		);
	}

	return $post;
}
