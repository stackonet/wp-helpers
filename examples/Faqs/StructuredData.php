<?php

namespace Stackonet\WP\Examples\Faqs;

/**
 * StructuredData
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
	 * @param Faq[] $faqs List of Faq object.
	 */
	public function __construct( array $faqs = [] ) {
		foreach ( $faqs as $faq ) {
			self::$data[] = [
				'@type'          => 'Question',
				'name'           => $faq->get_question(),
				'acceptedAnswer' => [
					'@type' => 'Answer',
					'text'  => $faq->get_answer(),
				],
			];
		}
	}

	/**
	 * Get array data.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return [
			'@context'   => 'https://schema.org',
			'@type'      => 'FAQPage',
			'mainEntity' => self::$data,
		];
	}
}
