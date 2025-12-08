<?php
/**
 * The template for displaying all content of archive trip. $args var is passed via Template::get_template_part method.
 *
 * @link https://developer.wordpress.org/themes/basics/template-hierarchy/#single-post
 *
 * @package tripzzy
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Template;
Template::get_template( 'layouts/default/layout-archive-tripzzy', $args ?? array() );
