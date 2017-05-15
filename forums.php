<?php

// add_filter( 'bbp_before_list_forums_parse_args', 'restrict_forums_to_group' );

add_shortcode('iw_groupforums','restrict_forums_to_group');

function get_forum_id(){

//	$cid = get_current_group();
	$cid = get_group_info();
	$pods = pods('topic_groups', $cid['id']);
	$fpod = $pods->field('forum');
	$forumid = implode(",",$fpod);
// echo "forumid = ". $forumid;
return $forumid;

}

function restrict_forums_to_group(){

$forumid = get_forum_id();

$params = array (
        'before'              => '<ul class="bbp-forums-list">',
        'after'               => '</ul>',
        'link_before'         => '<li class="bbp-forum">',
        'link_after'          => '</li>',
        'forum_id'            => $forumid,
        'show_topic_count'    => false,
        'show_reply_count'    => false,
        );

ob_start();
echo do_shortcode('[bbp-single-forum id=' . $forumid . ']');
ob_flush();



}

function widget_forums(){

$forumid = get_forum_id();

$params = array (
        'before'              => '<ul class="bbp-forums-list">',
        'after'               => '</ul>',
        'link_before'         => '<li class="bbp-forum">',
        'link_after'          => '</li>',
        'forum_id'            => $forumid,
        'show_topic_count'    => false,
        'show_reply_count'    => false,
        );

 bbp_list_forums($params);

}



// Creating the widget 
class forum_widget extends WP_Widget {

function __construct() {
parent::__construct(
// Base ID of your widget
'forum_widget', 

// Widget name will appear in UI
__('IW Workgroup Forums List', 'forum_widget_domain'), 

// Widget description
array( 'description' => __( 'List IW Workgroup Forums', 'forum_widget_domain' ), ) 
);
}

// Creating widget front-end
// This is where the action happens
public function widget( $args, $instance ) {

$title = apply_filters( 'widget_title', $instance['title'] );
// before and after widget arguments are defined by themes
echo $args['before_widget'];
if ( ! empty( $title ) )
echo $args['before_title'] . $title . $args['after_title'];

widget_forums();
restrict_forums_to_group();

// echo do_shortcode('[bbp-forum-index]');
echo $args['after_widget'];

}





// Widget Backend 
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
	$title = $instance[ 'title' ];
}
else {
	$title = __( 'New title', 'forum_widget_domain' );
}
// Widget admin form
?>
<p>
<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
</p>
<?php 
}
	
// Updating widget replacing old instances with new
public function update( $new_instance, $old_instance ) {
$instance = array();
$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
return $instance;
}
} // Class wpb_widget ends here

// Register and load the widget
function forum_load_widget() {
	register_widget( 'forum_widget' );
}
add_action( 'widgets_init', 'forum_load_widget' );


?>

