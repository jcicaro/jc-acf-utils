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
		add_shortcode( JC_ACF_Core::SHORTCODE_NEW_POST, array( $this, 'render_new_post_form' ) );
		add_shortcode( JC_ACF_Core::SHORTCODE_UPDATE_POST, array( $this, 'render_update_post_form' ) );
	}

	/**
	 * Shortcode to render ACF Frontend Form with dynamic Field Group and Post Type
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Form HTML.
	 */
	public function render_new_post_form( $atts ) {
		$atts = shortcode_atts(
			array(
				'field_group' => '',
				'post_type'   => 'post',
				'title_field' => '',
			),
			$atts,
			JC_ACF_Core::SHORTCODE_NEW_POST
		);

		$field_group_id = intval( $atts['field_group'] );
		$post_type_slug = sanitize_key( $atts['post_type'] );
		$title_field    = sanitize_text_field( $atts['title_field'] );

		if ( ! function_exists( 'acf_form' ) || 0 === $field_group_id ) {
			return '';
		}

		if ( ! post_type_exists( $post_type_slug ) ) {
			return '';
		}

		ob_start();

		$form_args = array(
			'post_id'         => 'new_post',
			'new_post'        => array(
				'post_type'   => $post_type_slug,
				'post_status' => 'publish',
			),
			'return'          => home_url( '/?post_type=' . $post_type_slug . '&p=%post_id%' ),
			'field_groups'    => array( $field_group_id ),
			'submit_value'    => 'Submit Content',
			'form_attributes' => array(
				'class' => 'acf-form acf-form-new-post-' . $post_type_slug,
			),
		);

		if ( ! empty( $title_field ) ) {
			$form_args['html_after_fields'] = $this->get_title_field_html( $title_field );
		}

		acf_form( $form_args );

		return ob_get_clean();
	}

	/**
	 * Shortcode specifically for UPDATING a record using standard WP params
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string Form HTML.
	 */
	public function render_update_post_form( $atts ) {
		$atts = shortcode_atts(
			array(
				'title_field' => '',
			),
			$atts,
			JC_ACF_Core::SHORTCODE_UPDATE_POST
		);

		$url_post_id = $this->get_request_post_id();
		if ( ! $url_post_id ) {
			return '<div class="acf-notice -error"><p>Error: Missing Record ID.</p></div>';
		}

		$post_object = get_post( $url_post_id );
		if ( ! $post_object ) {
			return '<div class="acf-notice -error"><p>Record not found.</p></div>';
		}

		if ( ! current_user_can( 'edit_post', $url_post_id ) ) {
			return '<div class="acf-notice -error"><p>You do not have permission to edit this record.</p></div>';
		}

		$allowed_post_type = $post_object->post_type;
		$title_field       = sanitize_text_field( $atts['title_field'] );

		ob_start();

		$form_args = array(
			'post_id'         => $url_post_id,
			'submit_value'    => 'Update Record',
			'updated_message' => 'Record updated successfully.',
			'form_attributes' => array(
				'class' => 'acf-form-edit-' . $allowed_post_type,
			),
			'return'          => ( isset( $_GET['p'] ) ) ? add_query_arg(
				array(
					'p'         => $url_post_id,
					'post_type' => $allowed_post_type,
				),
				home_url( '/' )
			) : get_permalink( $url_post_id ),
		);

		if ( ! empty( $title_field ) ) {
			$form_args['html_after_fields'] = $this->get_title_field_html( $title_field );
		}

		acf_form( $form_args );

		return ob_get_clean();
	}

	/**
	 * Helper to get the post ID from the request.
	 *
	 * @return int Post ID or 0 if not found.
	 */
	private function get_request_post_id() {
		if ( isset( $_GET['p'] ) ) {
			return absint( $_GET['p'] );
		}
		return get_the_ID();
	}

	/**
	 * Helper to generate the hidden title field HTML.
	 *
	 * @param string $field_name The field name.
	 * @return string HTML input.
	 */
	private function get_title_field_html( $field_name ) {
		return sprintf(
			'<input type="hidden" name="%s" value="%s">',
			esc_attr( JC_ACF_Core::HIDDEN_FIELD_TITLE ),
			esc_attr( $field_name )
		);
	}
}
