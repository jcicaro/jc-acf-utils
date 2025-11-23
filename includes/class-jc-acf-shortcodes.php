<?php
/**
 * Class: JC_ACF_Shortcodes
 * Handles registration and rendering of ACF form shortcodes.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class JC_ACF_Shortcodes {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'jc_acf_form_new_post', array( $this, 'render_new_post_form' ) );
		add_shortcode( 'jc_acf_form_update_post', array( $this, 'render_update_post_form' ) );
	}

	/**
	 * 2. Shortcode to render ACF Frontend Form with dynamic Field Group and Post Type
	 * Usage: [jc_acf_form_new_post field_group="ID_HERE" post_type="SLUG_HERE" title_field="FIELD_NAME_HERE"]
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Form HTML.
	 */
	public function render_new_post_form( $atts ) {
		// Define default attributes and merge with user-provided attributes
		$atts = shortcode_atts(
			array(
				'field_group' => '', // Default to empty/nothing if not provided
				'post_type'   => 'post', // Default to 'post' if not provided
				'title_field' => '', // NEW: Field to use for the post title
			),
			$atts,
			'jc_acf_form_new_post'
		);

		// --- 1. Retrieve and Validate Attributes ---

		// Retrieve and ensure the Field Group ID is an integer
		$field_group_id = intval( $atts['field_group'] );

		// Retrieve and Sanitize the Post Type slug
		$post_type_slug = sanitize_key( $atts['post_type'] );

		// Retrieve the title field name
		$title_field = sanitize_text_field( $atts['title_field'] );

		// Check if function exists and if a valid ID was passed
		if ( ! function_exists( 'acf_form' ) || $field_group_id === 0 ) {
			if ( WP_DEBUG ) {
				error_log( 'ACF Form Shortcode Error: Field Group ID is missing or invalid.' );
			}
			return '';
		}

		// Optional: Check if the post type slug is valid (e.g., exists in WordPress)
		if ( ! post_type_exists( $post_type_slug ) ) {
			if ( WP_DEBUG ) {
				error_log( "ACF Form Shortcode Error: Post Type slug '{$post_type_slug}' is not registered." );
			}
			return '';
		}

		// Prepare the field_groups argument as an array (required by ACF)
		$field_groups_array = array( $field_group_id );

		// --- 2. Render the Form ---

		// Start Output Buffering (Keeps form output at shortcode location)
		ob_start();

		$form_args = array(
			'post_id'         => 'new_post',
			'new_post'        => array(
				// Use the dynamic and sanitized slug here
				'post_type'   => $post_type_slug, // <-- THE DYNAMIC SLUG
				'post_status' => 'publish',
			),
			'return'          => home_url( '/?post_type=' . $post_type_slug . '&p=%post_id%' ),
			'field_groups'    => $field_groups_array,
			'submit_value'    => 'Submit Content',
			// SECURITY: Always use a nonce in the form to ensure submission legitimacy
			'form_attributes' => array(
				'class' => 'acf-form acf-form-new-post-' . $post_type_slug,
			),
		);

		// If a title field is specified, add a hidden input to pass this info to the save_post hook
		if ( ! empty( $title_field ) ) {
			$form_args['html_after_fields'] = '<input type="hidden" name="_jc_acf_title_field" value="' . esc_attr( $title_field ) . '">';
		}

		acf_form( $form_args );

		// Return the buffered content
		return ob_get_clean();
	}

	/**
	 * 3. Shortcode specifically for UPDATING a record using standard WP params
	 * MODIFIED: Field Group and Post Type are now detected automatically from the Post ID.
	 * Usage: [jc_acf_form_update_post title_field="text_field"]
	 * Target URL structure: ?post_type=sandbox&p=563
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Form HTML.
	 */
	public function render_update_post_form( $atts ) {
		// Define attributes
		$atts = shortcode_atts(
			array(
				// Removed field_group and post_type as they are detected from the post object
				'title_field' => '',        // Optional: Field to sync with Post Title
			),
			$atts,
			'jc_acf_form_update_post'
		);

		// --- 1. Retrieve and Validate URL Parameters ---

		// Check if 'p' (Post ID) is present, otherwise try get_the_ID()
		$url_post_id = 0;
		if ( isset( $_GET['p'] ) ) {
			$url_post_id = absint( $_GET['p'] );
		} else {
			$url_post_id = get_the_ID();
		}

		if ( ! $url_post_id ) {
			return '<div class="acf-notice -error"><p>Error: Missing Record ID.</p></div>';
		}
		$title_field = sanitize_text_field( $atts['title_field'] );

		// Check if the post actually exists
		$post_object = get_post( $url_post_id );
		if ( ! $post_object ) {
			return '<div class="acf-notice -error"><p>Record not found.</p></div>';
		}

		// --- 2. Security Check & Automatic Detection ---

		// Automatically determine the Post Type from the loaded object
		$allowed_post_type = $post_object->post_type;

		// Check if 'post_type' is present in URL (optional, but good for linking)
		if ( isset( $_GET['post_type'] ) && sanitize_key( $_GET['post_type'] ) !== $allowed_post_type ) {
			// If the URL parameter doesn't match the actual post type, it's a security/linking mismatch
			if ( WP_DEBUG ) {
				error_log( "ACF Edit Form: URL post_type mismatch for ID {$url_post_id}." );
			}
			// You could block this, but we will proceed based on the actual post type for editing.
		}

		// Check Permissions
		if ( ! current_user_can( 'edit_post', $url_post_id ) ) {
			return '<div class="acf-notice -error"><p>You do not have permission to edit this record.</p></div>';
		}

		// --- 3. Render the Edit Form ---

		ob_start();

		$form_args = array(
			'post_id'         => $url_post_id, // ACF loads data and field groups based on this ID
			// **FIELD GROUPS ARE OMITTED HERE**
			// ACF will automatically look up the Field Groups assigned to $post_object->post_type
			'submit_value'    => 'Update Record',
			'updated_message' => 'Record updated successfully.',
			'form_attributes' => array(
				// Use the detected post type for the class name
				'class' => 'acf-form-edit-' . $allowed_post_type,
			),
			// Ensure parameters persist after submit
			'return'          => ( isset( $_GET['p'] ) ) ? add_query_arg(
				array(
					'p'         => $url_post_id,
					'post_type' => $allowed_post_type,
				),
				home_url( '/' ) // Use home_url to ensure we build from root if using query args
			) : get_permalink( $url_post_id ),
		);

		// Hidden input for title updating
		if ( ! empty( $title_field ) ) {
			$form_args['html_after_fields'] = '<input type="hidden" name="_jc_acf_title_field" value="' . esc_attr( $title_field ) . '">';
		}

		// If no field groups are assigned to this CPT, acf_form() will render nothing.
		acf_form( $form_args );

		return ob_get_clean();
	}
}
