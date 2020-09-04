<?php
/**
 * Plugin Name: POEditor Shortcode
 * Plugin URI: https://github.com/ZumeProject/poeditor-shortcode
 * Description: This simple plugin connects to a POEditor project and gets the list and status of active languages and places it into a shortcode to be installed on a page.
 * Version:  0.1.0
 * Author URI: https://github.com/ZumeProject
 * GitHub Plugin URI: https://github.com/ZumeProject/poeditor-shortcode
 * Requires at least: 4.7.0
 * (Requires 4.7+ because of the integration of the REST API at 4.7 and the security requirements of this milestone version.)
 * Tested up to: 5.3
 *
 * @package Disciple_Tools
 * @link    https://github.com/DiscipleTools
 * @license GPL-2.0 or later
 *          https://www.gnu.org/licenses/gpl-2.0.html
 */

add_action( 'after_setup_theme', function (){

    require_once( 'shortcode.php' );
    Poeditor_Shortcode::instance();

    if ( is_admin() ){
        require_once('adminpage.php');
        Poeditor_Shortcode_Admin::instance();
    }

});

function get_poeditor_api_key(){
    return get_option('poeditor_api_key');
}
