<div class="wrap about-wrap">

	<?php $this->heading(); ?>

	<p class="about-description"><?php Shopp::_e( 'Shopp is crafted by a talented team of professionals practicing the art and engineering of e-commerce.'); ?></p>

	<?php $contributors = $this->contributors(); ?>

	<?php if ( ! empty($contributors) ): ?>
	<ul class="wp-people-group">
		<?php foreach( $contributors as $contributor ): ?>
		<li class="wp-person">
			<a href="<?php echo esc_url($contributor->link); ?>"><img src="<?php echo esc_url($contributor->avatar_url); ?>" width="64" height="64" class="gravatar" alt="<?php echo esc_html($contributor->login); ?>"/></a>
			<a class="web" href="<?php echo esc_url($contributor->link); ?>"><?php echo $contributor->name; ?></a>
			<span class="title"><?php echo $contributor->company; ?></a></span>
		</li>
		<?php endforeach; ?>
	</ul>
	<?php endif; ?>

</div>