<?php

namespace Stackonet\WP\Examples\Testimonial;

/**
 * StructuredData class
 */
class StructuredData {
	/**
	 * The structured data.
	 *
	 * @var array
	 */
	protected static $data = [];

	/**
	 * Class constructor
	 *
	 * @param Testimonial[] $testimonials List of Faq object.
	 */
	public function __construct( array $testimonials = [] ) {
		foreach ( $testimonials as $testimonial ) {
			self::$data[] = self::get_item_structured_data( $testimonial );
		}
	}

	/**
	 * Get structured data
	 *
	 * @return array
	 */
	public function get_structured_data(): array {
		return self::$data;
	}

	/**
	 * Get structured data for testimonial.
	 *
	 * @param Testimonial $testimonial The detail of testimonial.
	 *
	 * @return array
	 */
	public static function get_item_structured_data( Testimonial $testimonial ): array {
		$author_data = [
			'@type'    => 'Person',
			'name'     => $testimonial->get_author_name(),
			'jobTitle' => $testimonial->get_author_job_title(),
			'worksFor' => $testimonial->get_author_works_for(),
			'image'    => $testimonial->get_author_image_url(),
		];

		$data = [
			'@type'        => 'Review',
			'reviewRating' => [
				'@type'       => 'Rating',
				'bestRating'  => '5',
				'worstRating' => '1',
				'ratingValue' => (string) $testimonial->get_author_review_rating(),
			],
		];

		$title   = $testimonial->get_title();
		$content = $testimonial->get_content();

		if ( ! empty( $title ) ) {
			$data['name'] = $title;
		}

		if ( ! empty( $content ) ) {
			$data['reviewBody'] = $content;
		}

		foreach ( $author_data as $key => $value ) {
			if ( ! empty( $value ) ) {
				$data['author'][ $key ] = $value;
			}
		}

		return $data;
	}
}
