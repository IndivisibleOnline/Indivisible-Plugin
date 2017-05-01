<?php

function show_facebook_widget() {
    // Get post by post ID.
    $post = get_post( $post->ID );

    // Get post type by post.
    $post_type = $post->post_type;

    // Get post type taxonomies.
    $taxonomies = get_object_taxonomies( $post_type, 'objects' );

    $out = array();

    foreach ( $taxonomies as $taxonomy_slug => $taxonomy ){
        // Get the terms related to post.
        $terms = get_the_terms( $post->ID, $taxonomy_slug );

        if ( ! empty( $terms ) && ($taxonomy_slug == 'local_groups' || $taxonomy_slug == 'topic_groups')) {
            foreach ( $terms as $term ) {
		$type =  $taxonomy_slug;
                $name = $term->name;
		$id = $term->term_id;
            }
	 }
    }

 // Now that we have the group ID...

	//	echo "confirm group is ". $id . "<br>";
		$params = array(
		'where' => 't.term_id =' . $id,
                );

	$pods = pods($type, $params);

	while ($pods->fetch() ) {
    		$title = $pods->display('name');
		$fb = $pods->display('facebook_page_url');
	//	echo $title . " (" . $fb . ")";
	}
	echo do_shortcode('[custom-facebook-feed num=3 type=photos layout=full id=' . $fb . ' bgcolor=#ffffff]');
//	return $fb;

}

add_shortcode('iw_facebook','show_facebook_widget');

/*
// -------------------------------
// Join / Leave Group Widget
// -------------------------------
*/

// Creating the widget
class join_widget extends WP_Widget {

function __construct() {
parent::__construct(
// Base ID of your widget
'join_widget',

// Widget name will appear in UI
__('Join IW Workgroup', 'join_widget_domain'),

// Widget description
array( 'description' => __( 'Join an IW Workgroup', 'join_widget_domain' ), )
);
}

// Creating widget front-end
// This is where the action happens
public function widget( $args, $instance ) {
$title = apply_filters( 'widget_title', $instance['title'] );
// before and after widget arguments are defined by themes
echo $args['before_widget'];
if ( ! empty( $title ) )
// echo $args['before_title'] . $title . $args['after_title'];

// $pid = get_current_group();
$gid = get_group_info();
$pid = $gid['id'];
$ug=get_topic_groups();

$status=0;
foreach($ug as $key => $value){
	if ($pid == $value){
		$status = 1;
	}
	}
if ($status){

echo __( '<form action="https://www.indivisiblewestchester.org/wp-admin/admin-post.php" method="post" name="leavetopicgroup"><input type="hidden" name="action" value="leavetopicgroup"/><input type="hidden" name="pid" value="' . $pid .'"/><center><input type="submit" value="Leave GROUP"></center></form>', 'join_widget_domain' );
echo $args['after_widget'];

} else {


// This is where you run the code and display the output
echo __( '<form action="https://www.indivisiblewestchester.org/wp-admin/admin-post.php" method="post" name="jointopicgroup"><input type="hidden" name="action" value="jointopicgroup"/><input type="hidden" name="pid" value="' . $pid .'"/><center><input type="submit" value="JOIN GROUP"></center></form>', 'join_widget_domain' );
echo $args['after_widget'];

}



}

// Widget Backend
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'New title', 'join_widget_domain' );
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
function join_load_widget() {
	register_widget( 'join_widget' );
}
add_action( 'widgets_init', 'join_load_widget' );


/*
// -------------------------------
// Group Membership Widget
// -------------------------------
*/

// Creating the Groups widget that displays current Group Memberships
class iwleader_widget extends WP_Widget {

function __construct() {
parent::__construct(
// Base ID of your widget
'iwleader_widget',

// Widget name will appear in UI
__('My Groups', 'iwleader_widget_domain'),

// Widget description
array( 'description' => __( 'IW Group Info', 'iwleader_widget_domain' ), )
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

// This is where you run the code and display the output
if (authorized_user(array('administrator','group leader','groups_administrator','group_leader','iw_leadership'))){
	echo __( '<a href="' . get_site_url() . '/membershipapproval"> Manage Member Requests</a>', 'iwleader_widget_domain' );
	echo __(  do_shortcode('[iw_usergroups]') . ' <br> ' . do_shortcode('[iw_usergroups type="topic"]')  , 'iwleader_widget_domain' );

} else {
	if (authorized_user(array('subscriber'))){
	 	echo __('Please join a local group.','iw_widget_domain');
	} elseif(authorized_user(array('pending_member_validation'))) {
		echo __('Your request is pending... Thanks for your patience.','iw_widget_domain');
	} else {
	echo __( do_shortcode('[iw_usergroups]') . ' <br> ' . do_shortcode('[iw_usergroups type="topic"]')  , 'iwleader_widget_domain' );
	}
}
echo $args['after_widget'];

}
// Widget Backend
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'IW Group Leader', 'iwleader_widget_domain' );
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
function wpb_load_widget() {
	register_widget( 'iwleader_widget' );
}
add_action( 'widgets_init', 'wpb_load_widget' );

/*
// -------------------------------
// Group Management Widget
// -------------------------------
*/

// Creating the Groups widget that displays current Group Memberships
class gleader_widget extends WP_Widget {

function __construct() {
parent::__construct(
// Base ID of your widget
'gleader_widget',

// Widget name will appear in UI
__('Manage Group', 'gleader_widget_domain'),

// Widget description
array( 'description' => __( 'Group Info', 'gleader_widget_domain' ), )
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

// This is where you run the code and display the output
if (authorized_user(array('administrator','group leader','groups_administrator','group_leader','iw_leadership'))){
	echo __( '<a href="' . get_site_url() . '/membershipapproval"> Manage Member Requests</a>', 'iwleader_widget_domain' );
}
echo $args['after_widget'];

}
// Widget Backend
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'Group Leader', 'gleader_widget_domain' );
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
} // Class gl_widget ends here

// Register and load the widget
function gl_load_widget() {
	register_widget( 'gleader_widget' );
}
add_action( 'widgets_init', 'gl_load_widget' );



/* 
// -------------------------------
// Group Facebook Widget 
// -------------------------------
*/

// Creating the widget 
class group_facebook_widget extends WP_Widget {

function __construct() {
parent::__construct(
// Base ID of your widget
'group_facebook_widget', 

// Widget name will appear in UI
__('Group Facebook Feed', 'group_facebook_widget_domain'), 

// Widget description
array( 'description' => __( 'Display your Facebook Feed', 'group_facebook_widget_domain' ), ) 
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

	echo __( do_shortcode('[iw_facebook]'), 'group_facebook_widget_domain' );
	echo $args['after_widget'];


}

// Widget Backend 
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'New title', 'group_facebook_widget_domain' );
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
function group_facebook_load_widget() {
	register_widget( 'group_facebook_widget' );
}
add_action( 'widgets_init', 'group_facebook_load_widget' );

