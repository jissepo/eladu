<?php
/**
 * The template to show mobile menu
 *
 * @package WordPress
 * @subpackage CLOUDME
 * @since CLOUDME 1.0
 */
?>
<div class="mobile-sticky">
<div>
    <a class="icon-phone" href="tel:<?php the_field('settings_telefon', 'options'); ?>"><?php the_field('settings_telefon', 'options'); ?></a>
</div>
<div>
<a href="<?php get_home_url(); ?>#broneerimine" class="mobile-sticky-btn"><?php echo esc_attr_e('Broneeri ladu', THEME_TEXT_DOMAIN); ?></a>
</div>

</div>
<div class="menu_mobile_overlay"></div>
<div class="menu_mobile menu_mobile_<?php echo esc_attr(cloudme_get_theme_option('menu_mobile_fullscreen') > 0 ? 'fullscreen' : 'narrow'); ?> scheme_dark">
	<div class="menu_mobile_inner">
		<a class="menu_mobile_close icon-cancel"></a><?php

		// Logo
		set_query_var('cloudme_logo_args', array('type' => 'mobile'));
		get_template_part( 'templates/header-logo' );
		set_query_var('cloudme_logo_args', array());

		// Mobile menu
		$cloudme_menu_mobile = cloudme_get_nav_menu('menu_mobile');
		if (empty($cloudme_menu_mobile)) {
			$cloudme_menu_mobile = apply_filters('cloudme_filter_get_mobile_menu', '');
			if (empty($cloudme_menu_mobile)) $cloudme_menu_mobile = cloudme_get_nav_menu('menu_main');
			if (empty($cloudme_menu_mobile)) $cloudme_menu_mobile = cloudme_get_nav_menu();
		}
		if (!empty($cloudme_menu_mobile)) {
			if (!empty($cloudme_menu_mobile))
				$cloudme_menu_mobile = str_replace(
					array('menu_main', 'id="menu-', 'sc_layouts_menu_nav', 'sc_layouts_hide_on_mobile', 'hide_on_mobile'),
					array('menu_mobile', 'id="menu_mobile-', '', '', ''),
					$cloudme_menu_mobile
					);
			if (strpos($cloudme_menu_mobile, '<nav ')===false)
				$cloudme_menu_mobile = sprintf('<nav class="menu_mobile_nav_area">%s</nav>', $cloudme_menu_mobile);
			cloudme_show_layout(apply_filters('cloudme_filter_menu_mobile_layout', $cloudme_menu_mobile));
		}

		// Search field
		do_action('cloudme_action_search', 'normal', 'search_mobile', false);
		
		// Social icons
		cloudme_show_layout(cloudme_get_socials_links(), '<div class="socials_mobile">', '</div>');
		?>
	</div>
</div>
