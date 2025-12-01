<?php
/**
 * Repair Tool Admin Page Template
 *
 * @package PostFormatsBlockThemes
 * @since 1.0.0
 *
 * Accessibility: Uses semantic HTML, proper form labels, ARIA attributes,
 * and WordPress admin UI patterns for consistency and accessibility.
 *
 * @var array $scan_results Scan results from PFBT_Repair_Tool::scan_posts()
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Post Format Repair Tool', 'post-formats-for-block-themes' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'This tool scans your posts and identifies format mismatches based on content structure. It suggests corrections that you can apply individually or in bulk.', 'post-formats-for-block-themes' ); ?>
	</p>

	<?php
	// Display transient messages
	$repair_message = get_transient( 'pfbt_repair_message' );
	if ( $repair_message ) {
		$notice_class = 'success' === $repair_message['type'] ? 'notice-success' : 'notice-error';
		?>
		<div class="notice <?php echo esc_attr( $notice_class ); ?> is-dismissible">
			<p><strong><?php echo esc_html( $repair_message['message'] ); ?></strong></p>
		</div>
		<?php
		delete_transient( 'pfbt_repair_message' );
	}

	settings_errors( 'pfbt_repair' );
	?>

	<div class="pfbt-repair-summary card">
		<h2><?php esc_html_e( 'Scan Results', 'post-formats-for-block-themes' ); ?></h2>

		<table class="widefat striped">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Total Posts Scanned:', 'post-formats-for-block-themes' ); ?></th>
					<td><strong><?php echo esc_html( number_format_i18n( $scan_results['total_scanned'] ) ); ?></strong></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Correctly Formatted:', 'post-formats-for-block-themes' ); ?></th>
					<td><span class="dashicons dashicons-yes-alt" style="color: #46b450;" aria-hidden="true"></span> <?php echo esc_html( number_format_i18n( $scan_results['correct'] ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Format Mismatches:', 'post-formats-for-block-themes' ); ?></th>
					<td>
						<?php if ( $scan_results['mismatch_count'] > 0 ) : ?>
							<span class="dashicons dashicons-warning" style="color: #f0b849;" aria-hidden="true"></span>
							<strong><?php echo esc_html( number_format_i18n( $scan_results['mismatch_count'] ) ); ?></strong>
						<?php else : ?>
							<span class="dashicons dashicons-yes-alt" style="color: #46b450;" aria-hidden="true"></span>
							<?php esc_html_e( 'None', 'post-formats-for-block-themes' ); ?>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<?php if ( $scan_results['mismatch_count'] > 0 ) : ?>
		<div class="pfbt-repair-actions" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Bulk Actions', 'post-formats-for-block-themes' ); ?></h2>

			<form method="post" action="" id="pfbt-bulk-repair-form">
				<?php wp_nonce_field( 'pfbt_repair_action', 'pfbt_repair_nonce' ); ?>
				<input type="hidden" name="pfbt_repair_action" value="apply_all" />

				<p>
					<label for="pfbt_dry_run">
						<input type="checkbox" name="pfbt_dry_run" id="pfbt_dry_run" value="1" checked="checked" />
						<?php esc_html_e( 'Dry run (preview changes without applying)', 'post-formats-for-block-themes' ); ?>
					</label>
				</p>

				<p class="description">
					<?php esc_html_e( 'Dry run mode shows what would change without actually modifying your posts. Uncheck to apply changes.', 'post-formats-for-block-themes' ); ?>
				</p>

				<p>
					<button type="submit" class="button button-primary button-large">
						<span class="dashicons dashicons-update-alt" aria-hidden="true" style="margin-top: 3px;"></span>
						<?php esc_html_e( 'Apply All Suggestions', 'post-formats-for-block-themes' ); ?>
					</button>
				</p>

				<p class="description">
					<?php esc_html_e( 'Note: A revision will be created for each post before any changes are applied.', 'post-formats-for-block-themes' ); ?>
				</p>
			</form>
		</div>

		<div class="pfbt-mismatches" style="margin-top: 30px;">
			<h2><?php esc_html_e( 'Detected Mismatches', 'post-formats-for-block-themes' ); ?></h2>

			<table class="wp-list-table widefat striped table-view-list">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Post Title', 'post-formats-for-block-themes' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Current Format', 'post-formats-for-block-themes' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Suggested Format', 'post-formats-for-block-themes' ); ?></th>
						<th scope="col"><?php esc_html_e( 'First Block', 'post-formats-for-block-themes' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Action', 'post-formats-for-block-themes' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $scan_results['mismatches'] as $pfbt_mismatch ) : ?>
						<tr>
							<td>
								<strong>
									<a href="<?php echo esc_url( $pfbt_mismatch['post_url'] ); ?>" target="_blank">
										<?php echo esc_html( $pfbt_mismatch['post_title'] ?: __( '(no title)', 'post-formats-for-block-themes' ) ); ?>
										<span class="screen-reader-text"><?php esc_html_e( '(opens in new tab)', 'post-formats-for-block-themes' ); ?></span>
									</a>
								</strong>
								<br />
								<span class="description">
									<?php
									/* translators: %d: Post ID */
									echo esc_html( sprintf( __( 'ID: %d', 'post-formats-for-block-themes' ), $pfbt_mismatch['post_id'] ) );
									?>
								</span>
							</td>
							<td>
								<code><?php echo esc_html( ucfirst( $pfbt_mismatch['current_format'] ) ); ?></code>
							</td>
							<td>
								<strong><code><?php echo esc_html( ucfirst( $pfbt_mismatch['suggested_format'] ) ); ?></code></strong>
							</td>
							<td>
								<code><?php echo esc_html( $pfbt_mismatch['first_block'] ); ?></code>
							</td>
							<td>
								<form method="post" action="" style="display: inline;">
									<?php wp_nonce_field( 'pfbt_repair_action', 'pfbt_repair_nonce' ); ?>
									<input type="hidden" name="pfbt_repair_action" value="apply_single" />
									<input type="hidden" name="post_id" value="<?php echo esc_attr( $pfbt_mismatch['post_id'] ); ?>" />
									<input type="hidden" name="format" value="<?php echo esc_attr( $pfbt_mismatch['suggested_format'] ); ?>" />
									<input type="hidden" name="pfbt_dry_run" value="0" />
									<button type="submit" class="button button-small">
										<?php esc_html_e( 'Apply', 'post-formats-for-block-themes' ); ?>
									</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	<?php else : ?>
		<div class="notice notice-success inline" style="margin-top: 20px;">
			<p>
				<span class="dashicons dashicons-yes-alt" aria-hidden="true"></span>
				<strong><?php esc_html_e( 'Great job!', 'post-formats-for-block-themes' ); ?></strong>
				<?php esc_html_e( 'All your posts have the correct format based on their content structure.', 'post-formats-for-block-themes' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<div class="pfbt-help-section" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ccc;">
		<h3><?php esc_html_e( 'How Format Detection Works', 'post-formats-for-block-themes' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Gallery: First block is core/gallery', 'post-formats-for-block-themes' ); ?></li>
			<li><?php esc_html_e( 'Image: First block is core/image', 'post-formats-for-block-themes' ); ?></li>
			<li><?php esc_html_e( 'Video: First block is core/video', 'post-formats-for-block-themes' ); ?></li>
			<li><?php esc_html_e( 'Audio: First block is core/audio', 'post-formats-for-block-themes' ); ?></li>
			<li><?php esc_html_e( 'Quote: First block is core/quote', 'post-formats-for-block-themes' ); ?></li>
			<li><?php esc_html_e( 'Link: First block is bookmark-card/bookmark-card', 'post-formats-for-block-themes' ); ?></li>
			<li><?php esc_html_e( 'Chat: First block is chatlog/conversation', 'post-formats-for-block-themes' ); ?></li>
			<li><?php esc_html_e( 'Aside: First block is core/group with "aside-bubble" class', 'post-formats-for-block-themes' ); ?></li>
			<li><?php esc_html_e( 'Status: First block is core/paragraph with "status-paragraph" class', 'post-formats-for-block-themes' ); ?></li>
			<li><?php esc_html_e( 'Standard: Everything else or no content', 'post-formats-for-block-themes' ); ?></li>
		</ul>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Add loading state to bulk repair form
	$('#pfbt-bulk-repair-form').on('submit', function(e) {
		var $button = $(this).find('button[type="submit"]');
		var $icon = $button.find('.dashicons');

		// Disable button and show loading state
		$button.prop('disabled', true);
		$icon.removeClass('dashicons-update-alt').addClass('dashicons-update');
		$icon.css('animation', 'rotation 2s infinite linear');
		$button.append('<span class="spinner is-active" style="float: none; margin-left: 8px;"></span>');
	});

	// Add loading state to individual apply buttons
	$('.pfbt-mismatches form').on('submit', function(e) {
		var $button = $(this).find('button[type="submit"]');
		$button.prop('disabled', true).text('<?php echo esc_js( __( 'Applying...', 'post-formats-for-block-themes' ) ); ?>');
	});
});
</script>

<style>
@keyframes rotation {
	from { transform: rotate(0deg); }
	to { transform: rotate(359deg); }
}
</style>
