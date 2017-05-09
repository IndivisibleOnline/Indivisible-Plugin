<?php
 // include_once('posts.php');

add_action( 'wp', 'check_localgroup_access');

function check_localgroup_access() {
    if (!authorized_user(array('administrator')) ) {
        // Only target the front end
        // Do what you need to do
	global $current_user;
	$current_user = wp_get_current_user();

	$thisgroup = get_group_info();  // this might be where the problem lies
	$groupid = $thisgroup['id'];
 	$upod = pods("user", $current_user->ID);
    $usergroup = $upod->field( 'local_group' );
    $name = $usergroup[ 'name' ];
    $userid = $usergroup['term_id'];
//	var_dump($thisgroup);
	if ($thisgroup['type'] == 'local_groups' && $userid != $groupid){
		if (authorized_user(array('administrator','groups_administrator','iw_leadership'))){
			echo "Non Group Members do Not have access to this page.";
			echo "Group= " . $groupid;
			echo "& User= ". $userid;
		} else {
		wp_redirect( home_url() . "/mygroups");
		exit();
		}
	}
  }
}

function iw_list_related_posts(){

$html = list_related_posts();

return $html;
}

function iw_list_childposts(){

global $post;

if ( is_page() && $post->post_parent ){
	$ancestors = array();
	$ancestors = get_ancestors($post->ID,'post');
	$parent = (!empty($ancestors)) ? array_pop($ancestors) : $post->ID;
	if (!empty($parent)) {
	  $pages = get_pages(array('child_of'=>$parent));
  	  if (!empty($pages)) {
    		$page_ids = array();
    		foreach ($pages as $page) {
			if($page->ID != $post->ID){
      			$page_ids[] = $page->ID;
			}
    		}

		$inpages = implode(',',$page_ids);

		$grouppage = get_post($parent);

		$args =array(
			'sort_column' =>	'menu_order',
			'include' => 		$page_ids,
			'depth' => 		0,
			'echo' => 		0,
			'menu_class' =>		'sidebar',
);
//		$result = wp_page_menu($args);
//	return $result;
		$parentpage = '<a href="' . get_permalink($grouppage) . '">' . get_the_title($grouppage) . '</a>';
 //   		$childpages = wp_list_pages("sort_column=menu_order&title_li=&include=".$parent.','.implode(',',$page_ids)."&echo=0");
		$childpages = wp_list_pages($args);
	  }
	}

} else {
	$childpages = wp_list_pages('sort_column=menu_order&title_li=&child_of=' . $post->ID . '&echo=0');
}

if ($childpages){
	$string = "<ul><li>" . $parentpage . "</li>";
	$string .=  $childpages;
	$string .= "</ul>";
}
return $string;
}

add_shortcode('iw_related_posts','iw_list_related_posts');


/*
// -------------------------------
// Related Pages Child Pages
// -------------------------------
*/

// Creating the Groups widget that displays current Group Memberships
class relatedpages_widget extends WP_Widget {

function __construct() {
parent::__construct(
// Base ID of your widget
'relatedpages_widget',

// Widget name will appear in UI
__('Related Posts', 'relatedpages_widget_domain'),

// Widget description
array( 'description' => __( 'Related Posts', 'relatedpages_widget_domain' ), )
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

echo do_shortcode('[iw_related_posts]');

echo $args['after_widget'];

}
// Widget Backend
public function form( $instance ) {
if ( isset( $instance[ 'title' ] ) ) {
$title = $instance[ 'title' ];
}
else {
$title = __( 'Related Posts', 'relatedpages_widget_domain' );
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
function iwgroup_load_widget() {
	register_widget( 'relatedpages_widget' );
}
add_action( 'widgets_init', 'iwgroup_load_widget' );


?>
