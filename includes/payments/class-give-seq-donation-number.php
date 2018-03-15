<?php
// Exit if access directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Give_Seq_Donation_Number {
	/**
	 * Instance.
	 *
	 * @since  2.1.0
	 * @access private
	 * @var
	 */
	static private $instance;

	/**
	 * Singleton pattern.
	 *
	 * @since  2.1.0
	 * @access private
	 */
	private function __construct() {
	}


	/**
	 * Get instance.
	 *
	 * @since  2.1.0
	 * @access static
	 * @return Give_Seq_Donation_Number
	 */
	public static function get_instance() {
		if ( null === static::$instance ) {
			self::$instance = new static();

			self::$instance->init();
		}

		return self::$instance;
	}

	/**
	 * Initialize the plugin, bailing if any required conditions are not met,
	 * including minimum WooCommerce version
	 *
	 * @since 2.1.0
	 */
	public function init() {
		if ( give_is_setting_enabled( give_get_option( 'sequential-donation_status', 'disabled' ) ) ) {
			add_action( 'wp_insert_post', array( $this, '__save_donation_title' ), 10, 3 );
		}
	}

	/**
	 * Set serialize donation number as donation title.
	 *
	 * @since  2.1.0
	 * @access public
	 *
	 * @param int     $donation_id
	 * @param WP_Post $donation_post_data
	 * @param bool    $existing_donation_updated
	 *
	 * @return void
	 */
	public function __save_donation_title( $donation_id, $donation_post_data, $existing_donation_updated ) {
		// Bailout
		if ( $existing_donation_updated ) {
			return;
		}

		$serial_number = $this->set_donation_number( $donation_id );

		$serial_code = $this->__set_number_padding( $serial_number );

		// Add prefix.
		if ( $prefix = give_get_option( 'sequential-donation_number_prefix', '' ) ) {
			$serial_code = $prefix . $serial_code;
		}

		// Add suffix.
		if ( $suffix = give_get_option( 'sequential-donation_number_suffix', '' ) ) {
			$serial_code = $serial_code . $suffix;
		}

		$serial_code = give_time_do_tags( $serial_code );

		try {
			/* @var WP_Error $wp_error */
			$wp_error = wp_update_post(
				array(
					'ID'         => $donation_id,
					'post_title' => $serial_code
				)
			);

			if ( is_wp_error( $wp_error ) ) {
				throw new Exception( $wp_error->get_error_message() );
			}
		} catch ( Exception $e ) {
			error_log( "Give caught exception: {$e->getMessage()}" );
		}
	}

	/**
	 * Set donation number
	 *
	 * @since  2.1.0
	 * @access public
	 *
	 * @param int $donation_id
	 *
	 * @return int
	 */
	public function set_donation_number( $donation_id ) {
		return Give()->sequential_donation_db->insert( array(
			'payment_id' => $donation_id
		) );
	}

	/**
	 * Set number padding in serial code.
	 *
	 * @since
	 * @access private
	 *
	 * @param $serial_code
	 *
	 * @return string
	 */
	private function __set_number_padding( $serial_code ) {
		if ( $number_padding = give_get_option( 'sequential-donation_number_padding', 0 ) ) {
			$current_str_length = strlen( $serial_code );
			$serial_code        = $number_padding > $current_str_length ?
				substr( '0000000000', 0, $number_padding - $current_str_length ) . $serial_code :
				$serial_code;
		}

		return $serial_code;
	}

	/**
	 * Get donation number serial code
	 *
	 * @since  2.1.0
	 * @access public
	 *
	 * @param int|Give_Payment $donation
	 * @param array            $args
	 *
	 * @return string
	 */
	public function get_serial_code( $donation, $args = array() ) {
		$donation = $donation instanceof Give_Payment ? $donation : new Give_Payment( $donation );

		// Bailout.
		if ( empty( $donation->ID ) ) {
			return null;
		}

		// Set default params.
		$args = wp_parse_args(
			$args,
			array(
				'with_hash' => false,
				'default'   => true
			)
		);

		$serial_code = $args['default'] ? $donation->ID : '';

		if ( $donation_number = $this->get_serial_number( $donation->ID ) ) {
			$serial_code = get_the_title( $donation->ID );
		}

		$serial_code = $args['with_hash'] ? "#{$serial_code}" : $serial_code;

		/**
		 * Filter the donation serial code
		 *
		 * @since 2.1.0
		 */
		return apply_filters( 'give_get_donation_serial_code', $serial_code, $donation, $args, $donation_number );
	}

	/**
	 * Get serial number
	 *
	 * @since  2.1.0
	 * @access public
	 *
	 * @param int $donation_id
	 *
	 * @return string
	 */
	public function get_serial_number( $donation_id ) {
		return Give()->sequential_donation_db->get_column_by( 'id', 'payment_id', $donation_id );
	}


	/**
	 * Get donation id with donation number or serial code
	 *
	 * @since  2.1.0
	 * @access public
	 *
	 * @param string $donation_number_or_serial_code
	 *
	 * @return int
	 */
	public function get_donation_id( $donation_number_or_serial_code ) {
		global $wpdb;

		$is_donation_number = is_numeric( $donation_number_or_serial_code );

		if ( $is_donation_number ) {
			$query = $wpdb->get_prepare( "
				SELECT payment_id
				FROM $wpdb->paymentmeta
				WHERE meta_key=%s
				AND meta_value=%d
			", $this->meta_key, $donation_number_or_serial_code
			);
		} else {
			$query = $wpdb->get_prepare( "
				SELECT payment_id
				FROM $wpdb->posts
				WHERE post_title=%s
			", $donation_number_or_serial_code
			);
		}

		return $wpdb->get_var( $query );
	}


	/**
	 * Get a donation number on basis donation id or donation object
	 *
	 * @since  2.1.0
	 * @access public
	 *
	 * @param int|Give_Payment $donation
	 *
	 * @return int
	 */
	public function get_donation_number( $donation ) {
		global $wpdb;

		$donation    = $donation instanceof Give_Payment ? $donation : new Give_Payment( $donation );
		$donation_id = $donation->ID;

		return $wpdb->get_var(
			$wpdb->get_prepare( "
				SELECT meta_value
				FROM $wpdb->paymentmeta
				WHERE meta_key=%s
				AND payment_id=%d
			", $this->meta_key, $donation_id
			)
		);
	}
}

// @todo: add post_title support in Give_Payment
// @todo: resolve caching issue: donation listing is not updating when updating donation
