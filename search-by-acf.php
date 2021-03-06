<?php
/*
Plugin Name: SACF:Search by Advanced Custom Fields
Plugin URI:
Description: Search plugin for ACF.
Version: 1.2
Author: Tadahiko Suzuki
Author URI: http://suzukitadahiko.jp
License: GPLv2 or later
*/

/*
Copyright 2018 Tadahiko Suzuki. (http://suzukitadahiko.jp)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301, USA
*/


////////////////////////////////////
// shortcodes
////////////////////////////////////

/**
 * shortcode: sacf_show
 */
add_shortcode('sacf_show', 'sacf_show');
function sacf_show($attr) {
  if (isset($attr[0])) $attr['field'] = $attr[0];
  $field = get_field($attr['field']);
  if ($field) {
    if( is_array($field) ) {
      $field = @implode( ', ',$field );
    }
    return $field;
  }
}

/**
 * shortcode: sacf_show_jpnumber
 */
add_shortcode('sacf_show_jpnumber', 'sacf_show_jpnumber');
function sacf_show_jpnumber($attr) {
  $number = sacf_show($attr);
  return jpnumber_format($number);
}

/**
 * shordcode: sacf_show_boolean
 */
add_shortcode('sacf_show_boolean', 'sacf_show_boolean');
function sacf_show_boolean($attr) {
  if (sacf_show($attr) === true) {
    if (isset($attr[0])) $attr['field'] = $attr[0];
    $field = get_field($attr['field']);
    return $field['message']; 
  }
  return ""; 
}

/**
 * shortcode: sacf_form
 */
add_shortcode('sacf_form', 'sacf_form');
function sacf_form($attr, $content=null) {
  if (isset($attr[0])) $attr['group'] = $attr[0];
  global $sacf_form_state;
  $sacf_form_state = new Sacf_form_state($attr['group']);

  $converted_content = do_shortcode($content);

  // add hidden input element when no input element has attribute named "s".
  $dom = new DOMDocument;
  @$dom->loadHTML($converted_content);
  $xpath = new DOMXPath($dom);
  $result = $xpath->query('//input[@name = "s"]');
  if ($result->length == 0) {
    $converted_content = "<input type=\"hidden\" name=\"s\" value=\"\">" . $converted_content;
  }

  return sacf_form_start($attr) . $converted_content . sacf_form_end();
}
function sacf_form_start($attr) {
  global $sacf_form_state;
  $action_uri = home_url('/');
  return "<form name=\"sacf_form_{$sacf_form_state->get_group_id()}\" action=\"$action_uri\" class=\"sacf_form sacf_form_{$sacf_form_state->get_group_id()}\">";
}
function sacf_form_end() {
  global $sacf_form_state;
  $sacf_form_state = null;
  return "</form>";
}

/**
 * shortcode: sacf_submit
 */
add_shortcode('sacf_submit', 'sacf_submit');
function sacf_submit($attr) {
  if (isset($attr[0])) $attr['value'] = $attr[0];
  return "<input type=\"submit\" value=\"${attr['value']}\" class=\"sacf_submit\">";
}

/**
 * shortcode: sacf_input_text
 */
add_shortcode('sacf_input_text', 'sacf_input_text');
function sacf_input_text($attr) {
  if (isset($attr[0])) $attr['field'] = $attr[0];
  return "<input type=\"text\" name=\"sacf[${attr['field']}_like]\" class=\"sacf_input_text ${attr['field']}\">";
}

/**
 * shortcode: sacf_input_checkbox
 */
add_shortcode('sacf_input_checkbox', 'sacf_input_checkbox');
function sacf_input_checkbox($attr) {
  global $sacf_form_state;
  if (isset($attr[0])) $attr['field'] = $attr[0];
  $html = "";
  $field = get_fields_options($sacf_form_state->get_group_id(), $attr['field']);
  foreach ($field['choices'] as $value => $label) {
    $html .= "<label class=\"sacf_input_checkbox_label ${attr['field']}\">";
    $html .= "<input type=\"checkbox\" name=\"sacf[${attr['field']}][]\" value=\"${value}\" class=\"sacf_input_checkbox ${attr['field']}\">";
    $html .= "<span class=\"sacf_input_checkbox_text ${attr['field']}\">${label}</span>";
    $html .= "</label>";
  }
  return $html;
}

/**
 * shortcode: ascf_input_radio
 */
add_shortcode('sacf_input_radio', 'sacf_input_radio');
function sacf_input_radio($attr) {
  global $sacf_form_state;
  if (isset($attr[0])) $attr['field'] = $attr[0];
  $html = "";
  $field = get_fields_options($sacf_form_state->get_group_id(), $attr['field']);
  foreach ($field['choices'] as $value => $label) {
    $html .= "<label class=\"sacf_input_radio_label ${attr['field']}\">";
    $html .= "<input type=\"radio\" name=\"sacf[${attr['field']}]\" value=\"${value}\" class=\"sacf_input_radio ${attr['field']}\">";
    $html .= "<span class=\"sacf_input_radio_text ${attr['field']}\">${label}</span>";
    $html .= "</label>";
  }
  return $html;
}

/**
 * shortcode: sacf_select
 */
add_shortcode('sacf_select', 'sacf_select');
function sacf_select($attr) {
  global $sacf_form_state;
  if (isset($attr[0])) $attr['field'] = $attr[0];
  $html = "<select name=\"sacf[${attr['field']}]\" class=\"sacf_select ${attr['field']}\">";
  $field = get_fields_options($sacf_form_state->get_group_id(), $attr['field']);
  foreach ($field['choices'] as $value => $label) {
    $html .= "<option value=\"${value}\">${label}</option>";
  }
  $html .= "</select>";
  return $html;
}

/**
 * shortcode: sacf_boolean
 */
add_shortcode('sacf_boolean', 'sacf_boolean');
function sacf_boolean($attr) {
  global $sacf_form_state;
  if (isset($attr[0])) $attr['field'] = $attr[0];
  $field = get_fields_options($sacf_form_state->get_group_id(), $attr['field']);
  $label = $field['message'];
  $html  = "<label class=\"sacf_input_checkbox_label ${attr['field']}\">";
  $html .= "<input type=\"checkbox\" name=\"sacf[${attr['field']}]\" value=\"1\" class=\"sacf_input_checkbox ${attr['field']}\">";
  $html .= "<span class=\"sacf_input_checkbox_text ${attr['field']}\">${label}</span>";
  $html .= "</label>";
  return $html;
}

/**
 * shorcode: c (comment out)
 */
add_shortcode('c', 'comment_out');
function comment_out($attr, $content=null) {
  return "";
}


////////////////////////////////////
// logics
////////////////////////////////////

/**
 * custom_search 
 */
add_filter('posts_search','custom_search', 10, 2);
function custom_search($search, $wp_query) {
  if (isset($wp_query->query['s'])) $wp_query->is_search = true;  
  if (!$wp_query->is_search) return;
  $search .= " AND post_type = 'post'";
  return $search;
}

/**
 * custom_search_join
 */
add_filter( 'posts_join', 'custom_search_join' );
function custom_search_join($join){
  // search by "sacf" field
  if (!empty($_REQUEST['sacf'])) {
    $sacf_options = $_REQUEST['sacf'];
    $i = 0;
    foreach ($sacf_options as $field_name => $field_value) {
      $condition = "";
      $isLike = false;
      if (preg_match("/_like$/",$field_name)){
        $field_name = mb_substr($field_name, 0, -5);
        $isLike = true;
      }

      if (is_array($field_value)) {
        // multiple value (=checkbox)
        $condition .= " AND (";
        $j = 0;
        foreach ($field_value as $field_item) {
          $condition .= ($j > 0) ? "AND " : "";
          $condition .= " (wp_postmeta_" . $i . ".meta_key = '" . esc_sql($field_name) . "'"; 
          $condition .= " AND wp_postmeta_" . $i . ".meta_value LIKE '%\"" . esc_sql($field_item) . "\"%') ";
          $j++;
        }
        $condition .= ")"; 

      } else {
        // single value
        $condition .= " AND wp_postmeta_" . $i . ".meta_key = '" . esc_sql($field_name) . "'"; 
        if ($isLike) {
          // partial match
          $condition .= " AND wp_postmeta_" . $i . ".meta_value LIKE '%" . esc_sql($field_value) . "%'";
        } else {
          // perfect match
          $condition .= " AND wp_postmeta_" . $i . ".meta_value = '" . esc_sql($field_value) . "'"; 
        }
      }
      $join .= " INNER JOIN wp_postmeta AS wp_postmeta_" . $i . " ON (wp_posts.ID = wp_postmeta_" . $i . ".post_id " . $condition . ")";
      $i++;
    }
  }
  return $join;
}

/**
 * get field's options
 */
function get_fields_options($group_id, $field_name) {
  $acf_post = get_post_custom($group_id);
  foreach ($acf_post as $key => $arr) {
    if (preg_match( "/^field_/", $key)) {
      $arr = unserialize($arr[0]);
      if ($arr['name'] === $field_name) {
        return $arr;
      }
    }
  }
  return null;
}

/**
 * get_acf_all_fields_in_group (*for debug)
 */
function get_acf_all_fields_in_group($group_id) {
  $acf_post_custom = get_post_custom($group_id);
  $acf_fields_object = array();
  foreach ($acf_post_custom as $key => $arr) {
    if (preg_match( "/^field_/", $key)) {
      $arr = unserialize($arr[0]);
      $field_name = $arr['name'];
      $acf_fields_object[$field_name] = $arr;
    }
  }
  return $acf_fields_object;
}


////////////////////////////////////
// classes
////////////////////////////////////

/**
 * form state
 */
class Sacf_form_state {
  /**
   * ACF's group id.
   */
  private $group_id;

  public function __construct($group_id) {
    $this->group_id = $group_id;
  }

  public function get_group_id() {
    return $this->group_id;
  }

  public function __destruct() {
    $this->group_id = null;
  }
}
global $sacf_form_state;

////////////////////////////////////
// utilities
////////////////////////////////////

/**
 * jpnumber format 
 */
function jpnumber_format($int){
  if (!is_numeric($int)) return $int;

  $unit = array('万', '億', '兆', '京');
  krsort($unit);
  $tmp = '';
  $count = strlen($int);
  foreach($unit as $k => $v){
    if ($count > (4 * ($k + 1))){
      if ($int !== 0) $tmp .= number_format(floor($int / pow(10000, $k + 1))) . $v;
      $int = $int % pow(10000, $k + 1);
    }
  }
  if ($int !== 0) $tmp .= number_format($int % pow(10000, $k + 1));
  return $tmp;
}

// display group id on ACF groups list.
function add_posts_columns_postid($columns) {
  $columns['postid'] = 'ID';
  return $columns;
}
function add_posts_columns_postid_row($column_name, $post_id) {
  if ('postid' == $column_name) {
    echo $post_id;
  }
}
add_filter('manage_edit-acf_columns', 'add_posts_columns_postid', 20);
add_action('manage_acf_posts_custom_column', 'add_posts_columns_postid_row', 20, 2);

?>
