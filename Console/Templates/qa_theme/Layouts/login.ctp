<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo Configure::read('Variable.language.code_2'); ?>" lang="<?php echo Configure::read('Variable.language.code'); ?>" version="XHTML+RDFa 1.0" dir="<?php echo Configure::read('Variable.language.direction'); ?>">
	<head>
		<title><?php echo $this->Layout->title(); ?></title>
		<?php echo $this->Layout->meta(); ?>
		<?php echo $this->Layout->stylesheets(); ?>
		<?php echo $this->Layout->javascripts(); ?>
		<?php echo $this->Layout->header(); ?>
	</head>

	<body id="login">
		<div id="frame">
			<?php echo $this->Layout->content(); ?>
		</div>
	</body>
</html>