<?php

namespace Stackonet\WP\Framework\Emails;

defined( 'ABSPATH' ) || exit;

/**
 * Class ActionEmailTemplate
 * Template for actions emails: e. g. activate your account, password resets, welcome emails
 * @package Stackonet\WP\Framework\Emails
 */
class ActionEmailTemplate extends EmailTemplateBase {
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
	protected $action_text = '';

	/**
	 * Action button url
	 *
	 * @var string
	 */
	protected $action_url = '';

	/**
	 * @var string
	 */
	protected $level = 'default';

	/**
	 * @var array
	 */
	protected $intro_lines = [];

	/**
	 * @var array
	 */
	protected $outro_lines = [];

	/**
	 * Salutation
	 *
	 * @var string
	 */
	protected $salutation = '';

	/**
	 * Show sub copy
	 *
	 * @var bool
	 */
	protected $show_sub_copy = true;

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
	 *
	 * @return string
	 */
	public function get_greeting() {
		if ( ! empty( $this->greeting ) ) {
			return $this->greeting;
		}

		return $this->get_default_greeting_text();
	}

	/**
	 * Set greeting text
	 *
	 * @param string $greeting
	 *
	 * @return self
	 */
	public function set_greeting( $greeting ) {
		$this->greeting = $greeting;

		return $this;
	}

	/**
	 * Set action
	 *
	 * @param string $text
	 * @param string $url
	 * @param string $level
	 *
	 * @return self
	 */
	public function set_action( $text, $url, $level = 'default' ) {
		if ( ! empty( $text ) ) {
			$this->action_text = $text;
		}
		if ( filter_var( $url, FILTER_VALIDATE_URL ) ) {
			$this->action_url = $url;
		}

		if ( in_array( $level, [ 'default', 'success', 'error' ] ) ) {
			$this->level = $level;
		}

		return $this;
	}

	/**
	 * Check if has action
	 *
	 * @return bool
	 */
	public function has_action() {
		return ! empty( $this->action_text ) || ! empty( $this->action_url );
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
		if ( ! empty( $this->salutation ) ) {
			return $this->salutation;
		}

		return $this->get_default_salutation();
	}

	/**
	 * @param string $salutation
	 *
	 * @return self
	 */
	public function set_salutation( $salutation ) {
		$this->salutation = $salutation;

		return $this;
	}

	/**
	 * Show sub copy
	 *
	 * @param bool $show_sub_copy
	 *
	 * @return self
	 */
	public function set_show_sub_copy( $show_sub_copy ) {
		$this->show_sub_copy = (bool) $show_sub_copy;

		return $this;
	}


	/**
	 * Set intro lines
	 *
	 * @param array|string $intro_lines
	 *
	 * @return self
	 */
	public function set_intro_lines( $intro_lines ) {
		if ( is_string( $intro_lines ) ) {
			$this->intro_lines[] = $intro_lines;
		}
		if ( is_array( $intro_lines ) ) {
			foreach ( $intro_lines as $intro_line ) {
				$this->set_intro_lines( $intro_line );
			}
		}

		return $this;
	}

	/**
	 * Set outro lines
	 *
	 * @param array|string $outro_lines
	 *
	 * @return self
	 */
	public function set_outro_lines( $outro_lines ) {
		if ( is_string( $outro_lines ) ) {
			$this->outro_lines[] = $outro_lines;
		}
		if ( is_array( $outro_lines ) ) {
			foreach ( $outro_lines as $outro_line ) {
				$this->set_outro_lines( $outro_line );
			}
		}

		return $this;
	}

	/**
	 * Get greeting text
	 */
	public function get_greeting_html() {
		return sprintf( "<h1 style='%s'>%s</h1>", $this->style['header-1'], $this->get_greeting() );
	}

	/**
	 * Intro lines
	 */
	public function get_intro_lines_html() {
		$html = '';
		if ( count( $this->intro_lines ) ) {
			foreach ( $this->intro_lines as $line ) {
				$html .= sprintf( "<p style='%s'>%s</p>", $this->style['paragraph'], $line );
			}
		}

		return $html;
	}

	/**
	 * Outro lines
	 */
	protected function get_outro_lines_html() {
		$html = '';
		if ( count( $this->outro_lines ) ) {
			foreach ( $this->outro_lines as $line ) {
				$html .= sprintf( "<p style='%s'>%s</p>", $this->style['paragraph'], $line );
			}
		}

		return $html;
	}

	/**
	 * Salutation
	 */
	public function get_salutation_html() {
		return sprintf( "<p style='%s'>%s</p>", $this->style['paragraph'], $this->get_salutation() );
	}

	/**
	 * Get action button
	 */
	protected function get_action_button_html() {
		$html = '';
		if ( ! $this->has_action() ) {
			return $html;
		}

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

		$html .= '<table style="' . $this->style['body_action'] . '" align="center" width="100%" cellpadding="0" cellspacing="0">';
		$html .= '<tr><td align="center">';
		$html .= sprintf( "<a href=\"%s\" class=\"button\" target=\"_blank\" style=\"%s\">%s</a>",
			$this->action_url,
			$this->fontFamily . $this->style['button'] . $this->style[ $actionColor ],
			$this->action_text
		);
		$html .= '</td></tr>';
		$html .= '</table>';

		return $html;
	}

	/**
	 * Sub copy text
	 */
	protected function get_sub_copy_html() {
		$html = '';
		if ( ! ( $this->has_action() && $this->show_sub_copy ) ) {
			return $html;
		}
		$html .= '<table style="' . $this->style['body_sub'] . '"><tr><td style="' . $this->fontFamily . '">';

		$html .= sprintf( "<p style=\"%s\">If you’re having trouble clicking the \"%s\" button, copy and paste the URL below into your web browser:</p>",
			$this->style['paragraph-sub'], esc_html( $this->action_text ) );

		$html .= sprintf( "<p style=\"%s\"><a style=\"%s\" href=\"%s\" target=\"_blank\">%s</a></p>",
			$this->style['paragraph-sub'], $this->style['anchor'], esc_url( $this->action_url ), esc_url( $this->action_url ) );

		$html .= '</td></tr></table>';

		return $html;
	}

	/**
	 * Get content html
	 * @return string
	 */
	public function get_content_html() {
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

		$html .= $this->get_greeting_html();
		$html .= $this->get_intro_lines_html();
		$html .= $this->get_action_button_html();
		$html .= $this->get_outro_lines_html();
		$html .= $this->get_salutation_html();
		$html .= $this->get_sub_copy_html();

		$html .= $this->section_end();


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
