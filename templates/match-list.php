<?php
/**
 * Frontend match list template.
 *
 * Available variables:
 * - array         $groups Grouped match data.
 * - WP_Error|null $error  Error object when fetching failed.
 *
 * @package Taso_Matchlist
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="taso-matchlist" aria-live="polite">
	<div class="taso-matchlist__inner">
		<div class="taso-matchlist__header">
			<div>
				<p class="taso-matchlist__eyebrow"><?php echo esc_html__( 'Union Plaani', 'taso-matchlist' ); ?></p>
				<h2 class="taso-matchlist__title"><?php echo esc_html__( 'Kotipelit', 'taso-matchlist' ); ?></h2>
			</div>
			<p class="taso-matchlist__subtitle"><?php echo esc_html__( 'Seuraavat TASOsta haetut kotiottelut.', 'taso-matchlist' ); ?></p>
		</div>

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
					$date_label = '';

					if ( isset( $group['date_label'] ) && is_string( $group['date_label'] ) ) {
						$date_label = $group['date_label'];
					}

					$matches = array();

					if ( isset( $group['matches'] ) && is_array( $group['matches'] ) ) {
						$matches = $group['matches'];
					}
					?>
					<section class="taso-matchlist__group">
						<header class="taso-matchlist__group-header">
							<h3 class="taso-matchlist__group-title"><?php echo esc_html( $date_label ? $date_label : __( 'Päivämäärä puuttuu', 'taso-matchlist' ) ); ?></h3>
						</header>

						<div class="taso-matchlist__items">
							<?php foreach ( $matches as $match ) : ?>
								<?php
								$time             = isset( $match['time'] ) ? (string) $match['time'] : '';
								$competition_name = isset( $match['competition_name'] ) ? (string) $match['competition_name'] : '';
								$home_team_name   = isset( $match['home_team_name'] ) ? (string) $match['home_team_name'] : '';
								$away_team_name   = isset( $match['away_team_name'] ) ? (string) $match['away_team_name'] : '';
								$location         = isset( $match['location'] ) ? (string) $match['location'] : '';
								$status           = isset( $match['status'] ) ? (string) $match['status'] : '';
								$status_class     = $status ? ' taso-matchlist__status--visible' : '';
								?>
								<article class="taso-matchlist__item">
									<div class="taso-matchlist__time-wrap">
										<div class="taso-matchlist__time"><?php echo esc_html( $time ? $time : '--:--' ); ?></div>
										<?php if ( $status ) : ?>
											<div class="taso-matchlist__status<?php echo esc_attr( $status_class ); ?>"><?php echo esc_html( $status ); ?></div>
										<?php endif; ?>
									</div>

									<div class="taso-matchlist__content">
										<?php if ( $competition_name ) : ?>
											<div class="taso-matchlist__competition"><?php echo esc_html( $competition_name ); ?></div>
										<?php endif; ?>

										<div class="taso-matchlist__teams">
											<span class="taso-matchlist__team taso-matchlist__team--home"><?php echo esc_html( $home_team_name ); ?></span>
											<span class="taso-matchlist__vs">–</span>
											<span class="taso-matchlist__team taso-matchlist__team--away"><?php echo esc_html( $away_team_name ); ?></span>
										</div>

										<?php if ( $location ) : ?>
											<div class="taso-matchlist__location"><?php echo esc_html( $location ); ?></div>
										<?php endif; ?>
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