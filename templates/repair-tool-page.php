<?php
/**
 * Repair Tool Admin Page Template
 *
 * @package PostFormatsPowerUp
 * @since 1.0.0
 *
 * Accessibility: Uses semantic HTML, proper form labels, ARIA attributes,
 * and WordPress admin UI patterns for consistency and accessibility.
 *
 * @var array $scan_results Scan results from PFPU_Repair_Tool::scan_posts()
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h1><?php esc_html_e( 'Post Format Repair Tool', 'post-formats-power-up' ); ?></h1>

	<p class="description">
		<?php esc_html_e( 'This tool scans your posts and identifies format mismatches based on content structure. It suggests corrections that you can apply individually or in bulk.', 'post-formats-power-up' ); ?>
	</p>

	<?php settings_errors( 'pfpu_repair' ); ?>

	<div class="pfpu-repair-summary card">
		<h2><?php esc_html_e( 'Scan Results', 'post-formats-power-up' ); ?></h2>

		<table class="widefat striped">
			<tbody>
				<tr>
					<th scope="row"><?php esc_html_e( 'Total Posts Scanned:', 'post-formats-power-up' ); ?></th>
					<td><strong><?php echo esc_html( number_format_i18n( $scan_results['total_scanned'] ) ); ?></strong></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Correctly Formatted:', 'post-formats-power-up' ); ?></th>
					<td><span class="dashicons dashicons-yes-alt" style="color: #46b450;" aria-hidden="true"></span> <?php echo esc_html( number_format_i18n( $scan_results['correct'] ) ); ?></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Format Mismatches:', 'post-formats-power-up' ); ?></th>
					<td>
						<?php if ( $scan_results['mismatch_count'] > 0 ) : ?>
							<span class="dashicons dashicons-warning" style="color: #f0b849;" aria-hidden="true"></span>
							<strong><?php echo esc_html( number_format_i18n( $scan_results['mismatch_count'] ) ); ?></strong>
						<?php else : ?>
							<span class="dashicons dashicons-yes-alt" style="color: #46b450;" aria-hidden="true"></span>
							<?php esc_html_e( 'None', 'post-formats-power-up' ); ?>
						<?php endif; ?>
					</td>
				</tr>
			</tbody>
		</table>
	</div>

	<?php if ( $scan_results['mismatch_count'] > 0 ) : ?>
		<div class="pfpu-repair-actions" style="margin-top: 20px;">
			<h2><?php esc_html_e( 'Bulk Actions', 'post-formats-power-up' ); ?></h2>

			<form method="post" action="" id="pfpu-bulk-repair-form">
				<?php wp_nonce_field( 'pfpu_repair_action', 'pfpu_repair_nonce' ); ?>
				<input type="hidden" name="pfpu_repair_action" value="apply_all" />

				<p>
					<label for="pfpu_dry_run">
						<input type="checkbox" name="pfpu_dry_run" id="pfpu_dry_run" value="1" checked="checked" />
						<?php esc_html_e( 'Dry run (preview changes without applying)', 'post-formats-power-up' ); ?>
					</label>
				</p>

				<p class="description">
					<?php esc_html_e( 'Dry run mode shows what would change without actually modifying your posts. Uncheck to apply changes.', 'post-formats-power-up' ); ?>
				</p>

				<p>
					<button type="submit" class="button button-primary button-large">
						<span class="dashicons dashicons-update-alt" aria-hidden="true" style="margin-top: 3px;"></span>
						<?php esc_html_e( 'Apply All Suggestions', 'post-formats-power-up' ); ?>
					</button>
				</p>

				<p class="description">
					<?php esc_html_e( 'Note: A revision will be created for each post before any changes are applied.', 'post-formats-power-up' ); ?>
				</p>
			</form>
		</div>

		<div class="pfpu-mismatches" style="margin-top: 30px;">
			<h2><?php esc_html_e( 'Detected Mismatches', 'post-formats-power-up' ); ?></h2>

			<table class="wp-list-table widefat striped table-view-list">
				<thead>
					<tr>
						<th scope="col"><?php esc_html_e( 'Post Title', 'post-formats-power-up' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Current Format', 'post-formats-power-up' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Suggested Format', 'post-formats-power-up' ); ?></th>
						<th scope="col"><?php esc_html_e( 'First Block', 'post-formats-power-up' ); ?></th>
						<th scope="col"><?php esc_html_e( 'Action', 'post-formats-power-up' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $scan_results['mismatches'] as $pfpu_mismatch ) : ?>
						<tr>
							<td>
								<strong>
									<a href="<?php echo esc_url( $pfpu_mismatch['post_url'] ); ?>" target="_blank">
										<?php echo esc_html( $pfpu_mismatch['post_title'] ?: __( '(no title)', 'post-formats-power-up' ) ); ?>
										<span class="screen-reader-text"><?php esc_html_e( '(opens in new tab)', 'post-formats-power-up' ); ?></span>
									</a>
								</strong>
								<br />
								<span class="description">
									<?php
									/* translators: %d: Post ID */
									echo esc_html( sprintf( __( 'ID: %d', 'post-formats-power-up' ), $pfpu_mismatch['post_id'] ) );
									?>
								</span>
							</td>
							<td>
								<code><?php echo esc_html( ucfirst( $pfpu_mismatch['current_format'] ) ); ?></code>
							</td>
							<td>
								<strong><code><?php echo esc_html( ucfirst( $pfpu_mismatch['suggested_format'] ) ); ?></code></strong>
							</td>
							<td>
								<code><?php echo esc_html( $pfpu_mismatch['first_block'] ); ?></code>
							</td>
							<td>
								<form method="post" action="" style="display: inline;">
									<?php wp_nonce_field( 'pfpu_repair_action', 'pfpu_repair_nonce' ); ?>
									<input type="hidden" name="pfpu_repair_action" value="apply_single" />
									<input type="hidden" name="post_id" value="<?php echo esc_attr( $pfpu_mismatch['post_id'] ); ?>" />
									<input type="hidden" name="format" value="<?php echo esc_attr( $pfpu_mismatch['suggested_format'] ); ?>" />
									<input type="hidden" name="pfpu_dry_run" value="0" />
									<button type="submit" class="button button-small">
										<?php esc_html_e( 'Apply', 'post-formats-power-up' ); ?>
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
				<strong><?php esc_html_e( 'Great job!', 'post-formats-power-up' ); ?></strong>
				<?php esc_html_e( 'All your posts have the correct format based on their content structure.', 'post-formats-power-up' ); ?>
			</p>
		</div>
	<?php endif; ?>

	<div class="pfpu-help-section" style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ccc;">
		<h3><?php esc_html_e( 'How Format Detection Works', 'post-formats-power-up' ); ?></h3>
		<ul>
			<li><?php esc_html_e( 'Gallery: First block is core/gallery', 'post-formats-power-up' ); ?></li>
			<li><?php esc_html_e( 'Image: First block is core/image', 'post-formats-power-up' ); ?></li>
			<li><?php esc_html_e( 'Video: First block is core/video', 'post-formats-power-up' ); ?></li>
			<li><?php esc_html_e( 'Audio: First block is core/audio', 'post-formats-power-up' ); ?></li>
			<li><?php esc_html_e( 'Quote: First block is core/quote', 'post-formats-power-up' ); ?></li>
			<li><?php esc_html_e( 'Link: First block is bookmark-card/bookmark-card', 'post-formats-power-up' ); ?></li>
			<li><?php esc_html_e( 'Chat: First block is chatlog/conversation', 'post-formats-power-up' ); ?></li>
			<li><?php esc_html_e( 'Aside: First block is core/group with "aside-bubble" class', 'post-formats-power-up' ); ?></li>
			<li><?php esc_html_e( 'Status: First block is core/paragraph with "status-paragraph" class', 'post-formats-power-up' ); ?></li>
			<li><?php esc_html_e( 'Standard: Everything else or no content', 'post-formats-power-up' ); ?></li>
		</ul>
	</div>
</div>

<style>
/* Basic styling for repair tool page - inline for simplicity */
.pfpu-repair-summary.card {
	padding: 20px;
	background: #fff;
	border: 1px solid #ccd0d4;
	box-shadow: 0 1px 1px rgba(0,0,0,.04);
	margin-top: 20px;
}

.pfpu-repair-summary table {
	margin-top: 15px;
}

.pfpu-repair-summary th {
	width: 200px;
	font-weight: 600;
}

.pfpu-mismatches table {
	margin-top: 15px;
}

.pfpu-mismatches code {
	background: #f0f0f1;
	padding: 2px 6px;
	border-radius: 3px;
}
</style>
