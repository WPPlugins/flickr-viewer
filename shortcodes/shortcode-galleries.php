<?php
/**
 * Copyright (c) 2011, cheshirewebsolutions.com, Ian Kennerley (info@cheshirewebsolutions.com).
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
function cws_fgp_shortcode_galleries( $atts ) {

    $plugin = new CWS_Flickr_Gallery_Pro();
    $plugin_admin = new CWS_Flickr_Gallery_Pro_Admin( $plugin->get_plugin_name(), $plugin->get_version(), $plugin->get_isPro() );

    // Get the options stored in the database
    $options = get_option( 'cws_fgp_options' );

    // Debug flag
    $cws_debug = get_query_var('cws_debug');
    
    // set some defaults...
    $options['privacy_filter'] = isset($options['privacy_filter']) ? $options['privacy_filter'] : "";
    $options['size'] = isset($options['size']) ? $options['size'] : "";
    $options['thumb_size'] = isset($options['thumb_size']) ? $options['thumb_size'] : "";
    $options['lightbox_image_size'] = isset($options['lightbox_image_size']) ? $options['lightbox_image_size'] : "";
    $options['num_image_results'] = isset($options['num_image_results']) ? $options['num_image_results'] : "";
    $options['enable_cache'] = isset($options['enable_cache']) ? $options['enable_cache'] : false;
    $options['enable_download'] = isset($options['enable_download']) ? $options['enable_download'] : false;
    $options['row_height'] = isset($options['row_height']) ? $options['row_height'] : "150";
    $options['theme'] = isset($options['theme']) ? $options['theme'] : "grid";
    $options['show_title'] = isset($options['show_title']) ? $options['show_title'] : false;
    $options['gallery_id'] = isset($options['gallery_id']) ? $options['gallery_id'] : false;

    // Get page number from the url - if there isn't one - we're on page 1
    $cws_page = isset( $_GET['cws_page'] ) ? $_GET['cws_page'] : 1;

    // Init some vars
    $strFXStyle = '';
    $fx = null;
    $strOutput = '';

    // Get the options from the db
    $code = get_option( 'cws_fgp_code' );//  api key and api secret from db
    $ftoken = get_option( 'cws_fgp_token' );

    $flickr = new phpFlickr( $code['api_key'], $code['api_secret'], true );
    
    // set File system cache, if option is set in settings page and is Pro
    if( $options['enable_cache'] == true && $plugin->isPro == 1 ) {
        $flickr->enableCache( "fs", dirname(__FILE__) . "/../cache" );
    }

    $flickr->setToken($ftoken);

    // Get our authentication token needed for requests.  This should be tested for a `false` value.
    // The user's details will be within the $token array.
    $token = $flickr->auth_checkToken();

    // Get the user's base photo URL needed for linking retrieved images.
    $base_url   = $flickr->urls_getUserPhotos($token['user']['nsid']);

    // Extract the options from db and overwrite with any set in the shortcode
    extract( shortcode_atts( array(
        'privacy_filter' => $options['privacy_filter'],  
        'thumb_size' => $options['thumb_size'], 
        'lightbox_image_size' => $options['lightbox_image_size'], 
        'num_image_results' => $options['num_image_results'], 
        'enable_download' => $options['enable_download'], 
        'theme' => $options['theme'], 
        'row_height' => $options['row_height'],
        'show_title' => $options['show_title'],
        'gallery_id' => $options['gallery_id'],
    ), $atts ) );

    if ( $show_title === 'false' ) $show_title = false; // just to be sure...
    if ( $show_title === 'true' ) $show_title = true; // just to be sure...
    if ( $show_title === '0' ) $show_title = false; // just to be sure...
    if ( $show_title === '1' ) $show_title = true; // just to be sure...
    $show_title = ( bool ) $show_title;  

    if ( $enable_download === 'false' ) $enable_download = false; // just to be sure...
    if ( $enable_download === 'true' ) $enable_download = true; // just to be sure...
    if ( $enable_download === '0' ) $enable_download = false; // just to be sure...
    if ( $enable_download === '1' ) $enable_download = true; // just to be sure...
    $enable_download = ( bool ) $enable_download;      


    $arrArgs = array();
    //$arrArgs['per_page'] = $num_image_results;
    //$arrArgs['page'] = $cws_page;
    $arrArgs['extras'] = 'original_format,url_l,o_dims';   

    if( isset( $privacy_filter) && !empty( $privacy_filter ) ) { $arrArgs['privacy_filter'] = $privacy_filter; }

    if( $plugin->get_isPro() == 1 && $enable_download == true ) {
        $extras = 'original_format';
    } else {
        $extras = '';        
    }

    // Get users photo galleries
    // seems to be API bug that does not honour $per_page and $page
    // 
    $photos = $flickr->galleries_getPhotos ( $gallery_id, $arrArgs['extras'], $per_page = $num_image_results , $page = $cws_page );
    // $photos = $flickr->galleries_getPhotos ( $gallery_id, $arrArgs['extras'], 1 , 2 );

    $pages = $photos['photos']['pages']; // total number of pages
    $total_num_albums = $photos['photos']['total']; // total number of photos

    // Display debug values...
    if( $cws_debug == "1" ){ 
        echo 'Options';       
        echo '<pre>';
        print_r($options);
        echo '</pre>';
        
        echo 'Atts';       
        echo '<pre>';
        print_r($atts);
        echo '</pre>';

        echo 'Response from Flickr';        
        echo '<pre>';
        print_r($photos);
        echo '</pre>';
    }

    #----------------------------------------------------------------------------
    # Decide on the layout theme for the images
    #----------------------------------------------------------------------------
    
    switch( $theme ) {

        #----------------------------------------------------------------------------
        # Grid Layout
        #----------------------------------------------------------------------------
        case "grid":
            include('partials/results_grid.php');  
            break;

        #----------------------------------------------------------------------------
        # Justified Image Grid Layout *** PRO ONLY ***
        #----------------------------------------------------------------------------
        case "projig":
            include('partials_pro/pro_jig.php');  
            break;

        default:
            include('partials/results_grid.php');            
    } // End switch
    

    #----------------------------------------------------------------------------
    # Show output for pagination
    #----------------------------------------------------------------------------
    if( $num_image_results > 0 ) {

        $num_pages  = ceil( $total_num_albums / $num_image_results );

        // Display debug values...
        if( $cws_debug == "1" ){ 
            echo "<hr>total_num_albums = $total_num_albums<br>";
            echo "num_image_results = $num_image_results<br>";
            echo "lightbox_image_size = $lightbox_image_size<br>";
            echo "num_pages = $num_pages<br>";
            // echo "size = $size<br>";
            echo "thumb_size = $thumb_size<br>";
            echo "row_height = $row_height<br>";            
            echo "enable_cache = " . $options['enable_cache'] ;                       
            echo "<hr>";
        }

    }
    // Removed pagination v1.1.2 as Galleries are limited to 18 images each
    // total results, num to show per page, current page
    //$strOutput .= $plugin_admin->get_pagination( $total_num_albums, $num_image_results, $cws_page );

	return $strOutput;
}