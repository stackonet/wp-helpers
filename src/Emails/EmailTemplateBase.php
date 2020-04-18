<?php

namespace Stackonet\WP\Framework\Emails;

defined( 'ABSPATH' ) || exit;

class EmailTemplateBase {

	/**
	 * Email logo
	 *
	 * @var string
	 */
	protected $logo = '';

	/**
	 * If email content should show inside box
	 *
	 * @var bool
	 */
	protected $box_mode = false;

	/**
	 * Email footer text
	 *
	 * @var string
	 */
	protected $footer_text;

	/**
	 * Email default style
	 *
	 * @var array
	 */
	protected $style = [
		/* Layout ------------------------------ */
		'body'                => 'margin: 0; padding: 0; width: 100%; background-color: #f5f5f5;',
		'email-wrapper'       => 'width: 100%; margin: 0; padding: 0; background-color: #f5f5f5;',
		/* Masthead ----------------------- */
		'email-header'        => 'width: auto; max-width: 600px; margin: 0 auto; padding: 0; text-align: center;',
		'email-masthead'      => 'padding: 25px 0; text-align: center;',
		'email-masthead_name' => 'font-size: 16px; font-weight: bold; color: #2F3133; text-decoration: none; text-shadow: 0 1px 0 white;',
		'email-body'          => 'width: 100%; margin: 0; padding: 0; border-top: 1px solid #EDEFF2; border-bottom: 1px solid #EDEFF2; background-color: #FFF;',
		'email-body_inner'    => 'width: 100%; max-width: 600px; margin: 0; padding: 0;',
		'email-body_cell'     => 'padding: 15px;',
		'email-footer'        => 'width: auto; max-width: 600px; margin: 0 auto; padding: 0; text-align: center;',
		'email-footer_cell'   => 'color: #AEAEAE; padding: 35px; text-align: center;',
		/* Body ------------------------------ */
		'body_action'         => 'width: 100%; margin: 30px auto; padding: 0; text-align: center;',
		'body_sub'            => 'margin-top: 25px; padding-top: 25px; border-top: 1px solid #EDEFF2;',
		/* Type ------------------------------ */
		'anchor'              => 'color: #3869D4;',
		'header-1'            => 'margin-top: 0; color: #2F3133; font-size: 19px; font-weight: bold; text-align: left;',
		'paragraph'           => 'margin-top: 0; color: #74787E; font-size: 16px; line-height: 1.5em;',
		'paragraph-sub'       => 'margin-top: 0; color: #74787E; font-size: 12px; line-height: 1.5em;',
		'paragraph-center'    => 'text-align: center;',
		/* Buttons ------------------------------ */
		'button'              => 'display: block; display: inline-block; width: 200px; min-height: 20px; padding: 10px;
                 background-color: #3869D4; border-radius: 3px; color: #ffffff; font-size: 15px; line-height: 25px;
                 text-align: center; text-decoration: none; -webkit-text-size-adjust: none;',
		'button--green'       => 'background-color: #22BC66;',
		'button--red'         => 'background-color: #dc4d2f;',
		'button--blue'        => 'background-color: #3869D4;',
	];

	/**
	 * Email font family
	 *
	 * @var string
	 */
	protected $fontFamily = 'font-family: Arial, \'Helvetica Neue\', Helvetica, sans-serif;';

	/**
	 * Get style
	 *
	 * @param string $key
	 *
	 * @return string
	 */
	public function get_style( $key ) {
		if ( 'font-family' == $key ) {
			return $this->fontFamily;
		}

		return isset( $this->style[ $key ] ) ? $this->style[ $key ] : '';
	}

	/**
	 * Get WordPress blog name.
	 *
	 * @return string
	 */
	public function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}

	/**
	 * Get WordPress home URL
	 *
	 * @return string
	 */
	public function get_home_url() {
		return esc_url( home_url( '/' ) );
	}

	/**
	 * Get default logo
	 */
	public function get_default_logo() {
		$blog_name = $this->get_blogname();

		// Get logo image from WooCommerce email settings if available
		$logo = get_option( 'woocommerce_email_header_image' );
		if ( ! empty( $logo ) && filter_var( $logo, FILTER_VALIDATE_URL ) ) {
			return '<img src="' . $logo . '" alt="' . $blog_name . '"/>';
		}

		// Get logo image from customize settings if available
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			$logo = wp_get_attachment_image_src( $custom_logo_id, 'medium_large' );

			return '<img src="' . $logo[0] . '" alt="' . $blog_name . '"/>';
		}

		// Fallback to site title
		return $blog_name;
	}


	/**
	 * Get logo image
	 *
	 * @return string
	 */
	public function get_logo() {
		if ( ! empty( $this->logo ) ) {
			return '<img src="' . $this->logo . '" alt="' . $this->get_blogname() . '"/>';
		}

		return $this->get_default_logo();
	}

	/**
	 * Get logo html
	 */
	public function get_logo_html() {
		return sprintf( "<a style='%s' target='_blank' href='%s'>%s</a>",
			$this->fontFamily . $this->style['email-masthead_name'],
			$this->get_home_url(),
			$this->get_logo()
		);
	}

	/**
	 * Set logo
	 *
	 * @param string $logo
	 *
	 * @return self
	 */
	public function set_logo( $logo ) {
		if ( filter_var( $logo, FILTER_VALIDATE_URL ) ) {
			$this->logo = $logo;
		}

		return $this;
	}

	/**
	 * @return bool
	 */
	public function is_box_mode() {
		return $this->box_mode;
	}

	/**
	 * @param bool $box_mode
	 *
	 * @return self
	 */
	public function set_box_mode( $box_mode ) {
		$this->box_mode = (bool) $box_mode;

		return $this;
	}

	/**
	 * Make style unique
	 *
	 * @param array|string $styles
	 *
	 * @return string
	 */
	public function get_unique_styles( $styles ) {
		if ( is_string( $styles ) ) {
			$styles = explode( ';', $styles );
		}

		$_styles = [];
		foreach ( $styles as $_style ) {
			$style    = explode( ':', $_style );
			$property = isset( $style[0] ) ? trim( $style[0] ) : '';
			$value    = isset( $style[1] ) ? trim( $style[1] ) : '';
			if ( $property == '' || $value == '' ) {
				continue;
			}
			$_styles[ $property ] = $value;
		}

		$final_styles = [];
		foreach ( $_styles as $property => $value ) {
			$final_styles[] = $property . ":" . $value;
		}

		return implode( ";", $final_styles ) . ";";
	}

	/**
	 * Get footer
	 */
	public function get_footer_html() {
		return sprintf( "<p style='%s'>%s</p>", $this->style['paragraph-sub'], $this->get_footer_text() );
	}

	/**
	 * Get footer text
	 *
	 * @return string
	 */
	public function get_footer_text() {
		if ( ! empty( $this->footer_text ) ) {
			return $this->footer_text;
		}

		return sprintf( "&copy; %s <a style='%s' href='%s' target='_blank'>%s</a>. All rights reserved.",
			date( 'Y' ), $this->style['anchor'], esc_url( home_url( '/' ) ), $this->get_blogname()
		);
	}

	/**
	 * Set footer text
	 *
	 * @param string $footer_text
	 *
	 * @return self
	 */
	public function set_footer_text( $footer_text ) {
		$this->footer_text = $footer_text;

		return $this;
	}

	/**
	 * Get paragraph
	 *
	 * @param string $text
	 * @param string $style
	 *
	 * @return string
	 */
	public function add_paragraph( $text, $style = '' ) {
		$style = $this->get_unique_styles( $this->get_style( 'paragraph' ) . $style );

		return sprintf( "<p style='%s'>%s</p>", $style, $text ) . PHP_EOL;
	}

	/**
	 * Section start
	 *
	 * @param array $args
	 *
	 * @return string
	 */
	public function section_start( array $args = [] ) {
		$default       = [ 'content-width' => 600, 'section-style' => '', 'table-style' => '', 'cell-style' => '' ];
		$styles        = wp_parse_args( $args, $default );
		$width         = intval( $styles['content-width'] );
		$style_section = "width:100%;margin:0;padding:0;background-color:#ffffff;" . $styles['section-style'];
		$style_table   = "width: auto;max-width: " . $width . "px;margin: 0 auto;padding: 0;" . $styles['table-style'];
		$style_cell    = $this->fontFamily . $styles['cell-style'];

		$html = '<tr class="email-section">';
		$html .= '<td style="' . $this->get_unique_styles( $style_section ) . '">';
		$html .= '<table style="' . $this->get_unique_styles( $style_table ) . '" width="' . $width . '" align="center" cellpadding="0" cellspacing="0">';
		$html .= '<tr>';
		$html .= '<td style="' . $this->get_unique_styles( $style_cell ) . '">' . PHP_EOL;

		return $html;
	}

	/**
	 * Section end
	 */
	public function section_end() {
		$html = '</td>';
		$html .= '</tr>';
		$html .= '</table>';
		$html .= '</td>';
		$html .= '</tr>' . PHP_EOL;

		return $html;
	}

	/**
	 * Get email head
	 *
	 * @return string
	 */
	public function get_email_head() {
		$html = '<!DOCTYPE html>' . PHP_EOL;
		$html .= '<html>' . PHP_EOL;
		$html .= '<head>' . PHP_EOL;
		$html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0"/>' . PHP_EOL;
		$html .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>' . PHP_EOL;
		$html .= '<title>' . $this->get_blogname() . '</title>' . PHP_EOL;

		$html .= '<style type="text/css" rel="stylesheet" media="all">' . PHP_EOL;
		$html .= '@media only screen and (max-width: 500px) { .button { width: 100% !important; } }' . PHP_EOL;
		$html .= '</style>' . PHP_EOL;

		$html .= '</head>' . PHP_EOL;

		$html .= '<body style="' . $this->style['body'] . '">' . PHP_EOL;
		$html .= '<table class="body-wrap" width="100%" cellpadding="0" cellspacing="0">' . PHP_EOL;
		$html .= '<tr>' . PHP_EOL;
		$html .= '<td style="' . $this->style['email-wrapper'] . '" align="center">' . PHP_EOL;

		if ( $this->is_box_mode() ) {
			$html .= '<div class="email-content" style="max-width: 600px;">' . PHP_EOL;
		} else {
			$html .= '<div class="email-content">' . PHP_EOL;
		}

		$html .= '<table width="100%" cellpadding="0" cellspacing="0">' . PHP_EOL;

		return $html;
	}

	/**
	 * Get email footer
	 *
	 * @return string
	 */
	public function get_email_footer() {
		$html = '</table>' . PHP_EOL;
		$html .= '</div>' . PHP_EOL;
		$html .= '</td>' . PHP_EOL;
		$html .= '</tr>' . PHP_EOL;
		$html .= '</table>' . PHP_EOL;
		$html .= '</body>' . PHP_EOL;
		$html .= '</html>' . PHP_EOL;

		return $html;
	}

	/**
	 * Email top content
	 *
	 * @return string
	 */
	public function before_content() {
		$html = $this->get_email_head();

		$html .= $this->section_start( [
			'section-style' => 'background-color:#f5f5f5;',
			'cell-style'    => $this->style['email-masthead'],
		] );
		$html .= $this->get_logo_html();
		$html .= $this->section_end();

		$html .= $this->section_start( [
			'section-style' => $this->style['email-body'],
			'table-style'   => $this->style['email-body_inner'],
			'cell-style'    => $this->style['email-body_cell'],
		] );

		return $html;
	}

	/**
	 * Email bottom content
	 *
	 * @return string
	 */
	public function after_content() {
		$html = $this->section_end();

		$html .= $this->section_start( [
			'section-style' => 'background-color:#f5f5f5;',
			'cell-style'    => $this->style['email-footer_cell'],
		] );
		$html .= $this->get_footer_html();
		$html .= $this->section_end();

		$html .= $this->get_email_footer();

		return $html;
	}
}
