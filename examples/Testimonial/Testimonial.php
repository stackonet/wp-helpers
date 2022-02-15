<?php

namespace Stackonet\WP\Examples\Testimonial;

use Stackonet\WP\Framework\Abstracts\PostTypeModel;

/**
 * Testimonial class
 */
class Testimonial extends PostTypeModel {
	/**
	 * Post type name
	 */
	const POST_TYPE = 'testimonial';

	/**
	 * Meta fields
	 *
	 * @var array
	 */
	protected static $meta_fields = [
		[
			'meta_key'          => '_author_name',
			'sanitize_callback' => 'sanitize_text_field',
		],
		[
			'meta_key'          => '_author_job_title',
			'sanitize_callback' => 'sanitize_text_field',
		],
		[
			'meta_key'          => '_author_works_for',
			'sanitize_callback' => 'sanitize_text_field',
		],
		[
			'meta_key'          => '_author_works_for_url',
			'sanitize_callback' => 'esc_url',
		],
		[
			'meta_key'          => '_author_review_rating',
			'sanitize_callback' => 'intval',
		],
	];

	/**
	 * Get array representation of the class
	 *
	 * @return array
	 */
	public function to_array(): array {
		return [
			'id'        => $this->get_id(),
			'content'   => $this->get_content(),
			'name'      => $this->get_author_name(),
			'job_title' => $this->get_author_job_title(),
			'company'   => $this->get_author_works_for(),
			'image'     => $this->get_thumbnail_image( 'thumbnail' ),
		];
	}

	/**
	 * Author full name
	 *
	 * @return string
	 */
	public function get_author_name(): string {
		return $this->get_meta( '_author_name' );
	}

	/**
	 * Author job title
	 *
	 * @return string
	 */
	public function get_author_job_title(): string {
		return $this->get_meta( '_author_job_title' );
	}

	/**
	 * Author company name
	 *
	 * @return string
	 */
	public function get_author_works_for(): string {
		return $this->get_meta( '_author_works_for' );
	}

	/**
	 * Author company url
	 *
	 * @return string
	 */
	public function get_author_works_for_url(): string {
		return $this->get_meta( '_author_works_for_url' );
	}

	/**
	 * Author review rating
	 *
	 * @return int
	 */
	public function get_author_review_rating(): int {
		$rating = (int) $this->get_meta( '_author_review_rating', 5 );

		return max( 1, $rating );
	}

	/**
	 * Author image URL
	 *
	 * @param string $size The image size.
	 *
	 * @return string
	 */
	public function get_author_image_url( string $size = 'thumbnail' ): string {
		$image = $this->get_thumbnail_image( $size );

		return $image['url'] ?? '';
	}

	/**
	 * Get author avatar image
	 *
	 * @param string|array $size The image size.
	 *
	 * @return string
	 */
	public function get_author_image( $size = 'thumbnail' ): string {
		$thumbnail_id = get_post_thumbnail_id( $this->post->ID );

		return wp_get_attachment_image( $thumbnail_id, $size );
	}

	/**
	 * Check if avatar exists
	 *
	 * @return bool
	 */
	public function has_author_image(): bool {
		$thumbnail_id = get_post_thumbnail_id( $this->post->ID );

		return (bool) $thumbnail_id;
	}
}
