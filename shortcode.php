<?php

if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly

/**
 * Class Poeditor_Shortcode
 */
class Poeditor_Shortcode {

    public $token = 'poeditor_shortcode';
    public $title = 'POEditor Shortcode';
    public $permissions = 'manage_options';
    public $namespace = '';

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
        $this->api_key = get_poeditor_api_key();
        if ( empty( $this->api_key  ) ) {
            return;
        }

        $this->namespace = 'poeditor_shortcode/v1';

        add_shortcode( 'poeditor_shortcode', [ $this, 'short_code' ] );
    }

    public function short_code( $atts = [] ){
        wp_enqueue_script( 'jquery-ui' );
        wp_enqueue_script( 'jquery-ui-progressbar' );

        $id = (int) $atts['id'];
        $languages = $this->list_project_languages( $id );

        ob_start();
        ?>
        <style>
            .ui-progressbar {
                border: 1px solid lightgrey;
                height: 40px;
            }
            .ui-progressbar-value {
                background-color: #8bc34a;
                height: 40px;
            }
            .progressbar-element-wrapper {
                width: 100%;
                padding-bottom:20px;
            }
            .progressbar-element-title {
            }
            .progressbar-element-bar {
            }
            span.language-name {
                font-size:1.3em;
                font-weight:bold;
            }
            span.language-percentage {
                font-size:1.3em;
                font-weight:bold;
            }
        </style>
        <div id="language_list"></div>
        <script>
            let languages = [<?php echo  json_encode($languages) ?>][0]
            let language_list = jQuery('#language_list')
            jQuery(document).ready( function() {
                jQuery.each(languages, function(i,v){
                    language_list.append(`
                     <div class="progressbar-element-wrapper">
                        <div class="progressbar-element-title"><span class="language-name">${v.name}</span> <span class="language-percentage">(${v.percentage }%)</span></div>
                        <div class="progressbar-element-bar" id="${v.code}"></div>
                     </div>
                `)
                    jQuery('#'+v.code).progressbar({
                        value: v.percentage
                    })
                })
            } );

            console.log(languages)
        </script>

        <?php
        return ob_get_clean();
    }

    public function list_project_languages( $project_id ) {
        $api_url = 'https://api.poeditor.com/v2/languages/list';
        $args = [
            'method' => 'POST',
            'body' => [
                "api_token" => $this->api_key,
                "id" => $project_id
            ]
        ];
        $response = wp_remote_post( $api_url, $args );

        if ( is_wp_error( $response ) ) {
            dt_write_log($response);
            return [];
        }
        else {
            $response_content = json_decode( $response['body'], true );
            if ( $response_content['response']['status'] === 'success' ) {
                $languages = $response_content['result']['languages'];
                dt_write_log($response_content);
                return $languages;
            }
            else {
                dt_write_log(new WP_Error(__METHOD__, 'failed response from the poeditor api'));
                return [];
            }

        }

    }
}

