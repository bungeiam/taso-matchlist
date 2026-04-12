<?php
/**
 * Match normalization and grouping service.
 *
 * @package Taso_Matchlist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Taso_Matchlist_Matches' ) ) {
	/**
	 * Match service.
	 */
	class Taso_Matchlist_Matches {

		/**
		 * Cache key prefix.
		 */
		const CACHE_KEY = 'taso_matchlist_matches';

		/**
		 * API service.
		 *
		 * @var Taso_Matchlist_API
		 */
		private $api;

		/**
		 * Constructor.
		 *
		 * @param Taso_Matchlist_API $api API service.
		 */
		public function __construct( $api ) {
			$this->api = $api;
		}

		/**
		 * Get normalized home matches, cached when possible.
		 *
		 * @param bool $force_refresh Force fresh API request.
		 * @return array|\WP_Error
		 */
		public function get_home_matches( $force_refresh = false ) {
			$cache_key = $this->get_cache_key();

			if ( ! $force_refresh ) {
				$cached = get_transient( $cache_key );
				if ( false !== $cached && is_array( $cached ) ) {
					return $cached;
				}
			}

			$response = $this->api->get_matches();

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$rows       = $this->api->extract_matches_from_response( $response );
			$normalized = $this->normalize_matches( $rows );
			$grouped    = $this->group_matches_by_date( $normalized );

			set_transient(
				$cache_key,
				$grouped,
				absint( $this->api->get_cache_minutes() ) * MINUTE_IN_SECONDS
			);

			return $grouped;
		}

		/**
		 * Clear cached match data.
		 *
		 * @return void
		 */
		public function clear_cache() {
			delete_transient( $this->get_cache_key() );
		}

		/**
		 * Normalize all rows and keep only home matches.
		 *
		 * @param array $rows Raw rows.
		 * @return array
		 */
		public function normalize_matches( $rows ) {
			$normalized = array();

			if ( empty( $rows ) || ! is_array( $rows ) ) {
				return $normalized;
			}

			foreach ( $rows as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}

				$item = $this->normalize_match_row( $row );

				if ( empty( $item ) ) {
					continue;
				}

				if ( ! $item['is_home_match'] ) {
					continue;
				}

				$normalized[] = $item;
			}

			usort(
				$normalized,
				function ( $a, $b ) {
					return strcmp( $a['sort_datetime'], $b['sort_datetime'] );
				}
			);

			return $normalized;
		}

		/**
		 * Normalize single match row.
		 *
		 * @param array $row Raw row.
		 * @return array
		 */
		private function normalize_match_row( $row ) {
			$configured_club_id = $this->api->get_club_id();

			$home_club_id = $this->extract_first_non_empty(
				$row,
				array(
					'club_A_id',
					'home_club_id',
					'club_home_id',
					'clubIdHome',
					'homeClubId',
					'home_club',
					'homeClub',
				)
			);

			$away_club_id = $this->extract_first_non_empty(
				$row,
				array(
					'club_B_id',
					'away_club_id',
					'club_away_id',
					'clubIdAway',
					'awayClubId',
					'away_club',
					'awayClub',
				)
			);

			$home_team_name = $this->extract_first_non_empty(
				$row,
				array(
					'team_A_name',
					'home_team_name',
					'team_home_name',
					'home_name',
					'homeTeamName',
					'home',
					'team1_name',
					'team1Name',
				)
			);

			$away_team_name = $this->extract_first_non_empty(
				$row,
				array(
					'team_B_name',
					'away_team_name',
					'team_away_name',
					'away_name',
					'awayTeamName',
					'away',
					'team2_name',
					'team2Name',
				)
			);

			$home_team_id = $this->extract_first_non_empty(
				$row,
				array(
					'team_A_id',
					'home_team_id',
					'team_home_id',
					'homeTeamId',
					'team1_id',
					'team1Id',
				)
			);

			$away_team_id = $this->extract_first_non_empty(
				$row,
				array(
					'team_B_id',
					'away_team_id',
					'team_away_id',
					'awayTeamId',
					'team2_id',
					'team2Id',
				)
			);

			$competition_name = $this->extract_first_non_empty(
				$row,
				array(
					'competition_name',
					'name',
				)
			);

			$category_name = $this->extract_first_non_empty(
				$row,
				array(
					'category_name',
					'series_name',
					'series',
					'category',
					'group_name',
				)
			);

			$location = $this->extract_first_non_empty(
				$row,
				array(
					'venue_name',
					'field',
					'field_name',
					'location',
					'venue',
					'place',
				)
			);

			$status = $this->extract_first_non_empty(
				$row,
				array(
					'status',
					'match_status',
					'state',
				)
			);

			$match_id = $this->extract_first_non_empty(
				$row,
				array(
					'match_id',
					'id',
					'game_id',
				)
			);

			$home_logo_url = $this->extract_first_non_empty(
				$row,
				array(
					'club_A_crest',
					'home_logo_url',
					'home_team_logo',
					'home_team_logo_url',
					'home_logo',
					'home_team_crest',
				)
			);

			$away_logo_url = $this->extract_first_non_empty(
				$row,
				array(
					'club_B_crest',
					'away_logo_url',
					'away_team_logo',
					'away_team_logo_url',
					'away_logo',
					'away_team_crest',
				)
			);

			$datetime_info = $this->extract_datetime_data( $row );

			$home_club_id = $this->normalize_id_value( $home_club_id );
			$away_club_id = $this->normalize_id_value( $away_club_id );

			$is_home_match = ( '' !== $configured_club_id && '' !== $home_club_id && $configured_club_id === $home_club_id );

			return array(
				'match_id'         => $this->normalize_scalar( $match_id ),
				'competition_name' => $this->normalize_scalar( $competition_name ),
				'category_name'    => $this->normalize_scalar( $category_name ),
				'date'             => $datetime_info['date'],
				'date_label'       => $datetime_info['date_label'],
				'time'             => $datetime_info['time'],
				'sort_datetime'    => $datetime_info['sort_datetime'],
				'status'           => $this->normalize_scalar( $status ),
				'location'         => $this->normalize_scalar( $location ),
				'home_team_name'   => $this->normalize_scalar( $home_team_name ),
				'away_team_name'   => $this->normalize_scalar( $away_team_name ),
				'home_team_id'     => $this->normalize_id_value( $home_team_id ),
				'away_team_id'     => $this->normalize_id_value( $away_team_id ),
				'home_club_id'     => $home_club_id,
				'away_club_id'     => $away_club_id,
				'home_logo_url'    => esc_url_raw( $home_logo_url ),
				'away_logo_url'    => esc_url_raw( $away_logo_url ),
				'is_home_match'    => $is_home_match,
				'raw'              => $row,
			);
		}

		/**
		 * Group matches by date.
		 *
		 * @param array $matches Normalized matches.
		 * @return array
		 */
		public function group_matches_by_date( $matches ) {
			$grouped = array();

			foreach ( $matches as $match ) {
				$date_key = ! empty( $match['date'] ) ? $match['date'] : 'unknown';

				if ( ! isset( $grouped[ $date_key ] ) ) {
					$grouped[ $date_key ] = array(
						'date'       => $date_key,
						'date_label' => $match['date_label'],
						'matches'    => array(),
					);
				}

				$grouped[ $date_key ]['matches'][] = $match;
			}

			return array_values( $grouped );
		}

		/**
		 * Build cache key.
		 *
		 * @return string
		 */
		private function get_cache_key() {
			$club_id    = $this->api->get_club_id();
			$days_ahead = $this->api->get_days_ahead();

			return self::CACHE_KEY . '_' . md5( $club_id . '|' . $days_ahead );
		}

		/**
		 * Extract first non-empty field from row.
		 *
		 * @param array $row Source row.
		 * @param array $keys Candidate keys.
		 * @return string
		 */
		private function extract_first_non_empty( $row, $keys ) {
			foreach ( $keys as $key ) {
				if ( isset( $row[ $key ] ) && '' !== trim( (string) $row[ $key ] ) ) {
					return (string) $row[ $key ];
				}
			}

			return '';
		}

		/**
		 * Normalize scalar string.
		 *
		 * @param mixed $value Source value.
		 * @return string
		 */
		private function normalize_scalar( $value ) {
			return is_scalar( $value ) ? trim( (string) $value ) : '';
		}

		/**
		 * Normalize id values to numeric string when possible.
		 *
		 * @param mixed $value Source value.
		 * @return string
		 */
		private function normalize_id_value( $value ) {
			$value = is_scalar( $value ) ? trim( (string) $value ) : '';
			$value = preg_replace( '/[^0-9]/', '', $value );

			return is_string( $value ) ? $value : '';
		}

		/**
		 * Extract and normalize date/time fields.
		 *
		 * @param array $row Raw row.
		 * @return array
		 */
		private function extract_datetime_data( $row ) {
			$date_candidates = array(
				'date',
				'match_date',
				'start_date',
				'game_date',
				'event_date',
			);

			$time_candidates = array(
				'time',
				'match_time',
				'start_time',
				'game_time',
			);

			$datetime_candidates = array(
				'datetime',
				'match_datetime',
				'start',
				'start_datetime',
				'game_datetime',
			);

			$date_raw     = $this->extract_first_non_empty( $row, $date_candidates );
			$time_raw     = $this->extract_first_non_empty( $row, $time_candidates );
			$datetime_raw = $this->extract_first_non_empty( $row, $datetime_candidates );
			$timestamp    = false;

			if ( ! empty( $datetime_raw ) ) {
				$timestamp = strtotime( $datetime_raw );
			}

			if ( false === $timestamp && ! empty( $date_raw ) ) {
				$combined  = trim( $date_raw . ' ' . $time_raw );
				$timestamp = strtotime( $combined );
			}

			if ( false === $timestamp && ! empty( $date_raw ) ) {
				$timestamp = strtotime( $date_raw );
			}

			if ( false !== $timestamp ) {
				return array(
					'date'          => wp_date( 'Y-m-d', $timestamp ),
					'date_label'    => wp_date( 'd.m.Y', $timestamp ),
					'time'          => wp_date( 'H:i', $timestamp ),
					'sort_datetime' => wp_date( 'Y-m-d H:i:s', $timestamp ),
				);
			}

			return array(
				'date'          => '',
				'date_label'    => '',
				'time'          => $this->normalize_scalar( $time_raw ),
				'sort_datetime' => '9999-12-31 23:59:59',
			);
		}
	}
}