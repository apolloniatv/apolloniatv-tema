<?php
/**
 * PAGINA UNUI EPISOD (sau a unei știri).
 *
 * De sus în jos:
 *   - „Unde vă aflați”: Acasă › Emisiuni › Cultură › Stop cadru › Episodul 1
 *   - Playerul (se încarcă la apăsarea pe „Pornește”)
 *   - Emisiunea, numărul episodului, data, durata, titlul și descrierea
 *   - Butoane mari: „Episodul următor”, „Episodul anterior”, „Toate episoadele”
 *   - Un sfat pentru ecran complet și sunet
 *   - În dreapta (pe telefon: dedesubt): „Urmează în <emisiune>”
 *
 * Pentru știri nu se afișează numărul episodului, iar în dreapta apar „Alte știri”.
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

while ( have_posts() ) :
	the_post();
	$post_curent = get_post();
	$categorie   = aptv_categoria_episodului();
	$tip         = $categorie ? aptv_tip_categorie( $categorie ) : '';
	$e_emisiune  = 'emisiune' === $tip;
	$numar       = $e_emisiune ? aptv_numar_episod( $post_curent, $categorie ) : 0;
	$video       = aptv_video_id();
	$scurt       = (bool) get_post_meta( get_the_ID(), '_aptv_scurt', true );
	$durata      = aptv_durata_text();
	$urmatoare   = $e_emisiune ? aptv_episoade_urmatoare( $post_curent, $categorie, 3 ) : array();
	$anterior    = $e_emisiune ? aptv_episod_anterior( $post_curent, $categorie ) : null;
	$laterale    = $e_emisiune ? $urmatoare : ( $categorie ? aptv_cele_mai_noi( $categorie->term_id, 4, array( get_the_ID() ) ) : array() );

	// Conținutul articolului; dacă playerul e deja sus, nu-l mai repetăm în text.
	$continut = apply_filters( 'the_content', get_the_content() );
	?>
	<main id="main" class="aptv-main">
		<div class="aptv-container aptv-sectiune">
			<?php aptv_unde_va_aflati( $categorie, $e_emisiune ? 'Episodul ' . $numar : get_the_title() ); ?>

			<div class="aptv-episod-pagina">
				<article class="aptv-episod-pagina__principal">
					<?php
					if ( $video && false === strpos( $continut, 'aptv-player' ) ) {
						echo aptv_player( $video, get_the_title(), $scurt ); // phpcs:ignore -- HTML escapat în aptv_player().
					}
					?>

					<div class="aptv-etichete aptv-episod-pagina__etichete">
						<?php echo aptv_eticheta( $categorie ); // phpcs:ignore -- escapat în funcție. ?>
						<?php if ( $e_emisiune && aptv_e_arhiva() ) : ?><span class="aptv-eticheta aptv-eticheta--arhiva">ARHIVĂ</span><?php endif; ?>
						<?php if ( aptv_e_nou() ) : ?><span class="aptv-eticheta aptv-eticheta--nou">NOU</span><?php endif; ?>
						<span class="aptv-episod-pagina__meta">
							<?php
							$bucati = array();
							if ( $numar ) {
								$bucati[] = 'Episodul ' . (int) $numar;
							}
							$bucati[] = esc_html( get_the_date( 'j F Y' ) );
							if ( $durata ) {
								$bucati[] = esc_html( $durata );
							}
							echo implode( ' · ', $bucati ); // phpcs:ignore -- elementele sunt escapate mai sus.
							?>
						</span>
					</div>
					<h1 class="aptv-titlu-banda"><?php the_title(); ?></h1>

					<?php if ( trim( wp_strip_all_tags( $continut ) ) || false !== strpos( $continut, 'aptv-player' ) ) : ?>
						<div class="aptv-episod-pagina__descriere entry-content"><?php echo $continut; // phpcs:ignore -- conținut WordPress filtrat. ?></div>
					<?php endif; ?>

					<?php if ( $e_emisiune ) : ?>
						<?php if ( $urmatoare ) : ?>
							<div class="aptv-urmeaza">
								<span class="aptv-eticheta aptv-eticheta--urmeaza">URMEAZĂ</span>
								<a class="aptv-urmeaza__link" href="<?php echo esc_url( get_permalink( $urmatoare[0] ) ); ?>">
									<span>
										<span class="aptv-urmeaza__supra">Episodul <?php echo (int) ( $numar + 1 ); ?> · <?php echo esc_html( get_the_date( 'j F Y', $urmatoare[0] ) ); ?></span>
										<span class="aptv-urmeaza__titlu"><?php echo esc_html( get_the_title( $urmatoare[0] ) ); ?></span>
									</span>
									<span class="aptv-buton">Episodul următor ›</span>
								</a>
							</div>
						<?php else : ?>
							<p class="aptv-gol">Acesta este cel mai nou episod al emisiunii.</p>
						<?php endif; ?>
						<nav class="aptv-butoane aptv-episod-pagina__nav" aria-label="Alte episoade">
							<?php if ( $anterior ) : ?>
								<a class="aptv-buton aptv-buton--contur" href="<?php echo esc_url( get_permalink( $anterior ) ); ?>">‹ Episodul anterior</a>
							<?php endif; ?>
							<a class="aptv-buton aptv-buton--contur" href="<?php echo esc_url( get_term_link( $categorie ) ); ?>"><?php echo $categorie->count > 1 ? 'Toate cele ' . esc_html( aptv_plural( (int) $categorie->count, 'episod', 'episoade' ) ) . ' ' . esc_html( $categorie->name ) : 'Pagina emisiunii ' . esc_html( $categorie->name ); ?></a>
						</nav>
					<?php elseif ( $categorie ) : ?>
						<nav class="aptv-butoane aptv-episod-pagina__nav" aria-label="Alte știri">
							<a class="aptv-buton aptv-buton--contur" href="<?php echo esc_url( get_term_link( $categorie ) ); ?>">‹ Toate știrile <?php echo esc_html( mb_strtolower( $categorie->name ) ); ?></a>
						</nav>
					<?php endif; ?>

					<?php if ( $video ) : ?>
						<div class="aptv-sfat">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v6M12 7.5v.5"/></svg>
							<p><strong>Sfat:</strong> pentru imagine pe tot ecranul, apăsați pe pătrățelul din colțul din dreapta-jos al filmului. Pentru sunet mai tare, folosiți butoanele de volum ale dispozitivului.</p>
						</div>
					<?php endif; ?>
				</article>

				<?php if ( $laterale ) : ?>
					<aside class="aptv-episod-pagina__lateral" aria-labelledby="aptv-urmeaza">
						<h2 id="aptv-urmeaza" class="aptv-titlu-banda"><?php echo $e_emisiune ? 'Mai departe în ' . esc_html( $categorie->name ) : 'Alte știri'; ?></h2>
						<div class="aptv-lista-mica">
							<?php foreach ( $laterale as $p ) : ?>
								<?php aptv_card( $p, 'lista' ); ?>
							<?php endforeach; ?>
						</div>
						<?php if ( $categorie ) : ?>
							<a class="aptv-lista-mica__toate" href="<?php echo esc_url( get_term_link( $categorie ) ); ?>"><?php echo $e_emisiune ? ( $categorie->count > 1 ? 'Toate cele ' . esc_html( aptv_plural( (int) $categorie->count, 'episod', 'episoade' ) ) . ' →' : 'Vezi emisiunea →' ) : 'Toate știrile →'; ?></a>
						<?php endif; ?>
					</aside>
				<?php endif; ?>
			</div>
		</div>
	</main>
	<?php
endwhile;

get_footer();
