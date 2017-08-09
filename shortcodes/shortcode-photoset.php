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
function cws_fgp_shortcode_photoset( $atts ) {

    $plugin = new CWS_Flickr_Gallery_Pro();
    $plugin_admin = new CWS_Flickr_Gallery_Pro_Admin( $plugin->get_plugin_name(), $plugin->get_version(), $plugin->get_isPro() );

    // Debug flag
    $cws_debug = get_query_var('cws_debug');

    // Get page number from the url - if there isn't one - we're on page 1
    $cws_page = isset( $_GET['cws_page'] ) ? $_GET['cws_page'] : 1;
    // $num_results = 2; // Get from option / shortcode

    $strOutput = '';

    // Get the options from the db
    $code = get_option( 'cws_fgp_code' );//  api key and api secret from db
    $options = get_option( 'cws_fgp_options' );
    $ftoken = get_option( 'cws_fgp_token' );
//
    // set some defaults...
    $options['id'] = isset( $options['id'] ) ? $options['id'] : "";
    $options['privacy_filter'] = isset($options['privacy_filter']) ? $options['privacy_filter'] : "";
    $options['size'] = isset($options['size']) ? $options['size'] : "";
    $options['thumb_size'] = isset($options['thumb_size']) ? $options['thumb_size'] : "";
    $options['lightbox_image_size'] = isset($options['lightbox_image_size']) ? $options['lightbox_image_size'] : "";
    $options['num_image_results'] = isset($options['num_image_results']) ? $options['num_image_results'] : "";

    // Extract the options from db and overwrite with any set in the shortcode
    extract( shortcode_atts( array(
        'id' => $options['id'],
        'size' => $options['size'],  
        'thumb_size' => $options['thumb_size'], 
        'lightbox_image_size' => $options['lightbox_image_size'], 
        'num_image_results' => $options['num_image_results'],                 
    ), $atts ) );

    $flickr = new phpFlickr( $code['api_key'], $code['api_secret'], true );
    ///$flickr->enableCache( "fs", dirname(__FILE__) . "/../cache" );
    
    $flickr->setToken($ftoken);

    // Get our authentication token needed for requests.  This should be tested for a `false` value.
    // The user's details will be within the $token array.
    $token      = $flickr->auth_checkToken();

    $extras ='';
    $privacy_filter = '';

    $photos = $flickr->photosets_getPhotos($id, $extras, $privacy_filter, $num_image_results, $cws_page );

    $pages = $photos['photoset']['pages']; // total number of pages
    $total_num_albums = $photos['photoset']['total']; // total number of photos


    #----------------------------------------------------------------------------
    # Decide on the layout theme for the images
    #----------------------------------------------------------------------------
    $theme = "grid";

    switch( $theme ) {

        #----------------------------------------------------------------------------
        # Grid Layout
        #----------------------------------------------------------------------------
        case "grid":
            // Include Lightbox
            wp_enqueue_script( 'cws_fgp_lightbox', plugin_dir_url( __FILE__ )  . '../public/js/lightbox/lightbox.js', array( 'jquery' ), false, true );                 
            wp_enqueue_script( 'cws_fgp_init_lightbox', plugin_dir_url( __FILE__ )  . '../public/js/lightbox/init_lightbox.js', array( 'cws_fgp_lightbox' ), false , true );

            // Init Masonry
            wp_enqueue_script( 'cws_fgp_imagesLoaded', plugin_dir_url( __FILE__ )  . '../public/js/imagesloaded.pkgd.min.js', array( 'jquery' ), false, true ); 
            wp_enqueue_script( 'cws_fgp_init_masonry', plugin_dir_url( __FILE__ )  . '../public/js/init_masonry.js', array( 'masonry' ), false , true );            
            
            // include 'partials/grid.php';
            $strOutput .=  "<div class='listviewxxx'>\n";
            $strOutput .=  "<style>.grid-item.albums{ width: " . $thumb_size . "px !important; }</style>\n";

            $strOutput .= "<h2>" . $photos['photoset']['title'] . "</h2>"; 

            // Work out intColumnWidth based on value chosen in $thumb_size...
            switch ($thumb_size) {

                case 'square':
                case 'square_75':        
                    $intColumnWidth = 75 + 20;
                    break;

                case 'square_150':        
                    $intColumnWidth = 150 + 20;
                    break;

                case 'thumbnail':        
                    $intColumnWidth = 100 + 20;
                    break;            

                case 'small':
                case 'small_240':
                    $intColumnWidth = 240 + 20;
                    break;

                case 'small_320':
                    $intColumnWidth = 320 + 20;
                    break;

                case 'medium':
                case 'medium_500':
                    $intColumnWidth = 500 + 20;
                    break;

                case 'medium_640':
                    $intColumnWidth = 640 + 20;
                    break;            
                
                case 'medium_800':
                    $intColumnWidth = 800 + 20;
                    break; 

                default:
                    $intColumnWidth = 75 + 20;
                    break;
            }
            // Init masonry 
            $strOutput .= "<div class='grid js-masonry' data-masonry-options='{ \"itemSelector\": \".grid-item\", \"columnWidth\": ".$intColumnWidth.", \"isOriginLeft\": true, \"isFitWidth\": true }'>\n";

            if( $cws_debug == 1) { echo "<pre>" . print_r( $photos, true ) . "</pre>"; }
    
            #----------------------------------------------------------------------------
            # Iterate over the array and extract the info we want
            #----------------------------------------------------------------------------
            foreach ($photos['photoset']['photo']  as $photo) {
                // $strOutput .= "<img alt=\"" . $photo['title'] . "\" src=\"" . $flickr->buildPhotoURL($photo, 'square_150') . "\" >";  
                $strOutput .= "<div class='thumbnail grid-item albums' style=\"" . $thumb_size . "\">\n";
                    $strOutput .=  "<div class='thumbimage'>\n";

                // print out a link to the photo page, attaching the id of the photo
                $strOutput .= "<a class='result-image-link' href=\"" . $flickr->buildPhotoURL( $photo, $lightbox_image_size) . "\" data-lightbox='result-set' data-title=\"View $photo[title]\" title=\"View $photo[title]\">";
                     
                // This next line uses buildPhotoURL to construct the location of our image, and we want the 'Square' size
                // It also gives the image an alt attribute of the photo's title
                // $strOutput .= "<img src=\"" . $flickr->buildPhotoURL( $photo, $size ) .  "\" width=\"" . $thumb_size . "\" height=\"" . $thumb_size . "\" alt=\"$photo[title]\" />";
                $strOutput .= "<img src=\"" . $flickr->buildPhotoURL( $photo, $thumb_size ) . " alt=\"$photo[title]\" />";
                 

                // close link 
                $strOutput .= "</a>";

                    $strOutput .=  "</div>\n"; // End .thumbimage
                $strOutput .=  "</div>\n"; // End .thumbnail        
            }

            $strOutput .= "</div>";

            break;

    } // End switch

    #----------------------------------------------------------------------------
    # Show output for pagination
    #----------------------------------------------------------------------------
    if( $num_image_results > 0 ) {

        $num_pages  = ceil( $total_num_albums / $num_image_results );

        if( $cws_debug == "1" ){ 
            echo "<hr>total_num_albums = $total_num_albums<br>";
            echo "num_image_results = $num_image_results<br>";
            echo "num_pages = $num_pages<br>";
            echo "size = $size<br>";
            echo "thumb_size = $thumb_size<br>";
            echo "<hr>";
        }
    }
    // total results, num to show per page, current page
    $strOutput .= $plugin_admin->get_pagination( $total_num_albums, $num_image_results, $cws_page );

    return $strOutput;
}
