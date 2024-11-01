<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://github.com/Gabrielchihonglee
 * @since             1.0
 * @package           Wpattach
 *
 * @wordpress-plugin
 * Plugin Name:       WP Attach
 * Plugin URI:        https://github.com/Gabrielchihonglee/wpattach
 * Description:       Attach files to posts or pages as an information box by using the shortcode [wpattach link="LINK_TO_YOUR_FILE"].
 * Version:           1.0.2
 * Author:            Gabriel Chi Hong Lee
 * Author URI:        https://github.com/Gabrielchihonglee
 * License:           GPL-3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       wpattach
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wpattach-activator.php
 */
function activate_wpattach() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpattach-activator.php';
	Wpattach_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wpattach-deactivator.php
 */
function deactivate_wpattach() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wpattach-deactivator.php';
	Wpattach_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wpattach' );
register_deactivation_hook( __FILE__, 'deactivate_wpattach' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wpattach.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wpattach() {

	$plugin = new Wpattach();
	$plugin->run();

}
run_wpattach();

function wpattach_formatBytes($bytes, $precision = 2) { 
    $units = array('B', 'KB', 'MB', 'GB', 'TB'); 

    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 

    // Uncomment one of the following alternatives
     $bytes /= pow(1024, $pow);
    // $bytes /= (1 << (10 * $pow)); 

    return round($bytes, $precision) . ' ' . $units[$pow]; 
} 


function wpattach_GetRemoteLastModified( $uri )
{
    // default
    $unixtime = 0;
    
    $fp = fopen( $uri, "r" );
    if( !$fp ) {return;}
    
    $MetaData = stream_get_meta_data( $fp );
        
    foreach( $MetaData['wrapper_data'] as $response )
    {
        // case: redirection
        if( substr( strtolower($response), 0, 10 ) == 'location: ' )
        {
            $newUri = substr( $response, 10 );
            fclose( $fp );
            return wpattach_GetRemoteLastModified( $newUri );
        }
        // case: last-modified
        elseif( substr( strtolower($response), 0, 15 ) == 'last-modified: ' )
        {
            $unixtime = strtotime( substr($response, 15) );
            break;
        }
    }
    fclose( $fp );
    return $unixtime;
}


function wpattach_shortcode( $atts ) {
    $wpattachvar = shortcode_atts( array(
        'link' => '(blank)',
        'size' => 'medium',
    ), $atts );
    
$path_parts = pathinfo($wpattachvar["link"]);
 
 
 $ch = curl_init($wpattachvar["link"]);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

    $data = curl_exec($ch);
    curl_close($ch);

    if (preg_match('/Content-Length: (\d+)/', $data, $matches)) {

        // Contains file size in bytes
        $contentLength = (int)$matches[1];

    }
 
                                      return '
    <div class="attachbox" style="max-width:300px;">
        <div class="row" style="display: flex; flex-wrap: wrap; border: 1px solid #ddd; position: relative; max-width:300px;">
            <div class="col-xs-3" style="display: flex; flex-direction: column;">
            <a href="'.$wpattachvar["link"].'"><img src="'.plugins_url( 'public/fileicon/', __FILE__ ).$path_parts['extension'].'.png" style="width:75%; height: auto; max-width:70px; position: absolute; left: 50%; top: 50%; transform: translate(-50%, -50%);"></a>
            </div>
            <div class="col-xs-9" style="display: flex; flex-direction: column; padding-left: 5px; padding-top:10px; padding-bottom:10px;">
                <h5 style="margin:0 0 10px 0;"><a href="'.$wpattachvar["link"].'" style="font-size:16px;">'.basename($wpattachvar["link"]).PHP_EOL.'</a></h5>
                <p style="font-size:13px; margin:0;">File size: '.wpattach_formatBytes($contentLength).'<br>Last updated: '.gmdate("Y-m-d", wpattach_GetRemoteLastModified($wpattachvar["link"])).'<br></p>
            </div>
        </div>
    </div>
    <style>
        @-ms-viewport {
            width: device-width;
        }
        
        .row {
            margin-left: -15px;
            margin-right: -15px;
        }
        
        .col,
        .col-xs-3,
        .col-xs-9 {
            position: relative;
            min-height: 1px;
            padding-left: 15px;
            padding-right: 15px;
        }
        
        .col,
        .col-xs-3,
        .col-xs-9 {
            float: left;
        }
        
        .col-xs-9 {
            width: 75%;
        }
        
        .col-xs-3 {
            width: 25%;
        }
        
        .clearfix,
        .clearfix:before,
        .clearfix:after,
        .container:before,
        .container:after,
        .container-fluid:before,
        .container-fluid:after,
        .row:before,
        .row:after {
            content: " ";
            display: table;
        }
        
        .clearfix:after,
        .container:after,
        .container-fluid:after,
        .row:after {
            clear: both;
        }
        
        .center-block {
            display: block;
            margin-left: auto;
            margin-right: auto;
        }
        
        .pull-right {
            float: right !important;
        }
        
        .pull-left {
            float: left !important;
        }
        
        *,
        *:before,
        *:after {
            -webkit-box-sizing: border-box;
            -moz-box-sizing: border-box;
            box-sizing: border-box;
        }
    </style>
    ' ; } add_shortcode( 'wpattach', 'wpattach_shortcode' );