<?php
/**
 *
 * Plugin Name: Bulk YouTube Post Creator
 * Description: Quickly create multiple posts at once and embed YouTube into them
 * Version: 1.0
 * Author: Syed Tahir Ali Jan
 * Author URI: https://www.facebook.com/stahirjan
 * 
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as 
 * published by the Free Software Foundation.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * ---------------------------------------------------------------------------- */


class YouTube_Post_Embeder
{
	var $slug = 'youtube_post_embeder';

	function __construct() 
	{
		add_action('admin_menu', array($this, 'create_page'));
	} // end constructor

	function create_page() 
	{
		add_menu_page('Bulk YouTube Post Creator', 
							'Bulk YouTube Post Creator', 
							'manage_options', 
							$this->slug , 
							array($this, 'show_page'));
	} // end create_page()

	function pt_select() 
	{
		$types = get_post_types(array('public' => true));
		$out = '';
		foreach( $types as $k => $v ) 
		{
			if($v != 'attachment')
				$out .= '<option value="' . $k . '">' . $v . '</option>';
		} // end foreach
		return $out;
	} // end pt_select()

	function status_select() 
	{
		$types = array(
					"publish" => "Published" ,
					"pending" => "Pending Review" ,
					"draft" => "Draft" ,
		);
		$out = '';

		foreach( $types as $k => $v ) 
		{
			$out .= '<option value="' . $k . '">' . $v . '</option>';
		} // end foreach
		return $out;
	} // end status_select()


	function get_post_categories_dropdown($name)
	{
		$str = "";
		$str .= '<select name="'.$name.'">';
		$category_ids = get_all_category_ids();
		foreach($category_ids as $cat_id) 
		{
	  		$cat_name = get_cat_name($cat_id);
	  		$str .= '<option value="' . $cat_id . '">'. $cat_name . '</option>';
		}
		$str .= '</select>';
		return $str;
	} // end get_post_categories_dropdown($name)

	function show_page() 
	{
		if( isset($_POST['ve_set']) && $_POST['ve_set']=='set' )
		{
			echo "<div id='message' class='updated'>";
			foreach( $_POST['ve_post'] as $new ) 
			{
				if(! empty($new['name'])) 
				{
					$menu_order = $new['menu_order'] ? $new['menu_order'] : 0;
					$post_parent = $new['post_parent'] ? $new['post_parent'] : 0;

					$params = array( 
						'post_type' => $new['type'],
						'post_title' => $new['name'],
						'post_parent' => $post_parent,
						'menu_order' => $menu_order,
						'post_status' => $new['post_status'],
						'post_content' => $new['content'],
					);

					global $wpdb;
					$new_id = wp_insert_post($params);
					wp_set_post_terms($new_id, $new['post_category'], 'category'); // associate with category

					if($new_id && ! empty($new['thumbnail'])) 
					{
						update_post_meta($new_id, '_thumbnail_id', $new['thumbnail']);
						$id = wp_update_post(array('ID' => $new['thumbnail'], 'post_parent' => $new_id), true);
					} // end if
					
					if($new_id) 
					{
						printf(' <p> Added new %s: <a href="%s">%s</a> </p> ', 
								$new["type"], 
								get_edit_post_link($new_id), 
								$new["name"]
						);
					} // end if
				} // end if()
			} // end foreach
			echo "</div>";
			//form submitted
		} // end if()
		?>
		<style>
			.ve_table 
			{
				width:100%;
			}
			.ve_table td 
			{
				vertical-align:top;
			}
			#message 
			{
				margin-left:0;
			}
		</style>
		<h1>Bulk YouTube Post Creator</h2>
		<p>You can embed multiple videos at once, please fill the following appropriately.</p>
		<form action="<?php bloginfo('wpurl') ?>/wp-admin/tools.php?page=<?php echo $this->slug ?>" method="post">
			<input type="hidden" name="ve_set" value="set" />
			<table class="ve_table">
				<thead>
					<tr>
						<th width="20%">Name of the Post</th>
						<th>Paste YouTube embed code here</th>
						<th width="10%">Post Status</th>
						<th width="10%">Category</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><input class="widefat" type="text" name="ve_post[post_1][name]" value=""  /></td>
						<input type="hidden" name="ve_post[post_1][type]" value="post">
						<td><textarea class="widefat" name="ve_post[post_1][content]"></textarea></td>
						<td><select class="widefat pt-select" name="ve_post[post_1][post_status]"><?php echo $this->status_select() ?></select></td>
						<td><?php echo $this->get_post_categories_dropdown("ve_post[post_1][post_category]"); ?></td>
						<td><span class="button secondary ve_rm">Remove</span></td>
					</tr>
				</tbody>
			</table>      
			<p><span class="button secondary ve_add">+ Add Row</span></p>
			<p><input type="submit" value="Submit" class="button-primary" /></p>
		</form>
		<script type="text/javascript">
			jQuery(document).ready(function($)
			{
				(function()
				{
					function ve_renumber() 
					{
						var start = 1;
						$('.ve_table tbody tr').each(function(i, el)
						{
							var repl = 'post_' + start ;
							$(this).find('input, select, textarea').each(function(i, el)
							{
								var name = $(this).attr('name'),
								n = name.replace(/post_[0-9]+/, repl) ;
								$(this).attr('name', n);
							});
							start += 1;
						});
					}
					
					function set_all() 
					{
						var $sel = $(this),
						val = $sel.val() ;
						
						$('.pt-select').val( val );
					}
					
					$('.ve_add').live('click', function() 
					{
						$('.ve_table tbody').append('<tr>' + $('.ve_table tbody tr').slice(-1).html() + '</tr>');
						ve_renumber();
					});

					$('.ve_rm').live('click', function()
					{
						if($('.ve_rm').length > 1) 
						{
							$(this).parent().parent().remove();
						}
					});

					$('#set_all').change( set_all );

				})(this.jQuery);
			});
		</script>
		<?php
	} // end show_page() 
} // end class YouTube_Post_Embeder

new YouTube_Post_Embeder();
