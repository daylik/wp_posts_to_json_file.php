<?php
/*
Plugin Name: Json to file
Plugin URI: http://dayl.ru/plugins/to_json_file
Description: Сохранение записей по категориям прямо в файл в корень сайта в json формате для jquery и angular.
Version: 1.0
Author: Олег Мешаев
Author URI: http://dayl.ru/
*/
/*  Copyright 2016 Олег Мешаев  (email: daylike@yandex.ru)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
*/
defined( 'ABSPATH' ) or die( 'alert!' );

add_action('admin_menu', 'jtof_Menu');
add_action('admin_init', 'jtof_Script');


function jtof_Script(){
  //wp_register_script('daylike-to-json-file', plugins_url('/to_json_file_view.js', __FILE__), array('jquery') );
  //wp_enqueue_script('daylike-to-json-file');
}

function jtof_Menu(){
  add_menu_page("json to file plugin", "Сохранить json в файл", "delete_pages", "json-to-file", "jsonToFile", "dashicons-download");
}

function likeDateFormate($str){
  return preg_replace('/(\d+)-(\d+)-(\d+)\s+\d+:\d+:\d+/', "$3.$2.$1", $str);
}

function likeTimeFormate($str){
  return preg_replace('/\d+-\d+-\d+\s+(\d+):(\d+):(\d+)/', "$1:$2:$3", $str);
}

function getjson(){
  $text_1 = array("number" => "1");
  $file = '../config.json';
  $json = file_get_contents( $file );
  $json_arr = json_decode( $json, true );

  echo '<pre>';
  var_dump($json_arr);
  echo '</pre>';

  $json_arr[] = $text_1;

  $json_output = json_encode( $json_arr );
  file_put_contents($file, $json_output );
}


function jsonToFile( $category_id ){

  getjson(); //проверка config.json в корне сайта!

  global $wpdb, $wp_query;

  $getCatID = $wpdb->get_results("SELECT * FROM $wpdb->term_taxonomy WHERE taxonomy='category' AND term_taxonomy_id IN (SELECT term_taxonomy_id FROM $wpdb->term_relationships WHERE object_id LIKE '$category_id')");
  $cat_ID = $getCatID[0];

  if( isset($cat_ID->term_taxonomy_id) ){
    $like = $cat_ID->term_taxonomy_id;
  } else {
    $like = 1;
  }

  $sqlArr = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_status='publish' AND post_type='post' AND id IN (SELECT object_id FROM $wpdb->term_relationships WHERE term_taxonomy_id=(SELECT term_id FROM $wpdb->terms WHERE term_id LIKE '$like')) ORDER BY post_date DESC");

  $result_terms = $wpdb->get_results("SELECT * FROM $wpdb->terms WHERE term_id='$like'");
  $category = $result_terms[0];
  $br = "\n";
  $count = 1;

  $jsonPosts = '{ "posts" : ['. $br;

  foreach($sqlArr as $row){
    if( $count > 1 ){
      $jsonPosts .= ','.$br;
    }

    $jsonPosts .= '{'.$br;
    $jsonPosts .= '"count" : '. $count .', '.$br;
    $jsonPosts .= '"id" : '. $row->ID .', '.$br;
    $jsonPosts .= '"date" : "'. likeDateFormate($row->post_date) .'", '.$br;
    $jsonPosts .= '"time" : "'. likeTimeFormate($row->post_date) .'", '.$br;
    $jsonPosts .= '"category" : "'. $category->slug .'", '.$br;
    $jsonPosts .= '"category_name" : "'. $category->name .'", '.$br;
    $row_title = addslashes( $row->post_title );
    $jsonPosts .= '"title" : "'. $row_title .'", '.$br;
    $jsonPosts .= '"url" : "/'. $category->slug .'/'. $row->post_name .'.html", '.$br;
    $jsonPosts .= '}';
    $count++;
   }
  $jsonPosts .= $br .']'. $br .'}'. $br;

  if( isset( $category->slug ) ){
    $catName = $category->slug;
  } else {
    $catName = '';
  }
    //запись в файл
    $jsonFile = '../json-file'. $catName .'.json';
    //header('Content-type: application/json');
    $fp = fopen( $jsonFile, "w" );
    fwrite( $fp, b"\xEF\xBB\xBF" . $jsonPosts);
    fclose( $fp );
}
//add_action('save_post', 'jsonToFile');

?>
