<?php

add_action('admin_menu', function() {
	
	if( !is_plugin_active( 'buddypress/bp-loader.php' ) ||
			!bp_is_active( 'xprofile' ) ) {
		return;
	}
	
	$page_hook = add_users_page( _x( 'BuddyPress Extended Profile translation', 'admin page title', 'bogobp' ), 'XProfile translation', 'manage_options', 'xprofile-translation', 'bpxpt_admin_display_xprofile_translation_page' );
	
	add_action( 'admin_print_styles-' . $page_hook, function() {
		wp_enqueue_style( 'bpxpt-admin-xprofile', plugins_url( 'admin-xprofile.css', __FILE__ ) );
	});
});

function bpxpt_admin_display_xprofile_translation_page() {
	
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	if( !is_plugin_active( 'buddypress/bp-loader.php' ) ) {
		wp_die( __( 'BuddyPress must be active to translate your Extended Profiles.' ) );
	}
	
	echo '<div id="bpxpt">';
	echo '<h1>BuddyPress Extended Profile translation</h1>';

	// adapted from BP_XProfile_Field::get_children() in bp-xrofile-classes.php
	global $wpdb, $bp;
	
	$group_objects = $wpdb->get_results( "SELECT * FROM {$bp->profile->table_name_groups} ORDER BY group_order;" );
	$groups = array();
	foreach ( $group_objects as $go ) {
		$groups[$go->id]['name'] = $go->name;
		$groups[$go->id]['dsc'] = $go->description;
		$groups[$go->id]['fields'] = array();
	}
	
	$field_objects = $wpdb->get_results( "SELECT * FROM {$bp->profile->table_name_fields} ORDER BY option_order, field_order, group_id;" );
	foreach ( $field_objects as $fo ) {
		if ( $fo->parent_id == 0 ) {
			$groups[$fo->group_id]['fields'][$fo->id]['name'] = $fo->name;
			$groups[$fo->group_id]['fields'][$fo->id]['dsc'] = $fo->description;
		} else {
			$groups[$fo->group_id]['fields'][$fo->parent_id]['children'][$fo->id]['name'] = $fo->name;
			$groups[$fo->group_id]['fields'][$fo->parent_id]['children'][$fo->id]['dsc'] = $fo->description;
		}
	}

	$option = get_option( 'bpxpt_xprofile_data' );
	
	if ( isset( $_GET['save'] ) ) {
		
		if ( isset( $_POST['bpxpt_nonce'] ) && wp_verify_nonce( $_POST['bpxpt_nonce'], 'bpxpt_admin' ) ) {
			$new_option = array();
			foreach ( $_POST['bpxpt'] as $id => $translation ) {
				list( $group_id, $field_id, $parent_id, $type ) = explode( '%', $id );
				$translation = sanitize_text_field( $translation );
				if ( $translation ) {
					$new_option[$group_id][$field_id][$parent_id][$type] = $translation;
				}
			}

			$success = true;
			if ( $new_option != $option ) {
				if ( $option === false ) {
					$success = add_option( 'bpxpt_xprofile_data', $new_option, '', 'no' );
				} else {
					$success = update_option( 'bpxpt_xprofile_data', $new_option );
				}
			}
		
			$option = $new_option;
		} else {
			$success = false;
		}

		if ( $success ) {
			echo '<p class="success">Your translations have been saved.</span>';
		} else {
			echo '<p class="failure">An error occurred, please try again.</span>';
		}
	}
	
	echo '<form method="post" action="users.php?page=xprofile-translation&save=1">';
		wp_nonce_field( 'bpxpt_admin', 'bpxpt_nonce' );
		echo '<table><thead>';
			foreach ( array( true, false ) as $is_original ) {
				echo '<th>' . ( $is_original ? 'Original' : 'Translation' ) . '</th>';
			}
		echo '</thead><tbody>';
			echo '<tr class="empty"><td>&nbsp;</td></tr>';
			foreach ( $groups as $group_id => $group ) {
				bpxpt_admin_display_xprofile_item_name( $is_original, $option, $group, $group_id, 0, 0 );
				if ( isset( $group['dsc'] ) && !empty( $group['dsc'] ) ) {
					bpxpt_admin_display_xprofile_item_description( $is_original, $option, $group, $group_id, 0, 0 );
				}
				if ( !isset( $group['fields'] ) ) {
					continue;
				}
				foreach ( $group['fields'] as $field_id => $field ) {
					bpxpt_admin_display_xprofile_item_name( $is_original, $option, $field, $group_id, $field_id, 0 );
					if ( isset( $field['dsc'] ) && !empty( $field['dsc'] ) ) {
						bpxpt_admin_display_xprofile_item_description( $is_original, $option, $field, $group_id, $field_id, 0 );
					}
					if ( !isset( $field['children'] ) ) {
						continue;
					}
					foreach ( $field['children'] as $parent_id => $child ) {
						bpxpt_admin_display_xprofile_item_name( $is_original, $option, $child, $group_id, $field_id, $parent_id );
						if ( isset( $child['dsc'] ) && !empty( $child['dsc'] ) ) {
							bpxpt_admin_display_xprofile_item_description( $is_original, $option, $child, $group_id, $field_id, $parent_id );
						}
					}
				}
				echo '<tr class="empty"><td>&nbsp;</td></tr>';
			}
		echo '</tbody></table>';
		
		echo '<p class="submit"><input type="submit" class="button-primary" value="' . _x( 'Save Changes', 'admin save', 'bogobp' ) . '" /></p>';
		
	echo '</form>';
	echo '</div>';
}

function bpxpt_admin_display_xprofile_item_name( $is_original, $option, $item, $group_id, $field_id, $parent_id ) {
	echo '<tr>';
	foreach ( array( true, false ) as $is_original ) {
		echo '<td>';
		if ( $is_original ) {
			echo '<span class="' . ( $field_id == 0 ? 'group' : ( $parent_id == 0 ? 'field' : 'child' ) ) . '">' . stripslashes_deep( esc_html( $item['name'] ) ) . '</span>';
		} else {
			$val = isset( $option[$group_id][$field_id][$parent_id]['name'] ) ? $option[$group_id][$field_id][$parent_id]['name'] : '';
			echo '<input name="bpxpt[' . $group_id . '%' . $field_id . '%' . $parent_id . '%name]' . '" type="text" value="' . $val .'" />';
		}
		echo '</td>';
	}
	echo '</tr>';
}

function bpxpt_admin_display_xprofile_item_description( $is_original, $option, $item, $group_id, $field_id, $parent_id ) {
	echo '<tr>';
	foreach ( array( true, false ) as $is_original ) {
		echo '<td>';
		if ( $is_original ) {
			echo '<p class="' . ( $field_id == 0 ? 'group' : ( $parent_id == 0 ? 'field' : 'child' ) ) . '">' . stripslashes_deep( esc_html( $item['dsc'] ) ) . '</p>';
		} else {
			$val = isset( $option[$group_id][$field_id][$parent_id]['dsc'] ) ? $option[$group_id][$field_id][$parent_id]['dsc'] : '';
			echo '<textarea name="bpxpt[' . $group_id . '%' . $field_id . '%' . $parent_id . '%dsc]' . '">' . $val . '</textarea>';
		}
		echo '</td>';
	}
	echo '</tr>';
}

?>
