<?php

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class Poeditor_Shortcode
 */
class Poeditor_Shortcode_Admin {

    public $token = 'poeditor_shortcode';
    public $title = 'POEditor Shortcode';
    public $permissions = 'manage_dt';

    /**  Singleton */
    private static $_instance = null;
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }
    /**
     * Constructor function.
     * @access  public
     * @since   0.1.0
     */
    public function __construct() {

        if ( is_admin() ) {
            add_action( "admin_menu", [ $this, "register_menu" ] );
            // adds links to the plugin description area in the plugin admin list.
            add_filter( 'plugin_row_meta', [ $this, 'plugin_description_links' ], 10, 4 );

        }
    } // End __construct()


    /**
     * Loads the subnav page
     * @since 0.1
     */
    public function register_menu() {
        add_menu_page( 'Extensions (DT)', 'Extensions (DT)', $this->permissions, 'dt_extensions', [ $this, 'extensions_menu' ], 'dashicons-admin-generic', 59 );
        add_submenu_page( 'dt_extensions', $this->title, $this->title, $this->permissions, $this->token, [ $this, 'content' ] );
    }

    /**
     * Menu stub. Replaced when Disciple Tools Theme fully loads.
     */
    public function extensions_menu() {}

    /**
     * Builds page contents
     * @since 0.1
     */
    public function content() {

        if ( !current_user_can( $this->permissions ) ) { // manage dt is a permission that is specific to Disciple Tools and allows admins, strategists and dispatchers into the wp-admin
            wp_die( 'You do not have sufficient permissions to access this page.' );
        }

        ?>
        <div class="wrap">
            <h2><?php echo esc_html( $this->title ) ?></h2>
            <div class="wrap">
                <div id="poststuff">
                    <div id="post-body" class="metabox-holder columns-2">
                        <div id="post-body-content">
                            <!-- Main Column -->

                            <?php $this->meta_box_add_api_key(); ?>
                            <?php $this->meta_box_shortcodes(); ?>

                            <!-- End Main Column -->
                        </div><!-- end post-body-content -->
                        <div id="postbox-container-1" class="postbox-container">
                            <!-- Right Column -->

                            <?php $this->right_column(); ?>

                            <!-- End Right Column -->
                        </div><!-- postbox-container 1 -->
                        <div id="postbox-container-2" class="postbox-container">
                        </div><!-- postbox-container 2 -->
                    </div><!-- post-body meta box container -->
                </div><!--poststuff end -->
            </div><!-- wrap end -->
        </div><!-- End wrap -->

        <?php
    }

    public function meta_box_add_api_key() {
        if ( isset( $_POST['poeditor_api_nonce'] )
            && wp_verify_nonce( sanitize_text_field(wp_unslash( $_POST['poeditor_api_nonce'] ) ), 'poeditor_api'. get_current_user_id() )
        ) {
            if ( isset( $_POST['poeditor_key'] ) && ! empty( $_POST['poeditor_key'] ) ) {
                $key = sanitize_text_field( wp_unslash( $_POST['poeditor_key'] ) );
                update_option('poeditor_api_key', $key, false );
            } else {
                delete_option( 'poeditor_api_key' );
            }
        }
        $current_key = get_poeditor_api_key();
        ?>
        <!-- Box -->
        <form method="post">
            <?php wp_nonce_field('poeditor_api'. get_current_user_id(), 'poeditor_api_nonce') ?>
            <table class="widefat striped">
                <thead>
                <tr>
                    <th>Add POEditor API Key</th>
                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>
                        Add API Key for POEditor: <input type="text" class="regular-text" name="poeditor_key" value="<?php echo esc_attr( $current_key) ?>" /><br>
                    </td>
                </tr>
                <tr><td><button type="submit" class="button">Update</button></td></tr>
                </tbody>
            </table>
        </form>
        <br>
        <!-- End Box -->
        <?php
    }

    public function meta_box_shortcodes(){
        $api_key = get_poeditor_api_key();
        if ( empty( $api_key ) ) {
            return;
        }

        $projects = $this->list_projects();
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr>
                <th colspan="3">Available Projects and Usable Short Codes</th>
            </tr>
            </thead>
            <tbody>
            <?php
            if ( is_array( $projects ) ) {
                foreach($projects as $project){
                    ?>
                    <tr>
                        <td><?php echo esc_html( $project['name'] ) ?></td>
                        <td><?php echo esc_html( $project['id'] ) ?></td>
                        <td>[peoeditor_shortcode id="<?php echo esc_html( $project['id'] ) ?>" name="<?php echo esc_html( $project['name'] ) ?>"]</td>
                    </tr>
                    <?php
                }
            }
            ?>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    public function list_projects() {
        $api_key = get_poeditor_api_key();
        $list_projects_url = 'https://api.poeditor.com/v2/projects/list';
        $args = [
            'method' => 'POST',
            'body' => [
                "api_token" => $api_key,
            ]
        ];
        $response = wp_remote_post( $list_projects_url, $args );
        if ( is_wp_error( $response ) ) {
            dt_write_log($response);
            return [];
        }
        else {
            $response_content = json_decode( $response['body'], true );
            if ( $response_content['response']['status'] === 'success' ) {
                $projects = $response_content['result']['projects'];
                return $projects;
            }
            else {
                dt_write_log(new WP_Error(__METHOD__, 'failed response from the poeditor api'));
                return [];
            }
        }
    }

//    public function list_project_details( $project_id ) {
//        $api_key = get_poeditor_api_key();
//        $api_url = 'https://api.poeditor.com/v2/projects/view';
//        $args = [
//            'method' => 'POST',
//            'body' => [
//                "api_token" => $api_key,
//                "id" => $project_id
//            ]
//        ];
//        $response = wp_remote_post( $api_url, $args );
//        return json_decode( $response['body'], true );
//    }

    public function right_column() {
        ?>
        <!-- Box -->
        <table class="widefat striped">
            <thead>
            <tr><th>Information</th></tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    This is a simple plugin that connects to a POEditor project through an API key provided to admins in
                    the account area. Using this key it generates shortcodes for each project and that shortcode can be added
                    to any page or post to list with a progress bar the status of that language.
                </td>
            </tr>
            <tr>
                <td>
                    <hr>
                </td>
            </tr>
            <tr>
                <td>
                    All of these css values can be overridden<br>
                    .ui-progressbar {<br>
                    border: 1px solid lightgrey;<br>
                    height: 40px;<br>
                    }<br>
                    .ui-progressbar-value {<br>
                    background-color: #8bc34a;<br>
                    height: 40px;<br>
                    }<br>
                    .progressbar-element-wrapper {<br>
                    width: 100%;<br>
                    padding-bottom:20px;<br>
                    }<br>
                    .progressbar-element-title {<br>
                    }<br>
                    .progressbar-element-bar {<br>
                    }<br>
                    span.language-name {<br>
                    font-size:1.3em;<br>
                    font-weight:bold;<br>
                    }<br>
                    span.language-percentage {<br>
                    font-size:1.3em;<br>
                    font-weight:bold;<br>
                    }<br>
                </td>
            </tr>
            </tbody>
        </table>
        <br>
        <!-- End Box -->
        <?php
    }

    /**
     * Filters the array of row meta for each/specific plugin in the Plugins list table.
     * Appends additional links below each/specific plugin on the plugins page.
     *
     * @access  public
     * @param   array       $links_array            An array of the plugin's metadata
     * @param   string      $plugin_file_name       Path to the plugin file
     * @param   array       $plugin_data            An array of plugin data
     * @param   string      $status                 Status of the plugin
     * @return  array       $links_array
     */
    public function plugin_description_links( $links_array, $plugin_file_name, $plugin_data, $status ) {
        if ( strpos( $plugin_file_name, basename( __FILE__ ) ) ) {
            // You can still use `array_unshift()` to add links at the beginning.

            $links_array[] = '<a href="https://disciple.tools">Disciple.Tools Community</a>'; // @todo replace with your links.

            // add other links here
        }

        return $links_array;
    }

    /**
     * Method that runs only when the plugin is activated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function activation() {

    }

    /**
     * Method that runs only when the plugin is deactivated.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public static function deactivation() {

    }

    /**
     * Magic method to output a string if trying to use the object as a string.
     *
     * @since  0.1
     * @access public
     * @return string
     */
    public function __toString() {
        return $this->token;
    }

    /**
     * Magic method to keep the object from being cloned.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, esc_html( 'Whoah, partner!' ), '0.1' );
    }

    /**
     * Magic method to keep the object from being unserialized.
     *
     * @since  0.1
     * @access public
     * @return void
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, esc_html( 'Whoah, partner!' ), '0.1' );
    }

    /**
     * Magic method to prevent a fatal error when calling a method that doesn't exist.
     *
     * @param string $method
     * @param array $args
     *
     * @return null
     * @since  0.1
     * @access public
     */
    public function __call( $method = '', $args = array() ) {
        // @codingStandardsIgnoreLine
        _doing_it_wrong( __FUNCTION__, esc_html('Whoah, partner!'), '0.1' );
        unset( $method, $args );
        return null;
    }
}
