<?php
/**
* Plugin Name: Wordpress Json importer
* Plugin URI: http://restro.ir/
* Description: A plugin to import content from json and web-servise  
* Version: 0.1 
* Author: Mahdi Majidzadeh
* Author URI: http://restro.ir/
* License: GPLv2
*/
/*  Copyright 2015  mahdi majidzadeh  (email : mahdi.majidzadeh@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/


    if ( !defined('WP_LOAD_IMPORTERS') ) return;

    require_once ABSPATH . 'wp-admin/includes/import.php';

    if ( ! class_exists( 'WP_Importer' ) ) {
        $class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
        if ( file_exists( $class_wp_importer ) )
            require $class_wp_importer;
    }

    if ( class_exists( 'WP_Importer' ) ) {
        class json_Import extends WP_Importer {
            var $authors = array();
            var $posts = array();
            var $terms = array();
            var $categories = array();
            var $tags = array();
            var $base_url = '';

        function Json_Import() { /* nothing */ }

        function dispatch() {
            $this->header();

            $step = empty( $_GET['step'] ) ? 0 : (int) $_GET['step'];
            switch ( $step ) {
                case 0:
                $this->greet();
                break;
                case 1:
                check_admin_referer( 'import-upload' );
                if ( $this->handle_upload() )
                    $this->import_options();
                break;
                case 2:
                check_admin_referer( 'import-custom' );
                $this->fetch_attachments = ( ! empty( $_POST['fetch_attachments'] ) && $this->allow_fetch_attachments() );
                $this->id = (int) $_POST['import_id'];

                $file = get_attached_file( $this->id );
                set_time_limit(0);
                $this->import( $file );
                break;
            }
            $this->footer();
        }

        function import() {
            $this->import_posts();
            $this->process_posts();
            echo '<h3>';
            printf(__('All done. Celebrate!','custom-importer'), get_option('home'));
            echo '</h3>';
        }

        function import_posts() {
            global $wpdb;
            $index = 0;
            while ($objArticle = $arrArticles)  {
                $post['post_title'] = $wpdb->escape($objArticle->title);
                $post['post_excerpt'] = $wpdb->escape($objArticle->desc);
                $post['category'] = $this->import_category($objArticle->cat);
                $post['post_author'] = $this->import_author($objArticle->author);
                $this->posts[$index] = $post;
                $index++;
            }
        }

        function import_author() { 
            global $wpdb;
            while ($objAuthor = $arrAuthors) {
                $user_data = array(
                    'user_login'       => sanitize_user($objAuthor->login, true),
                    'user_pass'        => wp_generate_password(),
                    'user_url'         => '',
                    'user_email'       => $wpdb->escape($objAuthor->email),
                    'display_name'     => $wpdb->escape($objAuthor->full_name),
                    'first_name'       => $wpdb->escape($objAuthor->first_name),
                    'last_name'        => $wpdb->escape($objAuthor->last_name),
                    'description'      => '',
                    );

                $user = $this->authors[$user_data['user_login']];
                if ($user)
                    return $user->ID;
                $user = get_user_by('email', $user_data['user_email']);
                if ($user)
                    return $user->ID;

                $user_id = wp_insert_user( $user_data );

                if ( ! is_wp_error( $user_id ) ) {
                    $this->authors[$user_data['user_login']] = get_userdata($user_id);
                    return trim($user_id);
                } else {
                    if ($user_id->get_error_code() == 'existing_user_login') {
                        $user = get_user_by('email', $user_data['user_email']);
                        return trim($user->ID);
                    } else {
                        printf( __( 'Failed to create new user for %s. Their posts will be attributed to the admin.', 'custom-importer' ), esc_html($user_data['display_name']) );
                        if ( defined('IMPORT_DEBUG') && IMPORT_DEBUG )
                            echo ' ' . $user_id->get_error_message().' ('.$user_id->get_error_code().')';
                        echo '<br />';
                    }
                }
            }
        }

        function process_posts() {
            foreach ($this->posts as $post) {
                echo "<li>".__('Importing post...', 'custom-importer');
                extract($post);

                if ($post_id = post_exists($post_title, $post_content, $post_date)) {
                    _e('Post already imported', 'custom-importer');
                } else {
                    $post_id = wp_insert_post($post);
                    if ( is_wp_error( $post_id ) )
                        return $post_id;
                    if (!$post_id) {
                        _e('Couldn’t get post ID', 'custom-importer');
                        return;
                    } 
                    wp_create_categories(array('Category1','Category2'), $post_id);
                    _e('Done!', 'custom-importer');
                }
                echo '</li>';
            }
        }
        function header() {
            echo '<div class="wrap">';
            screen_icon();
            echo '<h2>Import json to WP</h2>';

            $updates = get_plugin_updates();
            $basename = plugin_basename(__FILE__);
            if ( isset( $updates[$basename] ) ) {
                $update = $updates[$basename];
                echo '<div class="error"><p><strong>';
                printf('A new version of this importer is available. Please update to version %s to ensure compatibility with newer export files.',$update->update->new_version );
                echo '</strong></p></div>';
            }
        }

        // Close div.wrap
        function footer() {
            echo '</div>';
        }

        function greet() {
            echo '<div class="narrow">'; ?>
            <p>enter url of json to import</p>
            <form action="admin.php?import=json&amp;step=1" method="post">
                <label>url:</label><input type="url" name="wpji-url"><br>
                <input type="submit" value="Go!">
            </form>
            </div>
            <?php
        }
    }
}
$json_import = new json_Import();
register_importer('json','json','Import posts from json url.', array ($json_import, 'dispatch'));


// if (!function_exists('is_admin')) {
//  header('Status: 403 Forbidden');
//  header('HTTP/1.1 403 Forbidden');
//  exit();
// }
// add_action('admin_menu', 'wpji_plugin_settings');


// function wpji_plugin_settings() {

//      //add_menu_page('Json importer', 'Json importer', 'administrator', 'wpji_settings', 'fwds_display_settings','dashicons-admin-post',81);
//  add_submenu_page( 'options-general.php', 'Json importer', 'Json importer', 'administrator', 'wpji_settings', 'wpji_settings');
//  add_submenu_page( 'options-general.php', 'Json importer', '', 'administrator', 'wpji_done', '_wpji_done');
//      //add_dashboard_page( 'Json importer', '', 'administrator', 'wpji_done', '_wpji_do');

// }

// function wpji_settings(){

//  $html = '</pre>
//  <div class="wrap"><form action="'.admin_url( 'options-general.php?page=wpji_done').'" method="post">
//  <h2>Wordpress Json importer</h2>
//  <table class="form-table" width="100%" cellpadding="10">
//  <tbody>
//  <tr>
//  <td scope="row">
//  <label>Json URL</label><input type="text" name="url" value="" /></td>
//  </tr>
//  <tr>
//  <td scope="row">
//  <label>json node</label><input type="text" name="node" value="" /></td>
//  </tr>
//  <tr>
//  <td scope="row">
//  <label>post title</label><input type="text" name="title" value="" /></td>
//  </tr>
//  <tr>
//  <td scope="row">
//  <label>post content</label><input type="text" name="content" value="" /></td>
//  </tr>
//  </tbody>
//  </table>
//  <input type="submit" name="Submit" value="insert" /></form></div>
//  <pre>
//  ';

//  echo $html;
// }


// function _wpji_done(){
//  echo '</pre>
//  <div class="wrap"><h2>Done</h2>';
//  if( (isset($_POST['url'])) && (isset($_POST['node'])) && (isset($_POST['title'])) && (isset($_POST['content'])) ){
//      $json = json_decode(file_get_contents($_POST['url']),true);

//      $node = $_POST['node'];
//      $title = $_POST['title'];
//      $content = $_POST['content'];
//      foreach ($json as $row) {
//          $post = array(
//            'post_content'   => $row[$node][$content], // The full text of the post.
//            //'post_name'      => [ <string> ] // The name (slug) for your post
//            'post_title'     => $row[$node][$title], // The title of your post.
//            'post_status'    => 'publish',//[ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ] // Default 'draft'.
//            'post_type'      => 'post',//[ 'post' | 'page' | 'link' | 'nav_menu_item' | custom post type ] // Default 'post'.
//            //'post_author'    => [ <user ID> ] // The user ID number of the author. Default is the current user ID.
//            //'ping_status'    => [ 'closed' | 'open' ] // Pingbacks or trackbacks allowed. Default is the option 'default_ping_status'.
//            //'post_parent'    => [ <post ID> ] // Sets the parent of the new post, if any. Default 0.
//            //'menu_order'     => [ <order> ] // If new post is a page, sets the order in which it should appear in supported menus. Default 0.
//            //'to_ping'        => // Space or carriage return-separated list of URLs to ping. Default empty string.
//            //'pinged'         => // Space or carriage return-separated list of URLs that have been pinged. Default empty string.
//            //'post_password'  => [ <string> ] // Password for post, if any. Default empty string.
//            //'guid'           => // Skip this and let Wordpress handle it, usually.
//            //'post_content_filtered' => // Skip this and let Wordpress handle it, usually.
//            //'post_excerpt'   => [ <string> ] // For all your post excerpt needs.
//            //'post_date'      => [ Y-m-d H:i:s ] // The time post was made.
//            //'post_date_gmt'  => [ Y-m-d H:i:s ] // The time post was made, in GMT.
//            //'comment_status' => [ 'closed' | 'open' ] // Default is the option 'default_comment_status', or 'closed'.
//            //'post_category'  => [ array(<category id>, ...) ] // Default empty.
//            //'tags_input'     => [ '<tag>, <tag>, ...' | array ] // Default empty.
//            //'tax_input'      => [ array( <taxonomy> => <array | string>, <taxonomy_other> => <array | string> ) ] // For custom taxonomies. Default empty.
//            //'page_template'  => [ <string> ] // Requires name of template file, eg template.php. Default empty.
//            );  
// if(wp_insert_post( $post, $wp_error )){
//  echo $row[$node][$title].' inserted <br>';
// }
// else{
//  echo 'failed to insert <br>';
// }
// }
// }
// else{
//  echo 'you must fill all field';
// }    
// echo '</div><pre>';
// }

