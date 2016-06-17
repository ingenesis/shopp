<ul class="subsubsub">
	<?php
		$counts = $this->status_counts();
		foreach((array)$counts as $id => $state):
			if ('' === $id) $id = null;
			$status = isset($_GET['status']) && array_key_exists((int)$_GET['status'],$counts)?(int)$_GET['status']:null;
			$args = array('status'=> $id, 'id'=>null);
			$url = add_query_arg(array_merge($_GET,$args),admin_url('admin.php'));
			$classes = $status === $id?' class="current"':'';
			$separator = is_null($id)?'':'| ';
	?>
		<li><?php echo $separator; ?><a href="<?php echo esc_url($url); ?>"<?php echo $classes; ?>><?php esc_html_e($state->label); ?></a> <span class="count">(<?php esc_html_e($state->total); ?>)</span></li>
	<?php endforeach; ?>
</ul>