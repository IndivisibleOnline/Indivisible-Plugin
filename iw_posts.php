<?php

function filter_recent_posts_widget_parameters( $params ) {
global $post;
$cats = wp_get_post_categories($post->ID); 

$params = array('cat' =>  $cats);
   return $params;
}

add_filter( 'widget_posts_args', 'filter_recent_posts_widget_parameters' );



function list_related_posts(){
ob_start();
  echo '<div class="relatedposts"> <h3>Related posts</h3>';
  $orig_post = $post;
  global $post;
  $tags = wp_get_post_categories($post->ID);
   
  if ($tags) {
  $tag_ids = array();
  foreach($tags as $individual_tag) $tag_ids[] = $individual_tag->term_id;
  $args=array(
  'cat' => $tag_ids,
  'post__not_in' => array($post->ID),
  'posts_per_page'=>6, // Number of related posts to display.
  'caller_get_posts'=>1
  );
   
  $my_query = new wp_query( $args );
 
  while( $my_query->have_posts() ) {
  $my_query->the_post();
 
   echo '<a href="' .  the_permalink() . '">' . the_title() . '</a><br>' . the_post_thumbnail(array(60,60)) . '<br>';

   
  }
  }
  $post = $orig_post;
  wp_reset_query();

  echo '</div>';
$output = ob_get_contents();
ob_end_clean();
return $output;
}
