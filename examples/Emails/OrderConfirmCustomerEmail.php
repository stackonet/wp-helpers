<?php

namespace Stackonet\WP\Examples\Emails;

use Stackonet\WP\Framework\Emails\BillingEmailTemplate;

// If this file is called directly, abort.
defined( 'ABSPATH' ) || exit;

class OrderConfirmCustomerEmail {
	/**
	 * @return string
	 */
	public static function get_content_html() {
		$mailer = new BillingEmailTemplate();
		$mailer->set_logo( 'https://www.bigbasket.com/static/v2267/custPage/build/content/img/bb_logo.png' );
		$mailer->set_box_mode( false );

		$html = $mailer->before_content();

		$html .= $mailer->add_paragraph( 'Hello Sayful!' );
		$html .= $mailer->add_paragraph( 'Thank you for your order at bigbasket', 'text-align:center;font-size:20px;' );

		$html .= $mailer->row_start( 'background-color:#d2d3d5;margin:0 -15px 15px;' );

		$html .= $mailer->column_start( 'padding:15px;' );
		$html .= $mailer->add_paragraph( 'Order No:<br/>MBO-112259629-070320', 'color: #323232;font-size:12px;' );
		$html .= $mailer->add_paragraph( 'Delivery slot:<br/> Sun 08 Mar 2020<br/>between 11:00 AM and 02:00 PM', 'font-size:12px;color: #323232;' );
		$html .= $mailer->column_end();

		$html .= $mailer->column_start( 'padding:15px;' );
		$html .= $mailer->add_paragraph( 'Your order will be delivered to this address:<br/>' . static::customer_address(),
			'font-size:12px;color: #323232;' );
		$html .= $mailer->column_end();

		$html .= $mailer->row_end();

		$columns   = [
			[ 'key' => 'sl', 'label' => 'Sl No.', ],
			[ 'key' => 'details', 'label' => 'Item Details', ],
			[ 'key' => 'qty', 'label' => 'Qty.', 'numeric' => true ],
			[ 'key' => 'sub_total', 'label' => 'Sub Total', 'numeric' => true ],
		];
		$data      = [
			[ 'sl' => 1, 'details' => 'Fresho Amaranthus - Green 250 g', 'qty' => '2', 'sub_total' => 'Rs. 36.00' ],
			[ 'sl' => 2, 'details' => 'Fresho Basale Leaf 250 g', 'qty' => '1', 'sub_total' => 'Rs. 10.00' ],
			[ 'sl' => 3, 'details' => 'Fresho Basale Leaf 250 g', 'qty' => '1', 'sub_total' => 'Rs. 10.00' ],
			[ 'sl' => 4, 'details' => 'Fresho Basale Leaf 250 g', 'qty' => '1', 'sub_total' => 'Rs. 10.00' ],
			[ 'sl' => 5, 'details' => 'Fresho Basale Leaf 250 g', 'qty' => '1', 'sub_total' => 'Rs. 10.00' ],
			[ 'sl' => 6, 'details' => 'Fresho Basale Leaf 250 g', 'qty' => '1', 'sub_total' => 'Rs. 10.00' ],
		];
		$foot_data = [
			[
				[ 'label' => 'Sub Total:', 'colspan' => 3, 'numeric' => true ],
				[ 'label' => 'Rs.&nbsp;2280.56', 'numeric' => true ]
			],
			[
				[ 'label' => 'Credit availed from bigbasket Wallet:', 'colspan' => 3, 'numeric' => true ],
				[ 'label' => 'Rs. -633.0', 'numeric' => true ]
			],
			[
				[ 'label' => 'Total Savings:', 'colspan' => 3, 'numeric' => true ],
				[ 'label' => 'Rs. 343.44', 'numeric' => true ]
			],
			[
				[
					'label'   => 'Final Total:',
					'colspan' => 3,
					'numeric' => true,
					'style'   => 'background-color:#666666;color:#fff;'
				],
				[ 'label' => 'Rs. 1647.56', 'numeric' => true, 'style' => 'background-color:#666666;color:#fff;' ]
			],
		];
		$html      .= $mailer->table_start( 'margin: 0;' );
		$html      .= $mailer->table_head( $columns );
		$html      .= $mailer->table_body( $columns, $data );
		$html      .= $mailer->table_foot( $foot_data );
		$html      .= $mailer->table_end();

		$html .= $mailer->add_paragraph( 'Happy shopping!<br/>Team bigbasket' );
		$html .= $mailer->add_paragraph( 'Note: To comply with GST, we have made a few process changes . Please visit <a target="_blank" href="https://www.bigbasket.com/gst/">https://www.bigbasket.com/gst/</a> to know more.' );

		$html .= $mailer->after_content();

		return $html;
	}

	/**
	 * Get customer address
	 *
	 * @return string
	 */
	public static function customer_address() {
		return 'Sayful Islam,<br># 6 4th floor,<br>20 C 2nd Cross Ejipura Main Rd, Ashwini Layout,<br>
				near to little kolkata,<br>Ejipura,<br>Bangalore - 560047<br>Phone Numbers: 8861721567';
	}
}
