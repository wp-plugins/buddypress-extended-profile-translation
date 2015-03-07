<?php
/*
Plugin Name: BuddyPress Extended Profile Translation
Description: Multilingual Extended Profiles in multisite BuddyPress
Plugin URI: http://wordpress.org/extend/plugins/buddypress-extended-profile-translation/
Author: Markus Echterhoff
Author URI: http://www.markusechterhoff.com
Version: 1.0
License: GPLv3 or later
*/

require_once( 'includes/admin-xprofile.php' );

add_filter( 'bp_xprofile_get_groups', 'bpxpt_translate_xprofile_groups' );
function bpxpt_translate_xprofile_groups( $groups ) {
	
	// leave them in default language in admin screen, no matter the currently active locale
	if ( is_admin() ) {
		return $groups;
	}

	$option = get_option( 'bpxpt_xprofile_data' );
	if ( $option  === false ) {
		return $groups;
	}

	$locale = get_locale();

	foreach ( $groups as $group ) {
		if ( isset( $option[$group->id][0][0]['name'] ) ) {
			$group->name = $option[$group->id][0][0]['name'];
		}
		if ( isset( $option[$group->id][0][0]['dsc'] ) ) {
			$group->description = $option[$group->id][0][0]['dsc'];
		}
		foreach ( $group->fields as $field ) {
			if ( isset( $option[$group->id][$field->id][0] ) ) {
				$field->name = $option[$group->id][$field->id][0]['name'];
			}
			if ( isset( $option[$group->id][$field->id][0]['dsc'] ) ) {
				$field->description = $option[$group->id][$field->id][0]['dsc'];
			}
		}
	}

	return $groups;
}

add_filter( 'bp_xprofile_field_get_children', 'bpxpt_translate_xprofile_children' );
function bpxpt_translate_xprofile_children( $children ) {

	$option = get_option( 'bpxpt_xprofile_data' );
	if ( $option  === false ) {
		return $children;
	}
	
	$locale = get_locale();

	foreach ( $children as $child ) {
		if ( isset( $option[$child->group_id][$child->parent_id][$child->id]['name'] ) ) {
			$child->name = $option[$child->group_id][$child->parent_id][$child->id]['name'];
		}
	}
	
	return $children;
}
