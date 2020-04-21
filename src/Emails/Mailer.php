<?php

namespace Stackonet\WP\Framework\Emails;

use Exception;

defined( 'ABSPATH' ) || exit;

class Mailer extends ActionEmailTemplate {

	/**
	 * list of email addresses to send message.
	 *
	 * @var array
	 */
	private $address = [];

	/**
	 * Email subject
	 *
	 * @var string
	 */
	private $subject = '';

	/**
	 * Email body
	 *
	 * @var string
	 */
	private $message = '';

	/**
	 * List of headers
	 *
	 * @var array
	 */
	private $headers = [];

	/**
	 * List of attachments file path
	 *
	 * @var array
	 */
	private $attachments = [];

	/**
	 * Check mail content type is HTML
	 *
	 * @var bool
	 */
	private $is_html = true;

	/**
	 * Email from address
	 *
	 * @var string
	 */
	protected $from_address;

	/**
	 * Email from name
	 *
	 * @var string
	 */
	protected $from_name;

	/**
	 * Send mail using WordPress wp_mail() function
	 *
	 * @return bool Whether the email contents were sent successfully.
	 * @throws Exception
	 */
	public function send() {
		if ( $this->is_html ) {
			$this->headers[] = 'Content-Type: text/html; charset=UTF-8';

			if ( $this->has_content_html() ) {
				$this->message = $this->get_content_html();
			}
		}

		$this->add_from_address();

		if ( empty( $this->address ) || empty( $this->subject ) || empty( $this->message ) ) {
			throw new Exception( 'Receiver address, Subject and Message are required.' );
		}

		return wp_mail( $this->getAddress(), $this->subject, $this->message, $this->getHeaders(), $this->attachments );
	}

	/**
	 * Get mail headers
	 *
	 * @return array
	 */
	public function getHeaders() {
		$headers  = array_unique( $this->headers );
		$_headers = array();
		foreach ( $headers as $header ) {
			$_headers[] = $this->decodeSpecialChars( $header );
		}

		return $_headers;
	}

	/**
	 * Set mail headers
	 *
	 * @param array|string $headers
	 *
	 * @return $this
	 */
	public function setHeaders( $headers ) {
		if ( is_array( $headers ) ) {
			foreach ( $headers as $header ) {
				if ( ! is_string( $header ) ) {
					continue;
				}
				$this->headers[] = $this->encodeSpecialChars( $header );
			}
		}

		if ( is_string( $headers ) ) {
			$this->headers[] = $this->encodeSpecialChars( $headers );
		}

		return $this;
	}

	/**
	 * Set mail sender email address and name(optional)
	 *
	 * @param string $address
	 * @param string $name
	 *
	 * @return self
	 * @throws Exception
	 */
	public function setFrom( $address, $name = null ) {
		if ( ! is_email( $address ) ) {
			throw new Exception( 'Address must be a valid email.' );
		}
		$this->from_address = $address;
		if ( ! empty( $name ) ) {
			$this->from_name = sanitize_text_field( $name );
		}

		return $this;
	}

	/**
	 * Set Cc for mail
	 *
	 * @param string $address
	 * @param string $name
	 *
	 * @return self
	 * @throws Exception
	 */
	public function setCc( $address, $name = null ) {
		if ( ! is_email( $address ) ) {
			throw new Exception( 'Address must be a valid email.' );
		}
		if ( ! empty( $name ) ) {
			$name            = sanitize_text_field( $name );
			$this->headers[] = $this->encodeSpecialChars( "Cc: {$name} <{$address}>" );
		} else {
			$this->headers[] = "Cc: $address";
		}

		return $this;
	}

	/**
	 * Set Bcc
	 *
	 * @param string $address
	 * @param string $name
	 *
	 * @return self
	 * @throws Exception
	 */
	public function setBcc( $address, $name = null ) {
		if ( ! is_email( $address ) ) {
			throw new Exception( 'Address must be a valid email.' );
		}

		$address = sanitize_email( $address );
		if ( ! empty( $name ) ) {
			$name            = sanitize_text_field( $name );
			$this->headers[] = $this->encodeSpecialChars( "Bcc: {$name} <{$address}>" );
		} else {
			$this->headers[] = "Bcc: $address";
		}

		return $this;
	}

	/**
	 * Set reply to email address
	 *
	 * @param string $address
	 * @param string $name
	 *
	 * @return self
	 * @throws Exception
	 */
	public function setReplyTo( $address, $name = null ) {
		if ( ! is_email( $address ) ) {
			throw new Exception( 'Address must be a valid email.' );
		}

		$address = sanitize_email( $address );
		if ( ! empty( $name ) ) {
			$name            = sanitize_text_field( $name );
			$this->headers[] = $this->encodeSpecialChars( "Reply-To: {$name} <{$address}>" );
		} else {
			$this->headers[] = "Reply-To: $address";
		}

		return $this;
	}

	/**
	 * Get mail address
	 *
	 * @return array
	 */
	public function getAddress() {
		$_address = [];
		foreach ( $this->address as $address ) {
			$_address[] = $this->decodeSpecialChars( $address );
		}

		return $_address;
	}

	/**
	 * Set mail receiver
	 *
	 * @param string $address
	 * @param string $name
	 *
	 * @return self
	 * @throws Exception
	 */
	public function setAddress( $address, $name = null ) {
		if ( ! is_email( $address ) ) {
			throw new Exception( 'Address must be a valid email.' );
		}

		$address = sanitize_email( $address );
		if ( ! empty( $name ) ) {
			$name            = sanitize_text_field( $name );
			$this->address[] = $this->encodeSpecialChars( "{$name} <{$address}>" );
		} else {
			$this->address[] = $address;
		}

		return $this;
	}

	/**
	 * Set mail receiver
	 *
	 * @param string $address
	 * @param string $name
	 *
	 * @return self
	 * @throws Exception
	 */
	public function setTo( $address, $name = null ) {
		return $this->setAddress( $address, $name );
	}

	/**
	 * Set mail receiver
	 *
	 * @param string $address
	 * @param string $name
	 *
	 * @return self
	 * @throws Exception
	 */
	public function setReceiver( $address, $name = null ) {
		return $this->setAddress( $address, $name );
	}

	/**
	 * Set mail content type is html
	 *
	 * @param $value
	 *
	 * @return self
	 */
	public function isHTML( $value ) {
		$this->is_html = (bool) $value;

		return $this;
	}

	/**
	 * Set mail subject
	 *
	 * @param string $subject
	 *
	 * @return self
	 */
	public function setSubject( $subject ) {
		if ( is_string( $subject ) ) {
			$this->subject = sanitize_text_field( $subject );
		}

		return $this;
	}

	/**
	 * Set mail content
	 *
	 * @param string $message
	 *
	 * @return self
	 */
	public function setMessage( $message ) {
		if ( is_string( $message ) ) {
			$this->message = wp_kses_post( $message );
		}

		return $this;
	}

	/**
	 * Set mail attachment
	 *
	 * @param array|string $attachments file path or array of file path
	 *
	 * @return self
	 */
	public function setAttachments( $attachments ) {
		if ( is_array( $attachments ) ) {
			foreach ( $attachments as $attachment ) {
				if ( file_exists( $attachment ) ) {
					$this->attachments[] = $attachment;
				}
			}
		}

		if ( is_string( $attachments ) && file_exists( $attachments ) ) {
			$this->attachments[] = $attachments;
		}

		return $this;
	}

	/**
	 * Converts a number of special characters into their HTML entities.
	 * Specifically deals with: &, <, >, ", and '.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	private function encodeSpecialChars( $string ) {
		return _wp_specialchars( $string, ENT_QUOTES, 'UTF-8' );
	}

	/**
	 * Converts a number of HTML entities into their special characters.
	 * Specifically deals with: &, <, >, ", and '.
	 *
	 * @param string $string
	 *
	 * @return string
	 */
	private function decodeSpecialChars( $string ) {
		return wp_specialchars_decode( $string, ENT_QUOTES );
	}

	/**
	 * Get mail from address
	 */
	public function add_from_address() {
		if ( empty( $this->from_name ) ) {
			$this->from_name = get_option( 'blogname' );
		}

		if ( empty( $this->from_address ) ) {
			$sitename = strtolower( $_SERVER['SERVER_NAME'] );
			if ( substr( $sitename, 0, 4 ) == 'www.' ) {
				$sitename = substr( $sitename, 4 );
			}

			$this->from_address = 'no-reply@' . $sitename;
		}

		$this->headers[] = $this->encodeSpecialChars( "From: {$this->from_name} <{$this->from_address}>" );
	}
}
