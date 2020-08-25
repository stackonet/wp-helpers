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
	 * @param mixed  $term_id  Term ID being saved.
	 * @param mixed  $tt_id    Term taxonomy ID.
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
	 * @param int    $term_id
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
		<div class="form-field stackonet-term-thumbnail term-thumbnail-wrap">
			<label><?php esc_html_e( 'Category Image' ); ?></label>
			<div id="product_cat_thumbnail" style="float: left; margin-right: 10px;">
				<img class="thumbnail-image" src="<?php echo $image; ?>" width="60px" height="60px"/>
			</div>
			<div style="line-height: 60px;" class="stackonet-term-image">
				<input type="hidden" id="_category_image_id" name="_category_image_id" class="hidden_image_id"
				       value=""/>
				<button type="button"
				        class="upload_image_button button"><?php esc_html_e( 'Upload/Add image' ); ?></button>
				<button type="button"
				        class="remove_image_button button"><?php esc_html_e( 'Remove image' ); ?></button>
			</div>
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

		<tr class="form-field term-thumbnail-wrap stackonet-term-thumbnail">
			<th scope="row" valign="top"><label><?php esc_html_e( 'Thumbnail' ); ?></label></th>
			<td>
				<div id="product_cat_thumbnail" style="float: left; margin-right: 10px;">
					<img class="thumbnail-image" src="<?php echo $image; ?>" width="60px" height="60px"/>
				</div>
				<div style="line-height: 60px;">
					<input type="hidden" id="_category_image_id" name="_category_image_id" class="hidden_image_id"
					       value="<?php esc_attr_e( $thumbnail_id ); ?>"/>
					<button type="button"
					        class="upload_image_button button"><?php esc_html_e( 'Upload/Add image' ); ?></button>
					<button type="button"
					        class="remove_image_button button"><?php esc_html_e( 'Remove image' ); ?></button>
				</div>
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
		return 'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'256\' height=\'256\'%3E%3Crect height=\'258\' width=\'258\' y=\'-1\' x=\'-1\' fill=\'%23fff\'/%3E%3Cpath d=\'m68.2 64.1l119.8 0c2.2 0 4 1.8 4 4l0 101.8c0.1 0.3 0.1 0.6 0 0.8l0 17.1c0 2.2-1.8 4-4 4l-119.8 0c-2.2 0-4-1.8-4-4l0-13.8c-0.3-0.6-0.3-1.3 0-1.8l0-104.1c0.1-2.2 1.9-4 4-4l0 0zm68.4 85.5l20.3-16.4c0.8-0.7 2-0.6 2.7 0.2l28.4 31.8 0-97 -119.8 0 0 99.6 40.5-44.8c0.4-0.4 0.9-0.7 1.5-0.7 0.6 0 1.1 0.2 1.5 0.6l24.9 26.7 0 0zm51.4 21.5l-30.1-33.7 -20.3 16.4c-0.8 0.7-2 0.6-2.7-0.2l-24.6-26.3 -42 46.4 0 14.2 119.7 0 0-16.8 0 0zm-28.1-90.6c-8.6 0-15.6 7-15.6 15.6 0 8.6 7 15.6 15.6 15.6 8.6 0 15.6-7 15.6-15.6 0.1-8.7-6.9-15.6-15.6-15.6l0 0zm0 3.9c-6.4 0-11.7 5.2-11.7 11.7 0 6.4 5.2 11.7 11.7 11.7 6.4 0 11.7-5.2 11.7-11.7 0-6.5-5.2-11.7-11.7-11.7z\'/%3E%3Cpath d=\'m136.6 149.8l20.3-16.4c0.8-0.7 2-0.6 2.7 0.2l28.4 31.8 0-97 -119.8 0 0 99.6 40.5-44.8c0.4-0.4 0.9-0.7 1.5-0.7 0.6 0 1.1 0.2 1.5 0.6l24.9 26.7 0 0zm23.3-69.1c8.6 0 15.6 7 15.6 15.6 0 8.6-7 15.6-15.6 15.6 -8.6 0-15.6-7-15.6-15.6 0-8.7 7-15.6 15.6-15.6z\' fill=\'%237aced7\'/%3E%3C/svg%3E%0A';
	}
}

/*
 * Example JavaScript for upload image
 *
class StackonetTermImageUploader {
	// Class construct
	constructor(className = '.stackonet-term-thumbnail', config = {}) {
		let defaultConfig = {title: 'Choose an image', button: {text: 'Use image'}, multiple: false};
		this.config = Object.assign(defaultConfig, config);

		let items = document.querySelectorAll(className);
		items.forEach(item => {
			this.handleImageUpload(item);
		});
	}

	// Handle image upload
	handleImageUpload(item) {
		let jquery = window.jQuery,
			_item = jquery(item),
			_thumbImage = _item.find('img.thumbnail-image'),
			_hiddenInput = _item.find('.hidden_image_id'),
			_uploadButton = _item.find('.upload_image_button'),
			_removeButton = _item.find('.remove_image_button'),
			_currentValue = _hiddenInput.val(),
			placeholder = StackonetTermImageUploader.placeholderImage();

		// Only show the "remove image" button when needed
		if (!_currentValue) {
			_removeButton.hide();
		}

		// Uploading files
		let file_frame;

		_uploadButton.on('click', event => {
			event.preventDefault();

			// If the media frame already exists, reopen it.
			if (file_frame) {
				file_frame.open();
				return;
			}

			// Create the media frame.
			file_frame = wp.media(this.config);

			// When an image is selected, run a callback.
			file_frame.on('select', function () {
				let attachment = file_frame.state().get('selection').first().toJSON();
				let attachment_thumbnail = attachment.sizes.thumbnail || attachment.sizes.full;

				_hiddenInput.val(attachment.id);
				_removeButton.show();
				_thumbImage.attr('src', attachment_thumbnail.url);
			});

			// Finally, open the modal.
			file_frame.open();
		});

		// Remove Image
		_removeButton.on('click', () => {
			_thumbImage.attr('src', placeholder);
			_hiddenInput.val('');
			_removeButton.hide();
			return false;
		});

		// Remove image on submit
		jquery(document).ajaxComplete(function (event, request, options) {
			if (request && 4 === request.readyState && 200 === request.status
				&& options.data && 0 <= options.data.indexOf('action=add-tag')) {

				let res = window.wpAjax.parseAjaxResponse(request.responseXML, 'ajax-response');
				if (!res || res.errors) {
					return;
				}
				// Clear Thumbnail fields on submit
				_thumbImage.attr('src', placeholder);
				_hiddenInput.val('');
				_removeButton.hide();
			}
		});
	}

	// Get default image url
	static placeholderImage() {
		return 'data:image/svg+xml;charset=UTF-8,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'256\' height=\'256\'%3E%3Crect height=\'258\' width=\'258\' y=\'-1\' x=\'-1\' fill=\'%23fff\'/%3E%3Cpath d=\'m68.2 64.1l119.8 0c2.2 0 4 1.8 4 4l0 101.8c0.1 0.3 0.1 0.6 0 0.8l0 17.1c0 2.2-1.8 4-4 4l-119.8 0c-2.2 0-4-1.8-4-4l0-13.8c-0.3-0.6-0.3-1.3 0-1.8l0-104.1c0.1-2.2 1.9-4 4-4l0 0zm68.4 85.5l20.3-16.4c0.8-0.7 2-0.6 2.7 0.2l28.4 31.8 0-97 -119.8 0 0 99.6 40.5-44.8c0.4-0.4 0.9-0.7 1.5-0.7 0.6 0 1.1 0.2 1.5 0.6l24.9 26.7 0 0zm51.4 21.5l-30.1-33.7 -20.3 16.4c-0.8 0.7-2 0.6-2.7-0.2l-24.6-26.3 -42 46.4 0 14.2 119.7 0 0-16.8 0 0zm-28.1-90.6c-8.6 0-15.6 7-15.6 15.6 0 8.6 7 15.6 15.6 15.6 8.6 0 15.6-7 15.6-15.6 0.1-8.7-6.9-15.6-15.6-15.6l0 0zm0 3.9c-6.4 0-11.7 5.2-11.7 11.7 0 6.4 5.2 11.7 11.7 11.7 6.4 0 11.7-5.2 11.7-11.7 0-6.5-5.2-11.7-11.7-11.7z\'/%3E%3Cpath d=\'m136.6 149.8l20.3-16.4c0.8-0.7 2-0.6 2.7 0.2l28.4 31.8 0-97 -119.8 0 0 99.6 40.5-44.8c0.4-0.4 0.9-0.7 1.5-0.7 0.6 0 1.1 0.2 1.5 0.6l24.9 26.7 0 0zm23.3-69.1c8.6 0 15.6 7 15.6 15.6 0 8.6-7 15.6-15.6 15.6 -8.6 0-15.6-7-15.6-15.6 0-8.7 7-15.6 15.6-15.6z\' fill=\'%237aced7\'/%3E%3C/svg%3E%0A';
	}
}

export {StackonetTermImageUploader}
export default StackonetTermImageUploader;
 */
