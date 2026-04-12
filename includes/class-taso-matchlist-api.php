<?php
/**
 * TASO API client.
 *
 * @package Taso_Matchlist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Taso_Matchlist_API' ) ) {

	/**
	 * Handles TASO REST API requests.
	 */
	class Taso_Matchlist_API {

		/**
		 * Base API URL.
		 *
		 * @var string
		 */
		const BASE_URL = 'https://spl.torneopal.fi/taso/rest/';

		/**
		 * Request timeout in seconds.
		 *
		 * @var int
		 */
		const TIMEOUT = 20;

		/**
		 * Get matches for configured club and date range.
		 *
		 * @return array|\WP_Error
		 */
		public function get_matches() {
			$api_key    = $this->get_api_key();
			$club_id    = $this->get_club_id();
			$days_ahead = $this->get_days_ahead();

			if ( empty( $api_key ) ) {
				return new WP_Error(
					'taso_matchlist_missing_api_key',
					__( 'TASO API -avain puuttuu.', 'taso-matchlist' )
				);
			}

			if ( empty( $club_id ) ) {
				return new WP_Error(
					'taso_matchlist_missing_club_id',
					__( 'Club ID puuttuu.', 'taso-matchlist' )
				);
			}

			$today_timestamp = current_time( 'timestamp' );
			$today           = wp_date( 'Y-m-d', $today_timestamp );
			$end_timestamp   = $today_timestamp + ( absint( $days_ahead ) * DAY_IN_SECONDS );
			$end_date        = wp_date( 'Y-m-d', $end_timestamp );

			$params = array(
				'api_key'     => $api_key,
				'club_id'     => $club_id,
				'home_away'   => 'home',
				'start_date'  => $today,
				'end_date'    => $end_date,
				'page_size'   => 200,
				'page_number' => 1,
			);

			$response = $this->request( 'getMatches', $params );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			return $response;
		}

		/**
		 * Test API connectivity using getMatches call.
		 *
		 * @return array|\WP_Error
		 */
		public function test_connection() {
			$result = $this->get_matches();

			if ( is_wp_error( $result ) ) {
				return $result;
			}

			$matches = $this->extract_matches_from_response( $result );

			return array(
				'ok'           => true,
				'match_count'  => count( $matches ),
				'raw_response' => $result,
			);
		}

		/**
		 * Extract match rows from API response.
		 *
		 * @param array $response API response.
		 * @return array
		 */
		public function extract_matches_from_response( $response ) {
			if ( isset( $response['matches'] ) && is_array( $response['matches'] ) ) {
				return $response['matches'];
			}

			if ( isset( $response['data'] ) && is_array( $response['data'] ) ) {
				return $response['data'];
			}

			if ( isset( $response[0] ) && is_array( $response ) ) {
				return $response;
			}

			return array();
		}

		/**
		 * Perform GET request to TASO API.
		 *
		 * @param string $endpoint Endpoint name.
		 * @param array  $params   Query params.
		 * @return array|\WP_Error
		 */
		public function request( $endpoint, $params = array() ) {
			$endpoint = ltrim( sanitize_text_field( $endpoint ), '/' );
			$url      = trailingslashit( self::BASE_URL ) . $endpoint;

			$url = add_query_arg( $params, $url );

			$response = wp_remote_get(
				$url,
				array(
					'timeout' => self::TIMEOUT,
					'headers' => array(
						'Accept' => 'application/json',
					),
				)
			);

			if ( is_wp_error( $response ) ) {
				return new WP_Error(
					'taso_matchlist_request_failed',
					sprintf(
						/* translators: %s: error message */
						__( 'TASO API -kutsu epäonnistui: %s', 'taso-matchlist' ),
						$response->get_error_message()
					)
				);
			}

			$status_code = wp_remote_retrieve_response_code( $response );
			$body        = wp_remote_retrieve_body( $response );

			if ( 200 !== (int) $status_code ) {
				return new WP_Error(
					'taso_matchlist_bad_status',
					sprintf(
						/* translators: %d: HTTP status code */
						__( 'TASO API palautti virhekoodin %d.', 'taso-matchlist' ),
						(int) $status_code
					),
					array(
						'body' => $body,
					)
				);
			}

			if ( empty( $body ) ) {
				return new WP_Error(
					'taso_matchlist_empty_body',
					__( 'TASO API palautti tyhjän vastauksen.', 'taso-matchlist' )
				);
			}

			$data = json_decode( $body, true );

			if ( JSON_ERROR_NONE !== json_last_error() ) {
				return new WP_Error(
					'taso_matchlist_invalid_json',
					sprintf(
						/* translators: %s: JSON error message */
						__( 'TASO API palautti virheellistä JSON-dataa: %s', 'taso-matchlist' ),
						json_last_error_msg()
					),
					array(
						'body' => $body,
					)
				);
			}

			if ( ! is_array( $data ) ) {
				return new WP_Error(
					'taso_matchlist_invalid_response_shape',
					__( 'TASO API -vastauksen rakenne ei ollut odotettu.', 'taso-matchlist' )
				);
			}

			return $data;
		}

		/**
		 * Get stored API key.
		 *
		 * @return string
		 */
		public function get_api_key() {
			$value = get_option( 'taso_matchlist_api_key', '' );
			return is_string( $value ) ? trim( $value ) : '';
		}

		/**
		 * Get stored club id.
		 *
		 * @return string
		 */
		public function get_club_id() {
			$value = get_option( 'taso_matchlist_club_id', '' );
			$value = is_string( $value ) ? trim( $value ) : '';
			$value = preg_replace( '/[^0-9]/', '', $value );

			return is_string( $value ) ? $value : '';
		}

		/**
		 * Get stored days ahead.
		 *
		 * @return int
		 */
		public function get_days_ahead() {
			$value = absint( get_option( 'taso_matchlist_days_ahead', 90 ) );

			if ( $value < 1 ) {
				$value = 90;
			}

			return $value;
		}

		/**
		 * Get stored cache minutes.
		 *
		 * @return int
		 */
		public function get_cache_minutes() {
			$value = absint( get_option( 'taso_matchlist_cache_minutes', 30 ) );

			if ( $value < 1 ) {
				$value = 30;
			}

			return $value;
		}
	}
}