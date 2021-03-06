<?php
/**
 * Handles storage and retrieval of social networks
 *
 * @class    ESS_Social_Networks
 * @version  1.0.0
 * @package  EasySocialSharing/Classes
 * @category Class
 * @author   ThemeGrill
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ESS_Social_Networks Class
 */
class ESS_Social_Networks {

	/**
	 * Get social networks from the database.
	 * @since  1.2.0
	 * @return array
	 */
	public static function get_networks() {
		global $wpdb;

		$raw_networks = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}ess_social_networks order by network_order ASC;" );
		$networks     = array();

		foreach ( $raw_networks as $key => $raw_network ) {
			$networks[ $key ]                         = $raw_network;
			$networks[ $key ]->formatted_network_name = self::get_formatted_name( $raw_network->network_name );
		}

		return $networks;
	}

	/**
	 * Get array of social networks name.
	 * @since  1.2.0
	 * @return array of strings
	 */
	public static function get_network_names() {
		return wp_list_pluck( self::get_networks(), 'network_name' );
	}

	/**
	 * Get social network desc.
	 * @since  1.2.0
	 * @return array of strings
	 */
	public static function get_network_desc() {
		return wp_list_pluck( self::get_networks(), 'network_desc', 'network_name' );
	}

	/**
	 * Get social network count.
	 * @since  1.2.0
	 * @return array of strings
	 */
	public static function get_network_count() {
		return wp_list_pluck( self::get_networks(), 'network_count', 'network_name' );
	}

	/**
	 * Get array of social networks name.
	 * @since  1.2.0
	 * @return array of strings
	 */
	public static function get_allowed_networks() {
		if ( 'yes' === get_option( 'easy_social_sharing_api_support_networks_only' ) ) {
			return array_intersect( self::get_network_names(), ess_get_share_networks_with_api_support() );
		}

		return self::get_network_names();
	}

	/**
	 * Format social network name.
	 * @param  string $name
	 * @return string
	 */
	private static function get_formatted_name( $name ) {
		$supported_networks = ess_get_core_supported_social_networks();
		return $name ? $supported_networks[ $name ] : __( 'Facebook', 'easy-social-sharing' );
	}

	/**
	 * Prepare and format network data for DB insertion.
	 * @param  array $network_data
	 * @return array
	 */
	private static function prepare_network_data( $network_data ) {
		foreach ( $network_data as $key => $value ) {
			if ( method_exists( __CLASS__, 'format_' . $key ) ) {
				$network_data[ $key ] = call_user_func( array( __CLASS__, 'format_' . $key ), $value );
			}
		}
		return $network_data;
	}

	/**
	 * Format the social network name.
	 * @param  string $name
	 * @return string
	 */
	private static function format_network_name( $name ) {
		return strtolower( $name );
	}

	/**
	 * Format the social network description.
	 * @param  string $desc
	 * @return string
	 */
	private static function format_network_desc( $desc ) {
		return ess_clean( $desc );
	}

	/**
	 * Format the social network count.
	 * @param  int $count
	 * @return int
	 */
	private static function format_network_count( $count ) {
		return absint( $count );
	}

	/**
	 * Format the social network API support.
	 * @param  int $api_support
	 * @return int
	 */
	private static function format_is_api_support( $api_support ) {
		return absint( $api_support );
	}

	/**
	 *  Insert a new social network.
	 *
	 * Internal use only.
	 *
	 * @since  1.2.0
	 * @access private
	 *
	 * @param  array $network_data
	 * @return int   Social network ID.
	 */
	public static function _insert_network( $network_data ) {
		global $wpdb;

		$wpdb->insert( $wpdb->prefix . 'ess_social_networks', self::prepare_network_data( $network_data ) );

		return $wpdb->insert_id;
	}

	/**
	 * Update a social network.
	 *
	 * Internal use only.
	 *
	 * @since  1.2.0
	 * @access private
	 *
	 * @param int   $network_id
	 * @param array $network_data
	 */
	public static function _update_network( $network_id, $network_data ) {
		global $wpdb;

		$network_id = absint( $network_id );

		$wpdb->update(
			$wpdb->prefix . "ess_social_networks",
			self::prepare_network_data( $network_data ),
			array(
				'network_id' => $network_id
			)
		);
	}

	/**
	 * Delete a social network from the database.
	 *
	 * Internal use only.
	 *
	 * @since  1.2.0
	 * @access private
	 *
	 * @param int $network_id
	 */
	public static function _delete_network( $network_id ) {
		global $wpdb;

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}ess_social_networks WHERE network_id = %d;", $network_id ) );
	}

	/**
	 * Update API Support for a social network in the DB.
	 *
	 * Internal use only.
	 *
	 * @since  1.2.0
	 * @access private
	 *
	 * @param int    $network_id
	 * @param string $network_name
	 */
	public static function _update_network_api_support( $network_id, $network_name ) {
		global $wpdb;

		$is_api_support = in_array( $network_name, ess_get_share_networks_with_api_support() );
		$wpdb->update( $wpdb->prefix . 'ess_social_networks', array( 'is_api_support' => $is_api_support ), array( 'network_id' => $network_id ) );
	}
}
