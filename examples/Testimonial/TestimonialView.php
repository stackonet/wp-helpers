<?php

namespace Stackonet\WP\Examples\Testimonial;

/**
 * TestimonialView class
 */
class TestimonialView {
	/**
	 * The testimonial object
	 *
	 * @var Testimonial
	 */
	private $testimonial;

	/**
	 * Class constructor
	 *
	 * @param Testimonial $testimonial The testimonial object.
	 */
	public function __construct( Testimonial $testimonial ) {
		$this->testimonial = $testimonial;
	}

	/**
	 * Get HTML content
	 *
	 * @return string
	 */
	public function get_html(): string {
		$html = '<div class="client-testimonial">';

		$html .= '<div class="client-testimonial__author">';

		$html .= '<div class="client-testimonial__avatar">';
		$html .= $this->get_author_image_html();
		$html .= '</div>'; // .client-testimonial__avatar

		$html .= '<div class="client-testimonial__vcard">';
		$html .= $this->get_author_name_html();
		$html .= $this->get_author_works_for_html();
		$html .= '</div>';

		$html .= '</div>'; // .client-testimonial__author

		$html .= '<div class="client-testimonial__content">';
		$html .= '<div class="client-testimonial__message">' . $this->testimonial->get_content() . '</div>';
		$html .= '</div>'; // .client-testimonial__content

		$html .= '</div>';

		return $html;
	}

	/**
	 * Client avatar placeholder
	 *
	 * @return string
	 */
	public function get_author_image_placeholder(): string {
		return '<svg xmlns="http://www.w3.org/2000/svg" width="60" height="60" viewBox="0 0 24 24">
		<path d="M20 2H4c-1.1 0-1.99.9-1.99 2L2 22l4-4h14c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 9h12v2H6V9zm8 5H6v-2h8v2zm4-6H6V6h12v2z"/>
		<path d="M0 0h24v24H0z" fill="none"/>
		</svg>';
	}

	/**
	 * Get author work for html
	 *
	 * @return string
	 */
	protected function get_author_works_for_html(): string {
		$url = $this->testimonial->get_author_works_for_url();

		$html = '<div class="client-testimonial__client-company">';
		$html .= '<span class="text-secondary color-secondary">' . $this->testimonial->get_author_job_title() . ', </span>';
		if ( ! empty( $url ) ) {
			$html .= '<a href="' . esc_url( $url ) . '" rel="nofollow" target="_blank"
			class="text-secondary color-secondary">';
			$html .= $this->testimonial->get_author_works_for();
			$html .= '</a>';
		} else {
			$html .= '<span class="text-secondary color-secondary">';
			$html .= $this->testimonial->get_author_works_for();
			$html .= '</span>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 * Get author image html
	 *
	 * @return string
	 */
	protected function get_author_image_html(): string {
		$html = '';
		if ( $this->testimonial->has_author_image() ) {
			$html .= '<span class="client-testimonial__avatar-thumb">';
			$html .= $this->testimonial->get_author_image( [ 60, 60 ] );
		} else {
			$html .= '<span class="client-testimonial__avatar-placeholder">';
			$html .= $this->get_author_image_placeholder();
		}
		$html .= '</span>';

		return $html;
	}

	/**
	 * Get author name html
	 *
	 * @return string
	 */
	protected function get_author_name_html(): string {
		$html = '<div class="client-testimonial__client-name">';
		$html .= '<span class="text-primary">';
		$html .= $this->testimonial->get_author_name();
		$html .= '</span>';
		$html .= '</div>';

		return $html;
	}
}
