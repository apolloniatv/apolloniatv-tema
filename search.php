<?php
/**
 * REZULTATELE CĂUTĂRII.
 *
 * Caută în titlurile și descrierile episoadelor (fără să conteze diacriticele). Afișează întâi
 * emisiunile al căror nume se potrivește, și după o parte din cuvânt
 * (ex. „muzica” găsește „Mirajul Muzicii”, „generali” găsește „Noaptea Generalilor”).
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$cautat   = get_search_query();
$emisiuni = $cautat ? aptv_categorii_potrivite( $cautat ) : array();
$gasite   = (int) $GLOBALS['wp_query']->found_posts;
?>
<main id="main" class="aptv-main">
	<div class="aptv-container aptv-sectiune">
		<?php aptv_unde_va_aflati( null, 'Căutare' ); ?>
		<h1 class="aptv-titlu-pagina aptv-titlu-banda">Rezultate pentru „<?php echo esc_html( $cautat ); ?>”</h1>
		<p class="aptv-cautare__numar">
			<?php
			if ( $gasite ) {
				echo esc_html( 'Am găsit ' . aptv_plural( $gasite, 'material', 'materiale' ) . ( $emisiuni ? ' și ' . aptv_plural( count( $emisiuni ), 'emisiune potrivită', 'emisiuni potrivite' ) : '' ) . '.' );
			} elseif ( $emisiuni ) {
				echo esc_html( 'Am găsit ' . aptv_plural( count( $emisiuni ), 'emisiune potrivită', 'emisiuni potrivite' ) . '.' );
			}
			?>
		</p>

		<?php if ( $emisiuni && ! is_wp_error( $emisiuni ) ) : ?>
			<h2 class="aptv-titlu-banda">Emisiuni și teme</h2>
			<div class="aptv-file">
				<?php foreach ( $emisiuni as $t ) : ?>
					<a href="<?php echo esc_url( get_term_link( $t ) ); ?>"><?php echo esc_html( $t->name ); ?></a>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( have_posts() ) : ?>
			<h2 class="aptv-titlu-banda">Episoade și știri</h2>
			<div class="aptv-grila aptv-grila--stiri">
				<?php
				while ( have_posts() ) :
					the_post();
					aptv_card( get_post(), 'lista' );
				endwhile;
				?>
			</div>
			<?php aptv_paginare( 'Mai multe rezultate', '← Înapoi', 'Mai multe rezultate →' ); ?>
		<?php else : ?>
			<p class="aptv-gol">Nu am găsit nimic. Încercați un alt cuvânt sau <a href="#contact">sunați-ne</a> și vă ajutăm.</p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
