<?php
/**
 * Plugin Name: USEN Migrator
 * Plugin URI:  https://github.com/INN/umbrella-usenergynews
 * Description: Run-once plugin for handling migration of US Energy News content
 * Version:     0.1.0
 * Author:      innlabs
 * Author URI:  https://labs.inn.org
 * Donate link: https://labs.inn.org/donate
 * License:     GPLv2
 * Text Domain: usen-migrator
 * Domain Path: /languages
 *
 * @link    https://labs.inn.org
 *
 * @package USEN_Migrator
 * @version 0.1.0
 *
 * Built using generator-plugin-wp (https://github.com/WebDevStudios/generator-plugin-wp)
 */

/**
 * Copyright (c) 2018 innlabs (email : labs@inn.org)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2 or, at
 * your discretion, any later version, as published by the Free
 * Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */


// Include additional php files here.
// require 'includes/something.php';

/**
 * Main initiation class.
 *
 * @since  0.1.0
 */
final class USEN_Migrator {

	/**
	 * Current version.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	const VERSION = '0.1.0';

	/**
	 * URL of plugin directory.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	protected $url = '';

	/**
	 * Path of plugin directory.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	protected $path = '';

	/**
	 * Plugin basename.
	 *
	 * @var    string
	 * @since  0.1.0
	 */
	protected $basename = '';

	/**
	 * Singleton instance of plugin.
	 *
	 * @var    USEN_Migrator
	 * @since  0.1.0
	 */
	protected static $single_instance = null;

	/**
	 * Creates or returns an instance of this class.
	 *
	 * @since   0.1.0
	 * @return  USEN_Migrator A single instance of this class.
	 */
	public static function get_instance() {
		if ( null === self::$single_instance ) {
			self::$single_instance = new self();
		}

		return self::$single_instance;
	}

	/**
	 * Sets up our plugin.
	 *
	 * @since  0.1.0
	 */
	protected function __construct() {
		$this->basename = plugin_basename( __FILE__ );
		$this->url      = plugin_dir_url( __FILE__ );
		$this->path     = plugin_dir_path( __FILE__ );

	}

	/**
	 * Add hooks and filters.
	 * Priority needs to be
	 * < 10 for CPT_Core,
	 * < 5 for Taxonomy_Core,
	 * and 0 for Widgets because widgets_init runs at init priority 1.
	 *
	 * @since  0.1.0
	 */
	public function hooks() {
		add_action( 'init', array( $this, 'init' ), 0 );
	}

	/**
	 * Init hooks
	 *
	 * @since  0.1.0
	 */
	public function init() {
		// ONLY activate this functionality if we're running WP CLI
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			require_once $this->path . '/includes/class-usen-migrator-cli.php';
			WP_CLI::add_command( 'usen', 'CR_WP_CLI' );
		}
	}

	/**
	 * Magic getter for our object.
	 *
	 * @since  0.1.0
	 *
	 * @param  string $field Field to get.
	 * @throws Exception     Throws an exception if the field is invalid.
	 * @return mixed         Value of the field.
	 */
	public function __get( $field ) {
		switch ( $field ) {
			case 'version':
				return self::VERSION;
			case 'basename':
			case 'url':
			case 'path':
				return $this->$field;
			default:
				throw new Exception( 'Invalid ' . __CLASS__ . ' property: ' . $field );
		}
	}
}

/**
 * Grab the USEN_Migrator object and return it.
 * Wrapper for USEN_Migrator::get_instance().
 *
 * @since  0.1.0
 * @return USEN_Migrator  Singleton instance of plugin class.
 */
function usen_migrator() {
	return USEN_Migrator::get_instance();
}

// Kick it off.
add_action( 'plugins_loaded', array( usen_migrator(), 'hooks' ) );
