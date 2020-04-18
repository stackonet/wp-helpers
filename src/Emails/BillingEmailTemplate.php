<?php

namespace Stackonet\WP\Framework\Emails;

defined( 'ABSPATH' ) || exit;

class BillingEmailTemplate extends EmailTemplateBase {

	/**
	 * Start of a new row
	 *
	 * @param string $style
	 *
	 * @return string
	 */
	public function row_start( $style = '' ) {
		$html = '<div class="email-row" style="' . $this->get_unique_styles( $style ) . '">';
		$html .= '<table style="width:100%;max-width:600px;margin:0;padding:0;" width="600" align="center" cellpadding="0" cellspacing="0"><tr>';

		return $html;
	}

	/**
	 * End of a row
	 *
	 * @return string
	 */
	public function row_end() {
		return '</tr></table></div>';
	}

	/**
	 * Start of a column
	 *
	 * @param string $style
	 *
	 * @return string
	 */
	public function column_start( $style = '' ) {
		$style = $this->get_style( 'font-family' ) . 'vertical-align:top;' . $style;

		return '<td style="' . $this->get_unique_styles( $style ) . '">';
	}

	/**
	 * End of column
	 *
	 * @return string
	 */
	public function column_end() {
		return '</td>';
	}

}
