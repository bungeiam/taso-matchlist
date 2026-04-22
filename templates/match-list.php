<?php
/**
 * Frontend match list template.
 *
 * Available variables:
 * - array $groups Grouped match data.
 * - WP_Error|null $error Error object when fetching failed.
 *
 * @package Taso_Matchlist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'taso_matchlist_pick_value' ) ) {
	/**
	 * Pick first non-empty string value from candidate keys.
	 *
	 * @param array $source Source array.
	 * @param array $keys   Candidate keys.
	 * @return string
	 */
	function taso_matchlist_pick_value( $source, $keys ) {
		foreach ( $keys as $key ) {
			if ( isset( $source[ $key ] ) && '' !== trim( (string) $source[ $key ] ) ) {
				return (string) $source[ $key ];
			}
		}

		return '';
	}
}

if ( ! function_exists( 'taso_matchlist_format_raw_date' ) ) {
	/**
	 * Format raw date Y-m-d => d.m.Y
	 *
	 * @param string $date_raw Raw date string.
	 * @return string
	 */
	function taso_matchlist_format_raw_date( $date_raw ) {
		$date_raw = trim( (string) $date_raw );

		if ( '' === $date_raw ) {
			return '';
		}

		$dt = DateTime::createFromFormat( 'Y-m-d', $date_raw );
		if ( false === $dt ) {
			$timestamp = strtotime( $date_raw );
			if ( false === $timestamp ) {
				return $date_raw;
			}

			return wp_date( 'd.m.Y', $timestamp );
		}

		return $dt->format( 'd.m.Y' );
	}
}

if ( ! function_exists( 'taso_matchlist_format_raw_time' ) ) {
	/**
	 * Format raw time H:i:s => H:i
	 *
	 * @param string $time_raw Raw time string.
	 * @return string
	 */
	function taso_matchlist_format_raw_time( $time_raw ) {
		$time_raw = trim( (string) $time_raw );

		if ( '' === $time_raw ) {
			return '';
		}

		$dt = DateTime::createFromFormat( 'H:i:s', $time_raw );
		if ( false !== $dt ) {
			return $dt->format( 'H:i' );
		}

		$dt = DateTime::createFromFormat( 'H:i', $time_raw );
		if ( false !== $dt ) {
			return $dt->format( 'H:i' );
		}

		return $time_raw;
	}
}

if ( ! function_exists( 'taso_matchlist_get_group_date_label' ) ) {
	/**
	 * Resolve visible date label from first match raw.date.
	 *
	 * @param array $group Group data.
	 * @return string
	 */
	function taso_matchlist_get_group_date_label( $group ) {
		if ( ! isset( $group['matches'] ) || ! is_array( $group['matches'] ) || empty( $group['matches'] ) ) {
			return '';
		}

		$first_match = reset( $group['matches'] );

		if ( ! is_array( $first_match ) ) {
			return '';
		}

		if ( isset( $first_match['raw'] ) && is_array( $first_match['raw'] ) ) {
			$raw_date = taso_matchlist_pick_value( $first_match['raw'], array( 'date' ) );
			if ( '' !== $raw_date ) {
				return taso_matchlist_format_raw_date( $raw_date );
			}
		}

		$fallback_date = taso_matchlist_pick_value( $first_match, array( 'date' ) );
		if ( '' !== $fallback_date ) {
			return taso_matchlist_format_raw_date( $fallback_date );
		}

		return '';
	}
}

if ( ! function_exists( 'taso_matchlist_get_match_time' ) ) {
	/**
	 * Resolve visible time from raw.time.
	 *
	 * @param array $match Match data.
	 * @return string
	 */
	function taso_matchlist_get_match_time( $match ) {
		if ( isset( $match['raw'] ) && is_array( $match['raw'] ) ) {
			$raw_time = taso_matchlist_pick_value( $match['raw'], array( 'time' ) );
			if ( '' !== $raw_time ) {
				return taso_matchlist_format_raw_time( $raw_time );
			}
		}

		$fallback_time = taso_matchlist_pick_value(
			$match,
			array( 'time', 'match_time', 'kickoff_time' )
		);

		if ( '' !== $fallback_time ) {
			return taso_matchlist_format_raw_time( $fallback_time );
		}

		return '';
	}
}

if ( ! function_exists( 'taso_matchlist_should_show_status' ) ) {
	/**
	 * Decide whether match status should be shown.
	 *
	 * @param string $status Raw status.
	 * @return bool
	 */
	function taso_matchlist_should_show_status( $status ) {
		$status = strtolower( trim( $status ) );

		if ( '' === $status ) {
			return false;
		}

		$hidden_statuses = array(
			'fixture',
			'scheduled',
			'upcoming',
		);

		return ! in_array( $status, $hidden_statuses, true );
	}
}

if ( ! function_exists( 'taso_matchlist_status_class' ) ) {
	/**
	 * Build safe CSS class for status.
	 *
	 * @param string $status Raw status.
	 * @return string
	 */
	function taso_matchlist_status_class( $status ) {
		$status = strtolower( trim( $status ) );
		$status = str_replace( ' ', '-', $status );

		return sanitize_html_class( $status );
	}
}

if ( ! function_exists( 'taso_matchlist_get_match_link' ) ) {
	/**
	 * Build Palloliitto match link from match_id.
	 *
	 * @param array $match Match data.
	 * @return string
	 */
	function taso_matchlist_get_match_link( $match ) {
		$match_id = taso_matchlist_pick_value(
			$match,
			array( 'match_id', 'id', 'game_id' )
		);

		$match_id = preg_replace( '/[^0-9]/', '', $match_id );

		if ( empty( $match_id ) ) {
			return '';
		}

		return 'https://tulospalvelu.palloliitto.fi/match/' . rawurlencode( $match_id ) . '/lineups';
	}
}
?>
<div class="taso-matchlist" aria-live="polite">
	<div class="taso-matchlist__inner">
		<?php if ( $error instanceof WP_Error ) : ?>
			<div class="taso-matchlist__message taso-matchlist__message--error">
				<?php echo esc_html( $error->get_error_message() ); ?>
			</div>
		<?php elseif ( empty( $groups ) ) : ?>
			<div class="taso-matchlist__message">
				<?php echo esc_html__( 'Kotipelejä ei löytynyt valitulta aikaväliltä.', 'taso-matchlist' ); ?>
			</div>
		<?php else : ?>
			<div class="taso-matchlist__groups">
				<?php foreach ( $groups as $group ) : ?>
					<?php
					$date_label = taso_matchlist_get_group_date_label( $group );
					$matches    = array();

					if ( isset( $group['matches'] ) && is_array( $group['matches'] ) ) {
						$matches = $group['matches'];
					}
					?>
					<section class="taso-matchlist__group">
						<header class="taso-matchlist__group-header">
							<h3 class="taso-matchlist__group-title">
								<?php echo esc_html( '' !== $date_label ? $date_label : __( 'Päivämäärä puuttuu', 'taso-matchlist' ) ); ?>
							</h3>

							<span class="taso-matchlist__group-count">
								<?php echo esc_html( (string) count( $matches ) ); ?>
							</span>
						</header>

						<div class="taso-matchlist__items">
							<?php foreach ( $matches as $match ) : ?>
								<?php
								$time = taso_matchlist_get_match_time( $match );

								$series_name = taso_matchlist_pick_value(
									$match,
									array(
										'category_name',
										'series_name',
										'group_name',
										'group',
										'category',
										'sarja',
									)
								);

								$home_team_name = taso_matchlist_pick_value(
									$match,
									array(
										'home_team_name',
										'home_name',
										'team_home_name',
										'club_A_name',
										'club_a_name',
									)
								);

								$away_team_name = taso_matchlist_pick_value(
									$match,
									array(
										'away_team_name',
										'away_name',
										'team_away_name',
										'club_B_name',
										'club_b_name',
									)
								);

								$location = taso_matchlist_pick_value(
									$match,
									array( 'location', 'venue_name', 'venue' )
								);

								$status = taso_matchlist_pick_value(
									$match,
									array( 'status_label', 'status', 'match_status' )
								);

								$home_logo = taso_matchlist_pick_value(
									$match,
									array(
										'home_logo_url',
										'home_team_logo',
										'home_team_logo_url',
										'home_logo',
										'home_team_crest',
									)
								);

								$away_logo = taso_matchlist_pick_value(
									$match,
									array(
										'away_logo_url',
										'away_team_logo',
										'away_team_logo_url',
										'away_logo',
										'away_team_crest',
									)
								);

								$match_link  = taso_matchlist_get_match_link( $match );
								$show_status = taso_matchlist_should_show_status( $status );
								$item_label  = sprintf(
									/* translators: 1: home team, 2: away team, 3: time */
									__( 'Avaa ottelun %1$s – %2$s kokoonpanot. Otteluaika %3$s.', 'taso-matchlist' ),
									$home_team_name ? $home_team_name : __( 'Kotijoukkue', 'taso-matchlist' ),
									$away_team_name ? $away_team_name : __( 'Vierasjoukkue', 'taso-matchlist' ),
									$time ? $time : '--:--'
								);
								?>

								<?php if ( $match_link ) : ?>
									<a
										class="taso-matchlist__item taso-matchlist__item--link"
										href="<?php echo esc_url( $match_link ); ?>"
										target="_blank"
										rel="noopener noreferrer"
										aria-label="<?php echo esc_attr( $item_label ); ?>"
									>
								<?php else : ?>
									<article class="taso-matchlist__item">
								<?php endif; ?>

									<div class="taso-matchlist__series-col">
										<?php if ( $series_name ) : ?>
											<div class="taso-matchlist__series">
												<?php echo esc_html( $series_name ); ?>
											</div>
										<?php endif; ?>

										<?php if ( $location ) : ?>
											<div class="taso-matchlist__location">
												<?php echo esc_html( $location ); ?>
											</div>
										<?php endif; ?>

										<?php if ( $show_status ) : ?>
											<div class="taso-matchlist__status-wrap">
												<span class="taso-matchlist__status taso-matchlist__status--<?php echo esc_attr( taso_matchlist_status_class( $status ) ); ?>">
													<?php echo esc_html( $status ); ?>
												</span>
											</div>
										<?php endif; ?>
									</div>

									<div class="taso-matchlist__center-col">
										<div class="taso-matchlist__team taso-matchlist__team--home">
											<span class="taso-matchlist__team-name">
												<?php echo esc_html( $home_team_name ); ?>
											</span>

											<?php if ( $home_logo ) : ?>
												<img
													class="taso-matchlist__logo"
													src="<?php echo esc_url( $home_logo ); ?>"
													alt="<?php echo esc_attr( $home_team_name ); ?>"
													loading="lazy"
													decoding="async"
												/>
											<?php endif; ?>
										</div>

										<div class="taso-matchlist__time">
											<?php echo esc_html( $time ? $time : '--:--' ); ?>
										</div>

										<div class="taso-matchlist__team taso-matchlist__team--away">
											<?php if ( $away_logo ) : ?>
												<img
													class="taso-matchlist__logo"
													src="<?php echo esc_url( $away_logo ); ?>"
													alt="<?php echo esc_attr( $away_team_name ); ?>"
													loading="lazy"
													decoding="async"
												/>
											<?php endif; ?>

											<span class="taso-matchlist__team-name">
												<?php echo esc_html( $away_team_name ); ?>
											</span>
										</div>
									</div>

								<?php if ( $match_link ) : ?>
									</a>
								<?php else : ?>
									</article>
								<?php endif; ?>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>