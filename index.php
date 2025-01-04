<?php
/**
 * The main template file
 *
 *
 * @package crud
 */

get_header();
?>

	<main id="primary" class="site-main">

		<?php
			if ( have_posts() ) :
		?>
		<header>
			<h1 class="page-title"><?php single_post_title(); ?></h1>
		</header>
		<?php

		while ( have_posts() ) : the_post();

			the_content();
			
		endwhile;


		endif;
		?>

	</main>

<?php
get_footer();
