<?php
/**
 * Call the init method when plugin is deactivated.
 *
 * @package tripzzy
 */

namespace Tripzzy\Core\Activation;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;
use Tripzzy\Core\Bases\PostTypeBase;
use Tripzzy\Core\Bases\TaxonomyBase;
use Tripzzy\Core\Helpers\MetaHelpers;
use Tripzzy\Core\Helpers\User;
use Tripzzy\Core\Helpers\Cron;

/**
 * Deactivator class.
 */
class Deactivator {

	/**
	 * Init Deactivator Hook.
	 *
	 * @since 1.0.0
	 */
	public static function init() {
		User::remove_roles();
		Cron::clear();
	}
}
