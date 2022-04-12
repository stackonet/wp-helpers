<?php

namespace Stackonet\WP\Examples\Faqs;

/**
 * FaqManager class
 */
class FaqManager {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * The list of FAQs
	 *
	 * @var Faq[]
	 */
	private $faqs = [];

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return FaqManager|null
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_action( 'init', [ self::$instance, 'register_post_type' ] );
			add_shortcode( 'stackonet_faqs', [ self::$instance, 'faqs' ] );
		}

		return self::$instance;
	}

	/**
	 * Load faqs content.
	 *
	 * @return string
	 */
	public function faqs(): string {
		add_action( 'wp_footer', [ self::$instance, 'faqs_structured_data' ] );

		$this->faqs = Faq::find();
		$nav_html   = '<ul class="faq-nav">' . PHP_EOL;
		$list_html  = '<div class="faq-list">' . PHP_EOL;
		foreach ( $this->faqs as $faq ) {
			$nav_html .= '<li class="faq-nav__item">';
			$nav_html .= '<a href="#faq-' . esc_attr( $faq->get_id() ) . '">' . esc_html( $faq->get_question() ) . '</a>';
			$nav_html .= '</li>' . PHP_EOL;

			$list_html .= '<div id="faq-' . $faq->get_id() . '" class="faq-list__item">' . PHP_EOL;
			$list_html .= '<h4 class="faq-list__question">' . esc_html( $faq->get_question() ) . '</h4>' . PHP_EOL;
			$list_html .= '<div class="faq-list__answer">' . $faq->get_answer() . '</div>' . PHP_EOL;
			$list_html .= '</div><!-- #faq-' . $faq->get_id() . '-->' . PHP_EOL;
		}
		$list_html .= '</div>' . PHP_EOL;
		$nav_html  .= '</ul>' . PHP_EOL;

		// Get FAQs from structured data and add here via JavaScript.
		return '<div id="winycart-faq-page">' . $nav_html . $list_html . '</div>';
	}

	/**
	 * Print faqs structured data
	 *
	 * @return void
	 */
	public function faqs_structured_data() {
		if ( count( $this->faqs ) < 1 ) {
			return;
		}
		$structured_data = ( new StructuredData( $this->faqs ) )->to_array();
		echo PHP_EOL;
		echo '<script id="faq-structured-data" type="application/ld+json">';
		echo wp_json_encode( $structured_data );
		echo '</script>';
		echo PHP_EOL;
	}

	/**
	 * Register post type
	 *
	 * @return void
	 */
	public function register_post_type() {
		register_post_type(
			Faq::POST_TYPE,
			Faq::get_post_type_args(
				'FAQs',
				'Posts',
				'Post',
				[
					'supports' => [ 'title', 'editor', 'page-attributes' ],
				]
			)
		);
	}
}
