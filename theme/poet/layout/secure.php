<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The secure layout.
 *
 * @package   theme_poet
 * @copyright 2013 Moodle, moodle.org
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

echo $OUTPUT->doctype() ?>
<html <?php echo $OUTPUT->htmlattributes(); ?>>
<head>
    <title><?php echo $OUTPUT->page_title(); ?></title>
    <link rel="shortcut icon" href="<?php echo $OUTPUT->favicon(); ?>" />
    <?php echo $OUTPUT->standard_head_html() ?>
    <link href='//fonts.googleapis.com/css?family=Oswald:400,700' rel='stylesheet' type='text/css'>
    <link href='//fonts.googleapis.com/css?family=Open+Sans:300,400,700,800' rel='stylesheet' type='text/css'>
    <link href="//maxcdn.bootstrapcdn.com/font-awesome/4.3.0/css/font-awesome.min.css" rel="stylesheet">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body <?php echo $OUTPUT->body_attributes('custom-theme'); ?>>

<?php echo $OUTPUT->standard_top_of_body_html() ?>

<header role="banner" class="navbar navbar-fixed-top moodle-has-zindex">
	<div id="header-wrap">
		<div class="container-fluid">
			<div id="logo">
				<a href="<?php echo $CFG->wwwroot;?>"><img src="<?php echo $OUTPUT->pix_url('logo', 'theme'); ?>" alt="Safe Kids Logo" /></a>
			</div>
			<nav role="navigation" class="navbar-inner">
				<a class="btn btn-navbar" data-toggle="workaround-collapse" data-target=".nav-collapse">
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</a>
				<div class="nav-collapse collapse">
					<?php echo $OUTPUT->login_info() ?>
					<?php echo $OUTPUT->custom_menu(); ?>
				</div>
			</nav>
		</div>
	</div>
</header>

<section id="page-wrap">
	<div id="page" class="container-fluid">

		<header id="page-header" class="clearfix">
		</header>

		<div id="page-content" class="row-fluid">
			<div id="region-bs-main-and-pre" class="span9">
				<div class="row-fluid">
					<section id="region-main" class="span8 pull-right">
						<?php echo $OUTPUT->main_content(); ?>
					</section>
					<?php echo $OUTPUT->blocks('side-pre', 'span4 desktop-first-column'); ?>
				</div>
			</div>
			<?php echo $OUTPUT->blocks('side-post', 'span3'); ?>
		</div>

		<?php echo $OUTPUT->standard_end_of_body_html() ?>

	</div>
</section>

<footer id="page-footer">
	<div id="footer-content" class="container">
		<div class="row-fluid">
			<?php echo $OUTPUT->blocks('footer-one', 'span4'); ?>
			<?php echo $OUTPUT->blocks('footer-two', 'span4'); ?>
			<?php echo $OUTPUT->blocks('footer-three', 'span4'); ?>
		</div>
		<div class="row-fluid">
			<?php echo $OUTPUT->blocks('footer-four', 'span12'); ?>
		</div>
	</div>
</footer>

</body>
</html>