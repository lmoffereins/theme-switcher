<?php

/**
 * The Theme Switcher Plugin
 * 
 * @package Theme Switcher
 * @subpackage Main
 */

/**
 * Plugin Name:       Theme Switcher
 * Description:       Enable theme switching for admin users. Based on solid work in the <a href="https://github.com/nash-ye/WP-Conditional-Themes">Conditional Themes</a> plugin.
 * Plugin URI:        https://github.com/lmoffereins/theme-switcher/
 * Version:           1.0.1
 * Author:            Laurens Offereins
 * Author URI:        https://github.com/lmoffereins/
 * Text Domain:       theme-switcher
 * Domain Path:       /languages/
 * GitHub Plugin URI: lmoffereins/theme-switcher
 */

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Theme_Switcher' ) ) :
/**
 * The main plugin class
 *
 * @since 1.0.0
 */
final class Theme_Switcher {

	/**
	 * Setup and return the singleton pattern
	 *
	 * @since 1.0.0
	 *
	 * @uses Theme_Switcher::setup_globals()
	 * @uses Theme_Switcher::setup_actions()
	 * @return The single Theme_Switcher
	 */
	public static function instance() {

		// Store instance locally
		static $instance = null;

		if ( null === $instance ) {
			$instance = new Theme_Switcher;
			$instance->setup_globals();
			$instance->includes();
			$instance->setup_actions();
		}

		return $instance;
	}

	/**
	 * Prevent the plugin class from being loaded more than once
	 */
	private function __construct() { /* Nothing to do */ }

	/** Private methods *************************************************/

	/**
	 * Setup default class globals
	 *
	 * @since 1.0.0
	 */
	private function setup_globals() {

		/** Versions **********************************************************/

		$this->version      = '1.0.1';

		/** Paths *************************************************************/

		// Setup some base path and URL information
		$this->file         = __FILE__;
		$this->basename     = plugin_basename( $this->file );
		$this->plugin_dir   = plugin_dir_path( $this->file );
		$this->plugin_url   = plugin_dir_url ( $this->file );

		// Includes
		$this->includes_dir = trailingslashit( $this->plugin_dir . 'includes' );
		$this->includes_url = trailingslashit( $this->plugin_url . 'includes' );

		// Languages
		$this->lang_dir     = trailingslashit( $this->plugin_dir . 'languages' );

		/** Misc **************************************************************/

		$this->extend       = new stdClass();
		$this->domain       = 'theme-switcher';
	}

	/**
	 * Include required files
	 *
	 * @since 1.0.0
	 */
	private function includes() {

		// Load switcher classes
		require( $this->includes_dir . 'class-conditional-themes-switcher.php' );
		require( $this->includes_dir . 'class-conditional-themes-manager.php'  );
	}

	/**
	 * Setup default actions and filters
	 *
	 * @since 1.0.0
	 */
	private function setup_actions() {

		// Load textdomain
		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );

		// Maybe switch
		add_action( 'plugins_loaded', array( $this, 'maybe_switch' ), 100 );

		// Maybe update switch
		add_action( 'init', array( $this, 'maybe_update_switch' ), 1 );

		// Admin bar menu switcher
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ) );

		// Plugin settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Settings link
		add_filter( 'plugin_action_links', array( $this, 'plugin_action_links' ), 10, 2 );
	}

	/** Plugin **********************************************************/

	/**
	 * Load the translation file for current language. Checks the languages
	 * folder inside the plugin first, and then the default WordPress
	 * languages folder.
	 *
	 * Note that custom translation files inside the plugin folder will be
	 * removed on plugin updates. If you're creating custom translation
	 * files, please use the global language folder.
	 *
	 * @since 1.0.0
	 *
	 * @uses apply_filters() Calls 'plugin_locale' with {@link get_locale()} value
	 * @uses load_textdomain() To load the textdomain
	 * @uses load_plugin_textdomain() To load the textdomain
	 */
	public function load_textdomain() {

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale', get_locale(), $this->domain );
		$mofile        = sprintf( '%1$s-%2$s.mo', $this->domain, $locale );

		// Setup paths to current locale file
		$mofile_local  = $this->lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/theme-switcher/' . $mofile;

		// Look in global /wp-content/languages/theme-switcher folder
		load_textdomain( $this->domain, $mofile_global );

		// Look in local /wp-content/plugins/theme-switcher/languages/ folder
		load_textdomain( $this->domain, $mofile_local );

		// Look in global /wp-content/languages/plugins/
		load_plugin_textdomain( $this->domain );
	}

	/** Helper methods **************************************************/

	/**
	 * Shortcut to return the switcher class instance
	 *
	 * @uses 1.0.0
	 * 
	 * @return The one Conditional_Themes_Switcher instance
	 */
	public function switcher() {
		return Conditional_Themes_Switcher::instance();
	}

	/**
	 * Return the current user ID
	 *
	 * Enables to get the current user ID before the current user is set.
	 *
	 * @since 1.0.0
	 * 
	 * @uses did_action()
	 * @uses apply_filters() Calls 'determine_current_user' to get the current user id
	 *                        when the current user is not set yet.
	 * @uses get_current_user_id()
	 *
	 * @return int Current user ID
	 */
	public function get_current_user_id() {
		return did_action( 'set_current_user' ) ? get_current_user_id() : apply_filters( 'determine_current_user', 0 );
	}

	/**
	 * Return the theme stylesheet to switch to
	 *
	 * @since 1.0.0
	 * 
	 * @uses apply_filters() Calls 'theme_switcher_theme'
	 *
	 * @return int Current user ID
	 */
	public function get_switch_theme() {
		return apply_filters( 'theme_switcher_switch_theme', get_option( 'theme-switcher_switch-theme' ) );
	}

	/**
	 * Return whether theme switching is enabled for the current site
	 *
	 * @since 1.0.0
	 * 
	 * @return bool Switching is enabled
	 */
	public function is_switching_enabled() {
		return get_option( 'theme-switcher' );
	}

	/**
	 * Return whether the user has enabled switching
	 *
	 * @since 1.0.0
	 *
	 * @param int $user_id Optional. The user ID. Defaults to current user.
	 * @return bool Switching is enabled for the user
	 */
	public function is_switching_user_enabled( $user_id = 0 ) {
		$retval = false;

		if ( empty( $user_id ) ) {
			$user_id = $this->get_current_user_id();
		}

		if ( $user_id ) {
			$retval = get_user_option( 'theme-switcher', $user_id );
		}

		return (bool) $retval;
	}

	/** Admin methods ***************************************************/

	/**
	 * Append a link to the plugin's settings page
	 *
	 * @since 1.0.1
	 * 
	 * @param array $actions Action links
	 * @param string $plugin Plugin basename
	 * @return array Actions
	 */
	public function plugin_action_links( $actions, $plugin ) {

		// This is our plugin
		if ( $this->basename === $plugin ) {
			$actions['settings'] = sprintf( '<a href="%s">%s</a>', admin_url( 'options-reading.php' ), __( 'Settings' ) );
		}

		return $actions;
	}

	/**
	 * Register the plugin's settings
	 *
	 * @since 1.0.0
	 *
	 * @uses add_settings_field()
	 * @uses register_setting()
	 */
	public function register_settings() {

		// Settings field
		add_settings_field( 'theme-switcher', __( 'Theme Switcher', 'theme-switcher' ), array( $this, 'display_settings' ), 'reading' );

		// Settings
		register_setting( 'reading', 'theme-switcher', array( $this, 'sanitize_checkbox' ) );
		register_setting( 'reading', 'theme-switcher_switch-theme' );
	}

	/**
	 * Display the plugin's settings field
	 *
	 * @since 1.0.0
	 *
	 * @uses do_settings_fields()
	 */
	public function display_settings() { ?>

		<label><?php
			$switch_theme = $this->get_switch_theme();
			$enabled      = $this->is_switching_enabled();
			$themes       = '';

			// List available themes
			foreach ( wp_get_themes( array( 'allowed' => true ) ) as $theme ) {
				$themes .= '<option value="' . $theme->stylesheet . '" ' . selected( $theme->stylesheet, $switch_theme, false ) . '>' . $theme->title . '</option>';
			}

			$onclick  = "jQuery('#theme-switcher-settings').toggleClass('hidden');";
			$checkbox = '<input type="checkbox" name="theme-switcher" value="1" ' . checked( $enabled, true, false ) .' onclick="' . $onclick . '" />';
			$dropdown = '<select name="theme-switcher_switch-theme"><option value="">' . __( 'Select a theme', 'theme-switcher' ) . '</option>' . $themes . '</select>';

			// Output setting line
			printf( __( '%1$s Enable capable users to switch the site\'s layout to the %2$s theme', 'theme-switcher' ), $checkbox, $dropdown );
		?></label>
		<p class="description"><?php _e( 'By default, theme switching is only allowed for site admins.', 'theme-switcher' ); ?></p>

		<?php if ( $this->has_section_settings_fields( 'reading', 'theme-switcher' ) ) : ?>

		<div id="theme-switcher-settings" <?php if ( ! $enabled ) { echo 'class="hidden"'; } ?>>
			<h4><?php _e( 'Theme Switcher Settings', 'theme-switcher' ); ?></h4>

			<table>
				<?php do_settings_fields( 'reading', 'theme-switcher' ); ?>
			</table>
		</div>

		<?php endif;
	}

	/**
	 * Return whether the given settings section has settings fields
	 *
	 * @since 1.0.1
	 * 
	 * @param string $page Page name of the section
	 * @param string $section Section name
	 * @return bool Section has settings fields
	 */
	public function has_section_settings_fields( $page, $section ) {
		global $wp_settings_fields;
		return isset( $wp_settings_fields[ $page ][ $section ] ) && ! empty( $wp_settings_fields[ $page ][ $section ] );
	}

	/**
	 * Sanitize values for checkbox options.
	 *
	 * Returns either 1 or 0, for unchecked options.
	 *
	 * @since 1.0.0
	 * 
	 * @param mixed $value Option value
	 * @return int Boolean checkbox value
	 */
	public function sanitize_checkbox( $value ) {
		$option = str_replace( 'sanitize_option_', '', current_filter() );
		$value  = (int) ( isset( $_REQUEST[ $option ] ) );
		return $value;
	}

	/**
	 * Update the current user's switch option from the admin bar menu
	 *
	 * @since 1.0.0
	 *
	 * @uses wp_verify_nonce()
	 * @uses update_user_option()
	 * @uses get_user_option()
	 * @uses wp_redirect()
	 */
	public function maybe_update_switch() {

		// Bail when nonce does not validate
		if ( ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'theme-switcher-user' ) )
			return;

		// Action was called
		if ( isset( $_REQUEST['action'] ) ) {
			$user_id = get_current_user_id();

			switch ( $_REQUEST['action'] ) {

				// Enable the switched theme
				case 'switch-theme' :

					// Invert theme-switcher option
					update_user_option( $user_id, 'theme-switcher', (int) ! $this->is_switching_user_enabled( $user_id ) );
					break;
			}
		}

		// Send user back
		$sendback = esc_url_raw( remove_query_arg( array( 'action', '_wpnonce' ), wp_get_referer() ) );
		wp_redirect( $sendback );
		exit;
	}

	/**
	 * Setup switching when requested
	 *
	 * @since 1.0.0
	 *
	 * @uses Theme_Switcher::get_current_user_id()
	 * @uses get_option()
	 * @uses get_user_option()
	 * @uses Conditional_Themes_Manager::set_option()
	 * @uses Conditional_Themes_Manager::register()
	 */
	public function maybe_switch() {

		// Switching is enabled for both the site and the user
		if ( $this->is_switching_enabled() && $this->is_switching_user_enabled() ) {

			// Checking for user capabilities fails when before 'init':
			// `$user->has_cap()` runs `is_super_admin` which eventually runs `set_current_user`
			// if ( ! user_can( $this->get_current_user_id(), 'manage_options' ) )
			// 	return;

			// No persistent switch. Only for the current user
			Conditional_Themes_Manager::set_option( 'persistent', false );

			// Switch theme
			Conditional_Themes_Manager::register( $this->get_switch_theme(), '__return_true' );
			// Conditional_Themes_Manager::register( $this->get_switch_theme(), array( $this, 'switch_conditional' ) );
		}
	}

	/**
	 * Return the result of the conditional switch logic
	 *
	 * @since 1.0.0
	 *
	 * @uses Theme_Switcher::is_switching_user_enabled()
	 * 
	 * @return bool Switch the theme
	 */
	public function switch_conditional() {
		return $this->is_switching_user_enabled();
	}

	/**
	 * Add the switcher to the admin bar menu
	 * 
	 * @since 1.0.0
	 *
	 * @uses current_user_can()
	 * @uses wp_get_theme()
	 * @uses Conditional_Themes_Switcher::get_switched_theme()
	 * 
	 * @param WP_Admin_Bar $wp_admin_bar
	 */
	public function admin_bar_menu( $wp_admin_bar ) {

		// Bail when in the network admin, the user is not capable or when switching is disabled
		if ( is_network_admin() || ! current_user_can( 'manage_options' ) || ! $this->is_switching_enabled() )
			return;

		// Get the switch-to theme
		$theme = wp_get_theme( $this->get_switch_theme() );

		// Bail when the theme is missing
		if ( ! $theme->exists() )
			return;

		// When switched
		$switched = $this->switcher()->get_switched_theme();
		if ( $switched ) {
			$title = __( 'Switch back to the original layout', 'theme-switcher' ); 
		} else {
			$title = sprintf( __( 'Switch the layout to the %s theme', 'theme-switcher' ), $theme->title );
		}

		// Add menu item
		$wp_admin_bar->add_menu( array(
			'id'        => 'theme-switcher',
			'parent'    => 'top-secondary',
			'title'     => '<span class="ab-icon"></span><span class="screen-reader-text">' . $title . '</span>',
			'href'      => esc_url( wp_nonce_url( add_query_arg( 'action', 'switch-theme' ), 'theme-switcher-user' ) ),
			'meta'      => array(
				'class'     => $switched ? 'hover active' : '',
				'title'     => esc_attr( $title )
			),
		) );

		// Hook admin bar styles. After core's footer scripts
		add_action( 'wp_footer',    array( $this, 'admin_bar_scripts' ), 21 );
		add_action( 'admin_footer', array( $this, 'admin_bar_scripts' ), 21 );
	}

	/**
	 * Output custom scripts
	 *
	 * @since 1.0.0
	 *
	 * @uses is_admin_bar_showing()
	 */
	public function admin_bar_scripts() {

		// For the admin bar
		if ( ! is_admin_bar_showing() )
			return; ?>

		<style type="text/css">
			#wpadminbar #wp-admin-bar-theme-switcher > .ab-item {
				padding: 0 9px 0 7px;
			}

			#wpadminbar #wp-admin-bar-theme-switcher > .ab-item .ab-icon {
				width: 18px;
				height: 20px;
				margin-right: 0;
			}

			#wpadminbar #wp-admin-bar-theme-switcher > .ab-item .ab-icon:before {
				content: '\f100'; /* dashicons-admin-appearance */
				top: 2px;
				opacity: 0.4;
			}

				#wpadminbar #wp-admin-bar-theme-switcher.active > .ab-item .ab-icon:before {
					opacity: 1;
				}
		</style>

		<?php
	}
}

/**
 * Return single instance of this main plugin class
 *
 * @since 1.0.0
 * 
 * @return Theme_Switcher
 */
function theme_switcher() {
	return Theme_Switcher::instance();
}

// Initiate
theme_switcher();

endif; // class_exists
