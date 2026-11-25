<?php
/**
 * ȘABLON: pagina „În direct” (www.apolloniatv.ro/in-direct/).
 *
 * De sus în jos: titlul, playerul transmisiunii (pornește la „Pornește”), „Acum” și „Urmează”,
 * apoi programul săptămânii, cu ziua de azi deschisă și programul curent evidențiat.
 * Datele vin din Apollonia TV → În direct (inc/live.php).
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$activ               = aptv_live_activ();
list( $acum, $urm )  = aptv_acum_si_urmeaza();
$grila               = aptv_grila();
$azi                 = (int) current_datetime()->format( 'N' );
$link_youtube        = 'https://www.youtube.com/@apolloniatvcultural/live';
?>
<main id="main" class="aptv-main">
	<div class="aptv-container aptv-sectiune">
		<?php aptv_unde_va_aflati( null, 'În direct' ); ?>
		<h1 class="aptv-titlu-pagina aptv-titlu-banda"><span class="aptv-live-punct" aria-hidden="true"></span> Apollonia TV în direct</h1>

		<?php if ( $activ ) : ?>
			<p class="aptv-live-intro">Transmitem 24 de ore din 24. Apăsați „Pornește” ca să urmăriți ce rulează acum.</p>
			<div class="aptv-live">
				<div class="aptv-player aptv-player--live" data-src="<?php echo esc_url( aptv_live_embed() ); ?>" data-titlu="Apollonia TV în direct">
					<span class="aptv-player__fundal" aria-hidden="true">
						<span class="aptv-live-eticheta"><span class="aptv-live-punct"></span> ÎN DIRECT</span>
						<?php if ( $acum ) : ?>
							<span class="aptv-player__acum"><?php echo esc_html( $acum['titlu'] ); ?></span>
						<?php endif; ?>
					</span>
					<button type="button" class="aptv-player__buton"><?php echo aptv_icon_play(); // phpcs:ignore ?> Pornește</button>
				</div>
				<p class="aptv-player__rezerva">Nu pornește? <a href="<?php echo esc_url( $link_youtube ); ?>" target="_blank" rel="noopener">Urmăriți transmisiunea pe YouTube</a>.</p>

				<?php if ( $acum ) : ?>
					<div class="aptv-acum-urmeaza">
						<div class="aptv-acum">
							<span class="aptv-eticheta aptv-eticheta--live">ACUM</span>
							<p class="aptv-acum__titlu"><?php echo aptv_program_html( $acum ); // phpcs:ignore -- escapat. ?></p>
							<p class="aptv-mic">de la ora <?php echo esc_html( $acum['ora'] ); ?></p>
						</div>
						<div class="aptv-acum">
							<span class="aptv-eticheta">URMEAZĂ</span>
							<p class="aptv-acum__titlu"><?php echo aptv_program_html( $urm ); // phpcs:ignore -- escapat. ?></p>
							<p class="aptv-mic"><?php echo esc_html( ( (int) $urm['zi'] === $azi ? 'azi' : mb_strtolower( aptv_zile()[ (int) $urm['zi'] ] ) ) . ', ora ' . $urm['ora'] ); ?></p>
						</div>
					</div>
				<?php endif; ?>
			</div>
		<?php else : ?>
			<div class="aptv-live-curand">
				<p><strong>Transmisiunea în direct începe în curând.</strong></p>
				<p>Până atunci, găsiți emisiunile noastre pe site, la <a href="<?php echo esc_url( home_url( '/category/emisiuni/' ) ); ?>">Emisiuni</a>, și pe <a href="https://www.youtube.com/@apolloniatvcultural" target="_blank" rel="noopener">canalul YouTube</a>.</p>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $grila ) : ?>
		<section class="aptv-container aptv-sectiune" aria-labelledby="aptv-program">
			<h2 id="aptv-program" class="aptv-titlu-banda">Programul săptămânii</h2>
			<div class="aptv-zile" role="tablist" aria-label="Alegeți ziua">
				<?php foreach ( aptv_zile() as $n => $zi ) : ?>
					<button type="button" role="tab" class="aptv-buton aptv-buton--contur aptv-zi" id="aptv-tab-<?php echo (int) $n; ?>" aria-controls="aptv-zi-<?php echo (int) $n; ?>" aria-selected="<?php echo $n === $azi ? 'true' : 'false'; ?>"><?php echo esc_html( $n === $azi ? 'Azi' : $zi ); ?></button>
				<?php endforeach; ?>
			</div>
			<?php foreach ( aptv_zile() as $n => $zi ) : ?>
				<?php
				$ale_zilei = array_filter(
					$grila,
					function ( $r ) use ( $n ) {
						return (int) $r['zi'] === $n;
					}
				);
				?>
				<div class="aptv-program" id="aptv-zi-<?php echo (int) $n; ?>" role="tabpanel" aria-labelledby="aptv-tab-<?php echo (int) $n; ?>" data-zi="<?php echo (int) $n; ?>">
					<h3 class="aptv-program__zi"><?php echo esc_html( $zi . ( $n === $azi ? ' (azi)' : '' ) ); ?></h3>
					<?php if ( $ale_zilei ) : ?>
						<ol class="aptv-program__lista">
							<?php foreach ( $ale_zilei as $r ) : ?>
								<?php $e_acum = $activ && $acum && $r === $acum; ?>
								<li class="aptv-program__rand<?php echo $e_acum ? ' is-acum' : ''; ?>">
									<span class="aptv-program__ora"><?php echo esc_html( $r['ora'] ); ?></span>
									<span class="aptv-program__titlu"><?php echo aptv_program_html( $r ); // phpcs:ignore -- escapat. ?></span>
									<?php if ( $e_acum ) : ?><span class="aptv-eticheta aptv-eticheta--live">ACUM</span><?php endif; ?>
								</li>
							<?php endforeach; ?>
						</ol>
					<?php else : ?>
						<p class="aptv-gol">Programul acestei zile nu e încă publicat.</p>
					<?php endif; ?>
				</div>
			<?php endforeach; ?>
		</section>
	<?php elseif ( $activ ) : ?>
		<section class="aptv-container aptv-sectiune">
			<p class="aptv-gol">Programul săptămânii va fi publicat aici în curând.</p>
		</section>
	<?php endif; ?>
</main>
<?php
get_footer();
