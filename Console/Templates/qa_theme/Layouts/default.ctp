<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo Configure::read('Variable.language.code_2'); ?>" lang="<?php echo Configure::read('Variable.language.code_2'); ?>" version="XHTML+RDFa 1.0" dir="<?php echo Configure::read('Variable.language.direction'); ?>">
	<head>
		<title><?php echo $this->Layout->title(); ?></title>
		<?php echo $this->Layout->meta(); ?>
		<?php echo $this->Layout->stylesheets(); ?>
		<?php echo $this->Layout->javascripts(); ?>
		<?php echo $this->Layout->header(); ?>
	</head>

	<body>
		<div id="frame">
			<?php echo $this->Html->link($this->Html->image('logo.png', array('class' => 'site-logo')), '/', array('escape' => false)); ?>

			<?php echo $this->Layout->content(); ?>

			<div class="blocks-container">
				<!-- REGIONS -->
			</div>

			<?php
				if ($Layout['feed']) {
					echo $this->Html->link(
						$this->Html->image('rss.png'),
						$Layout['feed'],
						array(
							'class' => 'rss-feed-icon',
							'escape' => false
						)
					);
				}
			?>
		</div>

		<?php echo $this->Layout->footer(); ?>
	</body>
</html>