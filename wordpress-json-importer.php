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

/*
* Function to display admin menu.
*/


if (!function_exists('is_admin')) {
	header('Status: 403 Forbidden');
	header('HTTP/1.1 403 Forbidden');
	exit();
}
add_action('admin_menu', 'wpji_plugin_settings');


function wpji_plugin_settings() {

		//add_menu_page('Json importer', 'Json importer', 'administrator', 'wpji_settings', 'fwds_display_settings','dashicons-admin-post',81);
	add_submenu_page( 'options-general.php', 'Json importer', 'Json importer', 'administrator', 'wpji_settings', 'wpji_settings');
	add_submenu_page( 'options-general.php', 'Json importer', '', 'administrator', 'wpji_done', '_wpji_done');
		//add_dashboard_page( 'Json importer', '', 'administrator', 'wpji_done', '_wpji_do');

}

function wpji_settings(){

	$html = '</pre>
	<div class="wrap"><form action="'.admin_url( 'options-general.php?page=wpji_done').'" method="post">
	<h2>Wordpress Json importer</h2>
	<table class="form-table" width="100%" cellpadding="10">
	<tbody>
	<tr>
	<td scope="row">
	<label>Json URL</label><input type="text" name="url" value="" /></td>
	</tr>
	<tr>
	<td scope="row">
	<label>json node</label><input type="text" name="node" value="" /></td>
	</tr>
	<tr>
	<td scope="row">
	<label>post title</label><input type="text" name="title" value="" /></td>
	</tr>
	<tr>
	<td scope="row">
	<label>post content</label><input type="text" name="content" value="" /></td>
	</tr>
	</tbody>
	</table>
	<input type="submit" name="Submit" value="insert" /></form></div>
	<pre>
	';

	echo $html;
}


function _wpji_done(){
	echo '</pre>
	<div class="wrap"><h2>Done</h2>';
	if( (isset($_POST['url'])) && (isset($_POST['node'])) && (isset($_POST['title'])) && (isset($_POST['content'])) ){
		$json = json_decode(file_get_contents($_POST['url']),true);

		$node = $_POST['node'];
		$title = $_POST['title'];
		$content = $_POST['content'];
		foreach ($json as $row) {
			$post = array(
			  'post_content'   => $row[$node][$content], // The full text of the post.
			  //'post_name'      => [ <string> ] // The name (slug) for your post
			  'post_title'     => $row[$node][$title], // The title of your post.
			  'post_status'    => 'publish',//[ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ] // Default 'draft'.
			  'post_type'      => 'post',//[ 'post' | 'page' | 'link' | 'nav_menu_item' | custom post type ] // Default 'post'.
			  //'post_author'    => [ <user ID> ] // The user ID number of the author. Default is the current user ID.
			  //'ping_status'    => [ 'closed' | 'open' ] // Pingbacks or trackbacks allowed. Default is the option 'default_ping_status'.
			  //'post_parent'    => [ <post ID> ] // Sets the parent of the new post, if any. Default 0.
			  //'menu_order'     => [ <order> ] // If new post is a page, sets the order in which it should appear in supported menus. Default 0.
			  //'to_ping'        => // Space or carriage return-separated list of URLs to ping. Default empty string.
			  //'pinged'         => // Space or carriage return-separated list of URLs that have been pinged. Default empty string.
			  //'post_password'  => [ <string> ] // Password for post, if any. Default empty string.
			  //'guid'           => // Skip this and let Wordpress handle it, usually.
			  //'post_content_filtered' => // Skip this and let Wordpress handle it, usually.
			  //'post_excerpt'   => [ <string> ] // For all your post excerpt needs.
			  //'post_date'      => [ Y-m-d H:i:s ] // The time post was made.
			  //'post_date_gmt'  => [ Y-m-d H:i:s ] // The time post was made, in GMT.
			  //'comment_status' => [ 'closed' | 'open' ] // Default is the option 'default_comment_status', or 'closed'.
			  //'post_category'  => [ array(<category id>, ...) ] // Default empty.
			  //'tags_input'     => [ '<tag>, <tag>, ...' | array ] // Default empty.
			  //'tax_input'      => [ array( <taxonomy> => <array | string>, <taxonomy_other> => <array | string> ) ] // For custom taxonomies. Default empty.
			  //'page_template'  => [ <string> ] // Requires name of template file, eg template.php. Default empty.
			  );  
			if(wp_insert_post( $post, $wp_error )){
				echo $row[$node][$title].' inserted <br>';
			}
			else{
				echo 'failed to insert <br>';
			}
		}
	}
	else{
		echo 'you must fill all field';
	}	
echo '</div><pre>';
}

