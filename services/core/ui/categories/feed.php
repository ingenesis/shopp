<?php echo '<?xml version="1.0" encoding="utf-8"?>'; ?>
<rss version="2.0" <?php if (is_array($rss['xmlns'])) foreach ($rss['xmlns'] as $key => $value) echo 'xmlns:'.$key.'="'.$value.'" '; ?>>
<channel>
	<atom:link href="<?php echo esc_attr($rss['link']); ?>" rel="self" type="application/rss+xml" />
	<title><?php echo esc_html($rss['title']); ?></title>
	<description><?php echo esc_html($rss['description']); ?></description>
	<link><?php echo esc_html($rss['link']); ?></link>
	<language><?php echo get_option('rss_language'); ?></language>
	<copyright><?php echo esc_html("Copyright ".date('Y').", ".$rss['sitename']); ?></copyright>
	<?php while (false !== $item = ShoppCollection()->feed()): ?>
		<?php if( empty($item) ) continue; ?>
		<item>
			<?php ShoppCollection()->feeditem($item); ?>
		</item>
	<?php endwhile; ?>
</channel>
</rss>