<?php

namespace Stackonet\WP\Examples\Testimonial;

/**
 * TestimonialManager class
 */
class TestimonialManager {
	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	private static $instance = null;

	/**
	 * List of testimonials
	 *
	 * @var Testimonial[]
	 */
	private $testimonials = [];

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return TestimonialManager|null
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();

			add_shortcode( 'stackonet_testimonials', [ self::$instance, 'testimonial_view' ] );
			add_action( 'init', [ self::$instance, 'register_post_type' ] );
			add_action( 'save_post', [ self::$instance, 'save_post' ] );
			add_filter( 'manage_edit-' . Testimonial::POST_TYPE . '_columns', [ self::$instance, 'columns_title' ] );
			add_action(
				'manage_' . Testimonial::POST_TYPE . '_posts_custom_column',
				[
					self::$instance,
					'columns_content',
				],
				10,
				2
			);
		}

		return self::$instance;
	}

	/**
	 * Load testimonials content.
	 *
	 * @return string
	 */
	public function testimonial_view(): string {
		$this->testimonials = Testimonial::find();

		// Get FAQs from structured data and add here via JavaScript.
		return '<div id="stackonet-testimonial-view"></div>';
	}

	/**
	 * Print testimonials structured data
	 *
	 * @return void
	 */
	public function testimonial_structured_data() {
		if ( count( $this->testimonials ) < 1 ) {
			return;
		}

		$structured_data = ( new StructuredData( $this->testimonials ) )->get_structured_data();
		echo PHP_EOL;
		echo '<script id="testimonial-structured-data" type="application/ld+json">';
		echo wp_json_encode( $structured_data );
		echo '</script>';
		echo PHP_EOL;
	}

	/**
	 * Save testimonial custom meta data.
	 *
	 * @param int $post_id The post id.
	 *
	 * @return void
	 */
	public function save_post( $post_id ) {
		if ( isset( $_POST['testimonials'] ) && wp_verify_nonce( $_POST['testimonials'], 'testimonials' ) ) {
			Testimonial::save_meta_data( $post_id, 'admin-ui', $_POST['testimonial'] );
		}
	}

	/**
	 * Register post type
	 *
	 * @return void
	 */
	public function register_post_type() {
		register_post_type(
			Testimonial::POST_TYPE,
			Testimonial::get_post_type_args(
				'Testimonials',
				'Testimonials',
				'Testimonial',
				[
					'supports'             => [ 'title', 'editor', 'thumbnail' ],
					'register_meta_box_cb' => [ $this, 'add_meta_box' ],
				]
			)
		);
	}

	/**
	 * Adding the necessary metabox
	 */
	public function add_meta_box() {
		add_meta_box(
			'testimonials_form',
			__( 'Client Info', 'stackonet-toolkit' ),
			array( $this, 'meta_box_cb' ),
			Testimonial::POST_TYPE,
			'side',
			'high'
		);
	}

	/**
	 * Adding the necessary metabox
	 *
	 * @param \WP_Post $post The post object.
	 */
	public function meta_box_cb( $post ) {
		$_author_name          = get_post_meta( $post->ID, '_author_name', true );
		$_author_job_title     = get_post_meta( $post->ID, '_author_job_title', true );
		$_author_works_for     = get_post_meta( $post->ID, '_author_works_for', true );
		$_author_review_rating = get_post_meta( $post->ID, '_author_review_rating', true );

		wp_nonce_field( 'testimonials', 'testimonials' );
		?>
		<p>
			<label for="_author_name">
				<strong><?php esc_html_e( 'Client Name', 'stackonet-toolkit' ); ?></strong>
			</label>
			<input type="text" class="widefat" id="_author_name" name="testimonial[_author_name]"
				   value="<?php echo esc_attr( $_author_name ); ?>">
		</p>
		<p>
			<label for="_author_works_for">
				<strong><?php esc_html_e( 'Works for (Company Name)', 'stackonet-toolkit' ); ?></strong>
			</label>
			<input type="text" class="widefat" id="_author_works_for" name="testimonial[_author_works_for]"
				   value="<?php echo esc_attr( $_author_works_for ); ?>">
		</p>
		<p>
			<label for="_author_job_title">
				<strong><?php esc_html_e( 'Job Title', 'stackonet-toolkit' ); ?></strong>
			</label>
			<input type="text" class="widefat" id="_author_job_title" name="testimonial[_author_job_title]"
				   value="<?php echo esc_attr( $_author_job_title ); ?>">
		</p>
		<p>
			<label for="_author_review_rating">
				<strong><?php esc_html_e( 'Rating', 'stackonet-toolkit' ); ?></strong>
			</label>
			<input type="number" class="widefat" id="_author_review_rating" name="testimonial[_author_review_rating]"
				   min="1" max="5" value="<?php echo esc_attr( $_author_review_rating ); ?>">
		</p>
		<?php
	}

	/**
	 * Modifying the list view columns
	 *
	 * This functions is attached to the 'manage_edit-testimonials_columns' filter hook.
	 *
	 * @return array
	 */
	public function columns_title() {
		return [
			'cb'          => '<input type="checkbox">',
			'title'       => __( 'Title', 'stackonet-toolkit' ),
			'rating'      => __( 'Rating', 'stackonet-toolkit' ),
			'name'        => __( 'Name', 'stackonet-toolkit' ),
			'company'     => __( 'Company', 'stackonet-toolkit' ),
			'designation' => __( 'Designation', 'stackonet-toolkit' ),
			'avatar'      => __( 'Avatar', 'stackonet-toolkit' ),
		];
	}

	/**
	 * Customizing the list view columns
	 *
	 * This function is attached to the 'manage_posts_custom_column' action hook.
	 *
	 * @param string $column The column name.
	 * @param int $post_id The post id.
	 */
	public function columns_content( $column, $post_id ) {
		$testimonial = new Testimonial( $post_id );
		switch ( $column ) {
			case 'testimonial':
				echo esc_attr( wp_trim_words( $testimonial->get_excerpt(), 10, '...' ) );
				break;
			case 'rating':
				echo esc_html( $testimonial->get_author_review_rating() );
				break;
			case 'name':
				echo esc_html( $testimonial->get_author_name() );
				break;
			case 'company':
				echo esc_html( $testimonial->get_author_works_for() );
				break;
			case 'designation':
				echo esc_html( $testimonial->get_author_job_title() );
				break;
			case 'avatar':
				if ( has_post_thumbnail() ) {
					echo get_the_post_thumbnail( $post_id, [ 64, 64 ] );
				}
				break;
		}
	}
}
