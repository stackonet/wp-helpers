<?php

namespace Stackonet\WP\Examples\BackgroundTasks;

use Exception;
use Stackonet\WP\Framework\Abstracts\BackgroundProcess;
use Stackonet\WP\Framework\Emails\Mailer;
use Stackonet\WP\Framework\Supports\Logger;

defined( 'ABSPATH' ) || exit;

class BackgroundMailer extends BackgroundProcess {

	/**
	 * The instance of the class
	 *
	 * @var self
	 */
	public static $instance = null;

	/**
	 * Action
	 *
	 * @var string
	 * @access protected
	 */
	protected $action = 'background_mailer';

	/**
	 * Only one instance of the class can be loaded
	 *
	 * @return self
	 */
	public static function init() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over.
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		$user_id = isset( $item['user_id'] ) ? intval( $item['user_id'] ) : 0;
		$user    = get_user_by( 'id', $user_id );

		try {
			$mailer = new Mailer();
			$mailer->setTo( $user->user_email, $user->display_name );
			$mailer->setSubject( 'Test background email.' );
			$mailer->set_greeting( 'Hello ' . $user->display_name . '!' );
			$mailer->set_intro_lines( 'This email is to test background process. If you got this email, then it is working perfectly.' );
			$mailer->set_action( 'Go to Site', $mailer->get_home_url(), 'success' );
			$mailer->send();
		} catch ( Exception $e ) {
			Logger::log( $e->getMessage() );
		}

		// Set false to remove task from queue
		return false;
	}
}

// add_action( 'plugins_loaded', [ BackgroundMailer::class, 'init' ] );

// Example
// ===============================================================================
//// Get a collection of users
//$users = get_users( [ 'number' => 10 ] );
//
//// Get background task instance
//$backgroundMailer = BackgroundMailer::init();
//// Add user to queue
//foreach ( $users as $user ) {
//	$backgroundMailer->push_to_queue( [ 'user_id' => $user->ID ] );
//}
//// Save and run background on shutdown of all code
//add_action( 'shutdown', function () use ( $backgroundMailer ) {
//	$backgroundMailer->save();
//	$backgroundMailer->dispatch();
//}, 100 );
// ===============================================================================
