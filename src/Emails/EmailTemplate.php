<?php

namespace Stackonet\WP\Framework\Emails;

defined( 'ABSPATH' ) || exit;

class EmailTemplate {

	/**
	 * Email default style
	 *
	 * @var array
	 */
	protected $style = [
		/* Layout ------------------------------ */
		'body'                => 'margin: 0; padding: 0; width: 100%; background-color: #F2F4F6;',
		'email-wrapper'       => 'width: 100%; margin: 0; padding: 0; background-color: #F2F4F6;',
		/* Masthead ----------------------- */
		'email-header'        => 'width: auto; max-width: 570px; margin: 0 auto; padding: 0; text-align: center;',
		'email-masthead'      => 'padding: 25px 0; text-align: center;',
		'email-masthead_name' => 'font-size: 16px; font-weight: bold; color: #2F3133; text-decoration: none; text-shadow: 0 1px 0 white;',
		'email-body'          => 'width: 100%; margin: 0; padding: 0; border-top: 1px solid #EDEFF2; border-bottom: 1px solid #EDEFF2; background-color: #FFF;',
		'email-body_inner'    => 'width: auto; max-width: 570px; margin: 0 auto; padding: 0;',
		'email-body_cell'     => 'padding: 35px;',
		'email-footer'        => 'width: auto; max-width: 570px; margin: 0 auto; padding: 0; text-align: center;',
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
	 * Email logo
	 *
	 * @var string
	 */
	protected $logo = '';

	/**
	 * Greeting text
	 *
	 * @var string
	 */
	protected $greeting = '';

	/**
	 * Action button text
	 *
	 * @var string
	 */
	protected $actionText = '';

	/**
	 * Action button url
	 *
	 * @var string
	 */
	protected $actionUrl = '';

	/**
	 * @var array
	 */
	protected $intro_lines = [];

	/**
	 * @var array
	 */
	protected $outro_lines = [];

	/**
	 * @var string
	 */
	protected $level = 'default';

	/**
	 * Salutation
	 *
	 * @var string
	 */
	protected $salutation = '';

	/**
	 * Email footer text
	 *
	 * @var string
	 */
	protected $footer_text;

	/**
	 * Show sub copy
	 *
	 * @var bool
	 */
	protected $show_sub_copy = true;

	/**
	 * Show sub copy
	 *
	 * @param bool $show_sub_copy
	 */
	public function set_show_sub_copy( $show_sub_copy ) {
		$this->show_sub_copy = $show_sub_copy;
	}

	/**
	 * @param string $greeting
	 */
	public function set_greeting( $greeting ) {
		$this->greeting = $greeting;
	}

	/**
	 * Set action
	 *
	 * @param string $text
	 * @param string $url
	 * @param string $level
	 */
	public function set_action( $text, $url, $level = '' ) {
		if ( ! empty( $text ) ) {
			$this->actionText = $text;
		}
		if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$this->actionUrl = $url;
		}

		if ( in_array( $level, [ 'default', 'success', 'error' ] ) ) {
			$this->level = $level;
		}
	}

	/**
	 * Check if has action
	 *
	 * @return bool
	 */
	public function has_action() {
		return ! empty( $this->actionText ) || ! empty( $this->actionUrl );
	}

	/**
	 * Set intro lines
	 *
	 * @param array|string $intro_lines
	 */
	public function set_intro_lines( $intro_lines ) {
		if ( is_array( $intro_lines ) ) {
			$this->intro_lines = $intro_lines;
		}
		if ( is_string( $intro_lines ) ) {
			$this->intro_lines[] = $intro_lines;
		}
	}

	/**
	 * Set intro lines
	 *
	 * @param array|string $outro_lines
	 */
	public function set_outro_lines( $outro_lines ) {
		if ( is_array( $outro_lines ) ) {
			$this->outro_lines = $outro_lines;
		}
		if ( is_string( $outro_lines ) ) {
			$this->outro_lines[] = $outro_lines;
		}
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
	 * Get default logo
	 */
	public function get_default_logo() {
		$blog_name = $this->get_blogname();
		$logo      = get_option( 'woocommerce_email_header_image' );
		if ( ! empty( $logo ) && filter_var( $logo, FILTER_VALIDATE_URL ) ) {
			return '<img src="' . $logo . '" width="75" alt="' . $blog_name . '"/>';
		}

		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			$logo = wp_get_attachment_image_src( $custom_logo_id, 'medium_large' );

			return '<img src="' . $logo[0] . '" width="75" alt="' . $blog_name . '"/>';
		}

		return $blog_name;
	}

	/**
	 * Get logo
	 *
	 * @return string
	 */
	protected function get_log() {
		if ( ! empty( $this->logo ) ) {
			return $this->logo;
		}

		return $this->get_default_logo();
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
	 */
	public function set_footer_text( $footer_text ) {
		$this->footer_text = $footer_text;
	}

	/**
	 * Get default salutation
	 *
	 * @return string
	 */
	public function get_default_salutation() {
		return 'Regards,<br>' . $this->get_blogname();
	}

	/**
	 * Get salutation
	 *
	 * @return string
	 */
	public function get_salutation() {
		return $this->salutation;
	}

	/**
	 * @param string $salutation
	 */
	public function set_salutation( $salutation ) {
		$this->salutation = $salutation;
	}

	/**
	 * Email wrapper start
	 */
	public function email_wrapper_start() {
		?>
		<!DOCTYPE html>
		<html>
		<head>
			<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
			<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
			<title><?php echo $this->get_blogname(); ?></title>
			<style type="text/css" rel="stylesheet" media="all">
				@media only screen and (max-width: 500px) {
					.button {
						width: 100% !important;
					}
				}
			</style>
		</head>

		<body style="<?php echo $this->style['body']; ?>">

		<table width="100%" cellpadding="0" cellspacing="0">
		<tr>
		<td style="<?php echo $this->style['email-wrapper']; ?>" align="center">
		<?php
	}

	/**
	 * Email wrapper end
	 */
	public function email_wrapper_end() {
		?>
		</td>
		</tr>
		</table>

		</body>
		</html>
		<?php
	}

	/**
	 * Section start
	 *
	 * @param array $args
	 */
	public function section_start( array $args = [] ) {
		$default       = [ 'content-width' => 600, 'section-style' => '', 'table-style' => '', 'cell-style' => '' ];
		$styles        = wp_parse_args( $args, $default );
		$width         = intval( $styles['content-width'] );
		$style_section = "width:100%;margin:0;padding:0;background-color:#ffffff;" . $styles['section-style'];
		$style_table   = "width: auto;max-width: " . $width . "px;margin: 0 auto;padding: 0;" . $styles['table-style'];
		$style_cell    = $this->fontFamily . $styles['cell-style'];
		?>
		<tr class="email-section">
		<td style="<?php echo $this->get_unique_styles( $style_section ) ?>">
		<table style="<?php echo $this->get_unique_styles( $style_table ); ?>" width="<?php echo $width ?>" align="center" cellpadding="0" cellspacing="0">
		<tr>
		<td style="<?php echo $this->get_unique_styles( $style_cell ); ?>">
		<?php
	}

	/**
	 * Section end
	 */
	public function section_end() {
		?>
		</td>
		</tr>
		</table>
		</td>
		</tr>
		<?php
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
	 * Get content html
	 */
	public function get_content_html() {
		ob_start();
		$this->email_wrapper_start()
		?>
		<table width="100%" cellpadding="0" cellspacing="0">
			<?php
			$this->section_start( [
				'section-style' => 'background-color:#F2F4F6;',
				'cell-style'    => $this->style['email-masthead'],
			] );
			$this->get_logo_html();
			$this->section_end();


			$this->section_start( [
				'section-style' => $this->style['email-body'],
				'table-style'   => $this->style['email-body_inner'],
				'cell-style'    => $this->style['email-body_cell'],
			] );

			$this->get_greeting_html();
			$this->get_intro_lines_html();
			$this->get_action_button_html();
			$this->get_outro_lines_html();
			$this->get_salutation_html();
			$this->get_sub_copy_html();

			$this->section_end();


			$this->section_start( [
				'section-style' => 'background-color:#F2F4F6;',
				'cell-style'    => $this->style['email-footer_cell'],
			] );
			$this->get_footer_html();
			$this->section_end();
			?>

		</table>
		<?php
		$this->email_wrapper_end();

		return ob_get_clean();
	}

	/**
	 * Get logo html
	 */
	protected function get_logo_html() {
		?>
		<a style="<?php echo $this->fontFamily . $this->style['email-masthead_name']; ?>" target="_blank"
		   href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php echo $this->get_log(); ?>
		</a>
		<?php
	}

	/**
	 * Default greeting text
	 *
	 * @return string
	 */
	public function get_default_greeting_text() {
		if ( $this->level == 'error' ) {
			return 'Whoops!';
		}

		return 'Hello!';
	}

	/**
	 * Get greeting text
	 */
	protected function get_greeting_html() {
		if ( empty( $this->greeting ) ) {
			return;
		}
		?>
		<h1 style="<?php echo $this->style['header-1']; ?>">
			<?php echo $this->greeting; ?>
		</h1>
		<?php
	}

	/**
	 * Intro lines
	 */
	protected function get_intro_lines_html() {
		if ( empty( $this->intro_lines ) ) {
			return;
		}
		foreach ( $this->intro_lines as $line ) { ?>
			<p style="<?php echo $this->style['paragraph']; ?>">
				<?php echo $line; ?>
			</p>
			<?php
		}
	}

	/**
	 * Outro lines
	 */
	protected function get_outro_lines_html() {
		if ( empty( $this->outro_lines ) ) {
			return;
		}
		foreach ( $this->outro_lines as $line ) { ?>
			<p style="<?php echo $this->style['paragraph']; ?>">
				<?php echo $line; ?>
			</p>
			<?php
		}
	}

	/**
	 * Get action button
	 */
	protected function get_action_button_html() {
		if ( ! $this->has_action() ) {
			return;
		}
		?>
		<table style="<?php echo $this->style['body_action']; ?>" align="center" width="100%" cellpadding="0"
		       cellspacing="0">
			<tr>
				<td align="center">
					<?php
					switch ( $this->level ) {
						case 'success':
							$actionColor = 'button--green';
							break;
						case 'error':
							$actionColor = 'button--red';
							break;
						default:
							$actionColor = 'button--blue';
					}
					?>

					<a href="<?php echo $this->actionUrl; ?>" class="button" target="_blank"
					   style="<?php echo $this->fontFamily . $this->style['button'] . $this->style[ $actionColor ]; ?>">
						<?php echo $this->actionText; ?>
					</a>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Salutation
	 */
	protected function get_salutation_html() {
		$salutation = $this->get_salutation();
		if ( empty( $salutation ) ) {
			return;
		}
		?>
		<p style="<?php echo $this->style['paragraph']; ?>">
			<?php echo $salutation; ?>
		</p>
		<?php
	}

	/**
	 * Sub copy text
	 */
	protected function get_sub_copy_html() {
		if ( ! $this->has_action() || ! $this->show_sub_copy ) {
			return;
		}
		?>
		<table style="<?php echo $this->style['body_sub']; ?>">
			<tr>
				<td style="<?php echo $this->fontFamily; ?>">
					<p style="<?php echo $this->style['paragraph-sub']; ?>">
						If you’re having trouble clicking the
						"<?php echo esc_html( $this->actionText ); ?>" button,
						copy and paste the URL below into your web browser:
					</p>

					<p style="<?php echo $this->style['paragraph-sub']; ?>">
						<a style="<?php echo $this->style['anchor']; ?>"
						   href="<?php echo $this->actionUrl; ?>" target="_blank">
							<?php echo $this->actionUrl; ?>
						</a>
					</p>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Get footer
	 */
	protected function get_footer_html() {
		?>
		<p style="<?php echo $this->style['paragraph-sub']; ?>">
			<?php echo $this->get_footer_text() ?>
		</p>
		<?php
	}
}
