<?php
/**
 * REZULTATELE CĂUTĂRII.
 *
 * Caută în titlurile și descrierile episoadelor. Afișează și emisiunile al căror
 * nume se potrivește (ex. căutarea „generali” găsește emisiunea „Noaptea Generalilor”).
 *
 * @package apolloniatv
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$cautat   = get_search_query();
$emisiuni = $cautat ? get_terms(
	array(
		'taxonomy'   => 'category',
		'hide_empty' => false,
		'search'     => $cautat,
		'number'     => 6,
	)
) : array();
?>
<main id="main" class="aptv-main">
	<div class="aptv-container aptv-sectiune">
		<?php aptv_unde_va_aflati( null, 'Căutare' ); ?>
		<h1 class="aptv-titlu-pagina aptv-titlu-banda">Rezultate pentru „<?php echo esc_html( $cautat ); ?>”</h1>

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
			<?php the_posts_pagination( array( 'prev_text' => '← Înapoi', 'next_text' => 'Mai multe rezultate →' ) ); ?>
		<?php else : ?>
			<p class="aptv-gol">Nu am găsit nimic. Încercați un alt cuvânt sau <a href="#contact">sunați-ne</a> și vă ajutăm.</p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
