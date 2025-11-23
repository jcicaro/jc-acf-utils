<?php
/**
 * Class: JC_ACF_Forms
 * Handles ACF form processing and hook logic.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class JC_ACF_Forms {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'template_redirect', array( $this, 'handle_form_head' ) );
		add_action( 'acf/save_post', array( $this, 'handle_save_post' ), 20 );
	}

	/**
	 * 1. Load acf_form_head() BEFORE headers are sent.
	 * * CHANGED: Switched from 'wp_head' to 'template_redirect'.
	 * This allows 'is_page()' to work while still being early enough to process redirects.
	 */
	public function handle_form_head() {
		// Check if ACF is active
		if ( ! function_exists( 'acf_form_head' ) ) {
			return;
		}

		acf_form_head();
	}

	/**
	 * 3. Update Post Title from ACF Field and Insert Shortcode
	 * Hook into acf/save_post to update the post title and post content 
	 * for newly created posts.
	 *
	 * @param int|string $post_id The post ID being saved.
	 */
	public function handle_save_post( $post_id ) {
		// Bail if not a new post created via the front-end form
		if ( ! isset( $_POST['_acf_post_id'] ) || $_POST['_acf_post_id'] !== 'new_post' ) {
			// If it's an existing post update, we still proceed for title update
			// but only if our title field hidden input is present.
			// If it's an existing post update, we still proceed for title update
			// but only if our title field hidden input is present.
			if ( ! isset( $_POST[ JC_ACF_Core::HIDDEN_FIELD_TITLE ] ) ) {
				return;
			}
		}

		// SECURITY: Ensure we are only running for posts and not term/user updates, 
		// although the ACF form handles this post_id will be an integer.
		if ( ! is_numeric( $post_id ) ) {
			return;
		}

		$update_args = array( 'ID' => $post_id );
		$post_updated = false;

		// --- 1. Handle Post Title Update ---

		if ( isset( $_POST[ JC_ACF_Core::HIDDEN_FIELD_TITLE ] ) ) {
			// Get the field name that should be used for the title
			$title_field_name = sanitize_text_field( $_POST[ JC_ACF_Core::HIDDEN_FIELD_TITLE ] );

			// Get the value of that field
			$new_title = get_field( $title_field_name, $post_id );

			if ( ! empty( $new_title ) ) {
				$update_args['post_title'] = $new_title;
				$post_updated = true;
			}
		}

		// --- 2. Handle Shortcode Insertion for NEW Posts Only ---

		// Check if a new post was just created by the ACF form
		$is_new_post_submission = ( isset( $_POST['_acf_post_id'] ) && $_POST['_acf_post_id'] === 'new_post' );

		if ( $is_new_post_submission ) {

			// Define the shortcode you want to include
			$shortcode_to_insert = sprintf( '[%s title_field="text_field"]', JC_ACF_Core::SHORTCODE_UPDATE_POST );

			// Retrieve the current post content (it's likely empty or default from ACF)
			$current_post_content = get_post_field( 'post_content', $post_id );

			// IMPORTANT: Only insert if the content doesn't already have it
			if ( strpos( $current_post_content, $shortcode_to_insert ) === false ) {

				// Append the shortcode to the existing content (if any)
				$new_post_content = $current_post_content . "\n\n" . $shortcode_to_insert;

				// Set the new content for update
				$update_args['post_content'] = $new_post_content;
				$post_updated = true;

				// Optional: You might want to automatically change the post status from 'draft'
				// to 'publish' here if the new post shortcode did not set it already.
				// $update_args['post_status'] = 'publish';
			}
		}

		// --- 3. Perform the Update ---

		if ( $post_updated ) {
			// Update the post title and/or content in one go
			// SECURITY: wp_update_post handles sanitization for these fields internally.
			wp_update_post( $update_args );
		}
	}
}
