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

if ( ! function_exists( 'taso_matchlist_format_date_label' ) ) {
	/**
	 * Format date label from Y-m-d if needed.
	 *
	 * @param string $date_raw Raw date.
	 * @return string
	 */
	function taso_matchlist_format_date_label( $date_raw ) {
		$date_raw = trim( (string) $date_raw );

		if ( '' === $date_raw ) {
			return '';
		}

		$timestamp = strtotime( $date_raw );
		if ( false === $timestamp ) {
			return $date_raw;
		}

		return gmdate( 'j.n.', $timestamp );
	}
}

if ( ! function_exists( 'taso_matchlist_get_group_date_label' ) ) {
	/**
	 * Resolve group date label safely.
	 *
	 * @param array $group Group data.
	 * @return string
	 */
	function taso_matchlist_get_group_date_label( $group ) {
		$date_label = taso_matchlist_pick_value(
			$group,
			array( 'date_label', 'label' )
		);

		if ( '' !== $date_label ) {
			return $date_label;
		}

		$date_raw = taso_matchlist_pick_value(
			$group,
			array( 'date' )
		);

		if ( '' !== $date_raw ) {
			return taso_matchlist_format_date_label( $date_raw );
		}

		if ( isset( $group['matches'] ) && is_array( $group['matches'] ) && ! empty( $group['matches'][0] ) && is_array( $group['matches'][0] ) ) {
			$match_date_label = taso_matchlist_pick_value(
				$group['matches'][0],
				array( 'date_label' )
			);

			if ( '' !== $match_date_label ) {
				return $match_date_label;
			}

			$match_date_raw = taso_matchlist_pick_value(
				$group['matches'][0],
				array( 'date' )
			);

			if ( '' !== $match_date_raw ) {
				return taso_matchlist_format_date_label( $match_date_raw );
			}
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
								$time = taso_matchlist_pick_value(
									$match,
									array( 'time', 'match_time', 'kickoff_time' )
								);

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
										'club_A_crest',
										'club_a_crest',
										'home_team_logo',
										'home_team_logo_url',
										'home_logo',
										'home_logo_url',
										'home_team_crest',
									)
								);

								$away_logo = taso_matchlist_pick_value(
									$match,
									array(
										'club_B_crest',
										'club_b_crest',
										'away_team_logo',
										'away_team_logo_url',
										'away_logo',
										'away_logo_url',
										'away_team_crest',
									)
								);

								$show_status = taso_matchlist_should_show_status( $status );
								?>
								<article class="taso-matchlist__item">
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
								</article>
							<?php endforeach; ?>
						</div>
					</section>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>
	</div>
</div>