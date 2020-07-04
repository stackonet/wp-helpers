<?php

namespace Stackonet\WP\Examples\WordPressCore;

use WP_Term;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

/**
 * Class TermField
 * Example: how to add custom field on term add and edit form
 * Example: how to add custom column on term list table
 *
 * @package Stackonet\WP\Examples\WordPressCore
 */
class TermField {

	/**
	 * taxonomy name
	 * Can be default terms 'category' and 'post_tag' or any custom term
	 */
	const TAXONOMY = 'category';

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance;

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;

			add_action( static::TAXONOMY . '_add_form_fields', [ self::$instance, 'add_form_fields' ] );
			add_action( static::TAXONOMY . '_edit_form_fields', [ self::$instance, 'edit_form_fields' ] );

			add_action( 'created_term', array( self::$instance, 'save_form_fields' ), 10, 3 );
			add_action( 'edit_term', array( self::$instance, 'save_form_fields' ), 10, 3 );

			add_filter( 'manage_edit-' . static::TAXONOMY . '_columns', [ self::$instance, 'add_custom_columns' ], 99 );
			add_filter( 'manage_' . static::TAXONOMY . '_custom_column', [ self::$instance, 'column_content' ], 10, 3 );
		}

		return self::$instance;
	}

	/**
	 * Save category fields
	 *
	 * @param mixed $term_id Term ID being saved.
	 * @param mixed $tt_id Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 */
	public function save_form_fields( $term_id, $tt_id = '', $taxonomy = '' ) {
		if ( static::TAXONOMY === $taxonomy ) {
			if ( isset( $_POST['_category_image_id'] ) ) {
				update_term_meta( $term_id, '_category_image_id', absint( $_POST['_category_image_id'] ) );
			}
		}
	}

	/**
	 * Add custom column on category list table
	 *
	 * @param array $columns
	 *
	 * @return mixed
	 */
	public function add_custom_columns( $columns ) {
		$columns['_category_image_id'] = __( 'Image' );

		return $columns;
	}

	/**
	 * Add content for the column
	 *
	 * @param string $content
	 * @param string $column_name
	 * @param int $term_id
	 *
	 * @return string
	 */
	public function column_content( $content, $column_name, $term_id ) {
		if ( '_category_image_id' == $column_name ) {
			$thumbnail_id = absint( get_term_meta( $term_id, '_category_image_id', true ) );
			$image        = wp_get_attachment_thumb_url( $thumbnail_id );

			if ( filter_var( $image, FILTER_VALIDATE_URL ) ) {
				$content = '<img src="' . $image . '" width="32" height="32" />';
			} else {
				$content = '<img src="' . $this->get_default_image_url() . '" width="32" height="32" />';
			}
		}

		return $content;
	}

	/**
	 * Term create page form field
	 */
	public function add_form_fields() {
		$image = $this->get_default_image_url();
		?>
		<div class="form-field term-thumbnail-wrap">
			<label><?php esc_html_e( 'Category Image' ); ?></label>
			<div id="product_cat_thumbnail" style="float: left; margin-right: 10px;">
				<img src="<?php echo $image; ?>" width="60px" height="60px"/>
			</div>
			<div style="line-height: 60px;">
				<input type="hidden" id="_category_image_id" name="_category_image_id"/>
				<button type="button"
						class="upload_image_button button"><?php esc_html_e( 'Upload/Add image' ); ?></button>
				<button type="button"
						class="remove_image_button button"><?php esc_html_e( 'Remove image' ); ?></button>
			</div>
			<script type="text/javascript">

				// Only show the "remove image" button when needed
				if (!jQuery('#_category_image_id').val()) {
					jQuery('.remove_image_button').hide();
				}

				// Uploading files
				var file_frame;

				jQuery(document).on('click', '.upload_image_button', function (event) {

					event.preventDefault();

					// If the media frame already exists, reopen it.
					if (file_frame) {
						file_frame.open();
						return;
					}

					// Create the media frame.
					file_frame = wp.media.frames.downloadable_file = wp.media({
						title: '<?php esc_html_e( 'Choose an image' ); ?>',
						button: {
							text: '<?php esc_html_e( 'Use image' ); ?>'
						},
						multiple: false
					});

					// When an image is selected, run a callback.
					file_frame.on('select', function () {
						var attachment = file_frame.state().get('selection').first().toJSON();
						var attachment_thumbnail = attachment.sizes.thumbnail || attachment.sizes.full;

						jQuery('#_category_image_id').val(attachment.id);
						jQuery('#product_cat_thumbnail').find('img').attr('src', attachment_thumbnail.url);
						jQuery('.remove_image_button').show();
					});

					// Finally, open the modal.
					file_frame.open();
				});

				jQuery(document).on('click', '.remove_image_button', function () {
					jQuery('#product_cat_thumbnail').find('img').attr('src', '<?php echo esc_js( $image ); ?>');
					jQuery('#_category_image_id').val('');
					jQuery('.remove_image_button').hide();
					return false;
				});

				jQuery(document).ajaxComplete(function (event, request, options) {
					if (request && 4 === request.readyState && 200 === request.status
						&& options.data && 0 <= options.data.indexOf('action=add-tag')) {

						var res = wpAjax.parseAjaxResponse(request.responseXML, 'ajax-response');
						if (!res || res.errors) {
							return;
						}
						// Clear Thumbnail fields on submit
						jQuery('#product_cat_thumbnail').find('img').attr('src', '<?php echo esc_js( $image ); ?>');
						jQuery('#_category_image_id').val('');
						jQuery('.remove_image_button').hide();
						// Clear Display type field on submit
						jQuery('#display_type').val('');
						return;
					}
				});

			</script>
			<div class="clear"></div>
		</div>
		<?php
	}

	/**
	 * Term edit page form field
	 *
	 * @param WP_Term $term
	 */
	public function edit_form_fields( $term ) {
		$thumbnail_id = absint( get_term_meta( $term->term_id, '_category_image_id', true ) );

		if ( $thumbnail_id ) {
			$image = wp_get_attachment_thumb_url( $thumbnail_id );
		} else {
			$image = $this->get_default_image_url();
		}
		?>

		<tr class="form-field term-thumbnail-wrap">
			<th scope="row" valign="top"><label><?php esc_html_e( 'Thumbnail' ); ?></label></th>
			<td>
				<div id="product_cat_thumbnail" style="float: left; margin-right: 10px;">
					<img src="<?php echo $image; ?>" width="60px" height="60px"/>
				</div>
				<div style="line-height: 60px;">
					<input type="hidden" id="_category_image_id" name="_category_image_id"
						   value="<?php esc_attr_e( $thumbnail_id ); ?>"/>
					<button type="button"
							class="upload_image_button button"><?php esc_html_e( 'Upload/Add image' ); ?></button>
					<button type="button"
							class="remove_image_button button"><?php esc_html_e( 'Remove image' ); ?></button>
				</div>
				<script type="text/javascript">

					// Only show the "remove image" button when needed
					if ('0' === jQuery('#_category_image_id').val()) {
						jQuery('.remove_image_button').hide();
					}

					// Uploading files
					var file_frame;

					jQuery(document).on('click', '.upload_image_button', function (event) {

						event.preventDefault();

						// If the media frame already exists, reopen it.
						if (file_frame) {
							file_frame.open();
							return;
						}

						// Create the media frame.
						file_frame = wp.media.frames.downloadable_file = wp.media({
							title: '<?php esc_html_e( 'Choose an image' ); ?>',
							button: {
								text: '<?php esc_html_e( 'Use image' ); ?>'
							},
							multiple: false
						});

						// When an image is selected, run a callback.
						file_frame.on('select', function () {
							var attachment = file_frame.state().get('selection').first().toJSON();
							var attachment_thumbnail = attachment.sizes.thumbnail || attachment.sizes.full;

							jQuery('#_category_image_id').val(attachment.id);
							jQuery('#product_cat_thumbnail').find('img').attr('src', attachment_thumbnail.url);
							jQuery('.remove_image_button').show();
						});

						// Finally, open the modal.
						file_frame.open();
					});

					jQuery(document).on('click', '.remove_image_button', function () {
						jQuery('#product_cat_thumbnail').find('img').attr('src', '<?php echo esc_js( $image ); ?>');
						jQuery('#_category_image_id').val('');
						jQuery('.remove_image_button').hide();
						return false;
					});

				</script>
				<div class="clear"></div>
			</td>
		</tr>
		<?php
	}

	/**
	 * Get default image url
	 *
	 * @return string
	 */
	public function get_default_image_url() {
		return 'data:image/png;base64,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';
	}
}
