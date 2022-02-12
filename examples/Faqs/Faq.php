<?php

namespace Stackonet\WP\Examples\Faqs;

use Stackonet\WP\Framework\Abstracts\PostTypeModel;

/**
 * Faq class
 */
class Faq extends PostTypeModel {
	const POST_TYPE = 'faq';

	/**
	 * Get array representation of the class
	 *
	 * @return array
	 */
	public function to_array(): array {
		return [
			'id'       => $this->get_id(),
			'question' => $this->get_question(),
			'answer'   => $this->get_answer(),
		];
	}

	/**
	 * Get question
	 *
	 * @return string
	 */
	public function get_question(): string {
		return $this->get_title();
	}

	/**
	 * Get answer
	 *
	 * @return string
	 */
	public function get_answer(): string {
		return $this->get_content();
	}
}
