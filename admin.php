<?php

// Add New Post Types to Toolbar
add_action('wp_before_admin_bar_render','iw_adminbar');

function iw_adminbar(){
   $userg = get_local_group();
   iw_bar_render_item('Topic Group Post','/wp-admin/post-new.php?type=tgp','new-content');
  // Any Group Member can create a Post
   iw_bar_render_item($userg['name'] . ' Group Post','/wp-admin/post-new.php?type=lgp','new-content');
  // Only Group Leaders can create Group Pages or Group Events
 if(authorized_user(array('administrator','group_leader'))){
   iw_bar_render_item($userg['name'] . ' Group PAGE','/wp-admin/post-new.php?post_type=page&type=lgp','new-content');
   iw_bar_render_item($userg['name'] . ' Event','/wp-admin/post-new.php?post_type=tribe_events&type=lge','new-content');
   }
 if(authorized_user(array('administrator','event_administrator','iw_leadership','groups_administrator'))){
   iw_bar_render_item('General Event','/wp-admin/post-new.php?post_type=tribe_events','new-content');
}
   iw_bar_render_item('My Groups','/mygroups');
     $grpurl = get_group_url($userg['term_id']);
   iw_bar_render_item($userg['name'],$grpurl,'My Groups');

$user_groups = get_topic_groups();
if (isset($user_groups)){
   iw_bar_render_item('------','#','My Groups');
}	
 foreach($user_groups as $group){
	$url = get_topic_group_url($group);
	$name = get_topic_group_name($group);
	iw_bar_render_item($name,$url,'My Groups');
}

 // If user is Authorized, give them Link to Manage Members 
  if(authorized_user()){
	iw_bar_render_item('Manage','/');
 if(authorized_user(array('administrator','group_leader'))){
	iw_bar_render_item('Manager Group Leaders','/groupmembers','Manage');
  }
   	iw_bar_render_item('Manage Pending Members','/membershipapproval','Manage');
	iw_bar_render_item('Manage Event Requests','/eventrequests','Manage');

  }
}

function get_group_url($group){
 $pod=pods('local_groups',$group);
 $field=$pod->field('group_page_url');
 $url = $field['guid'];
return $url;
}

function get_topic_group_url($group){
 $pod = pods('topic_groups',$group);
 $field = $pod->field('group_page_url');
 $url = $field['guid'];
return $url;
}

function get_topic_group_name($group){
 $pod = pods('topic_groups',$group);
 $field = $pod->field('name');
return $field;
}

function iw_bar_render_item($name, $href='', $parent='' ,$custom_meta=array()){
global $wp_admin_bar;

	if(!function_exists('is_admin_bar_showing') || !is_admin_bar_showing() ){
		return;
	}

	$wp_admin_bar->add_node(array(
	'parent' => $parent,
	'id' => $name,
	'title' => $name,
	'href' => $href,
	));
}

// Change the Post Title to Reflect Current Type of Post
function change_post_titles() {
 global $post, $title, $action, $current_screen;
 if ($post->post_type == 'post' || $post->post_type == 'page'){ 
	$type = iw_get_type($post);
	if ($type == 'lg_posts'){
	  $userg = get_local_group();
//	  $groupid = $userg['term_id'];	
          $title = "ADD NEW ". strtoupper($post->post_type) ." IN " . htmlspecialchars_decode(strtoupper($userg['name']));
	// Also make sure that Post Meta type reflects the proper group type  
 	update_post_meta($post->ID,'iw_post_type','lg_posts');
	} elseif ($type == 'tg_posts') {
	  $title = "ADD NEW TOPIC GROUP POST -- REMEMBER TO SELECT YOUR TOPIC GROUP---------->";
	// Also make sure that Post Meta type reflects the proper group type  
	  update_post_meta($post->ID,'iw_post_type','tg_posts');
	//  $test = get_post_meta($post->ID,'iw_post_type',true);
	//  $title = "Add New Post in " . $test;
	  add_meta_box('iw_topic_groupsdiv', __('My Topic Groups'), 'iw_filter_topic_meta_box', 'post', 'side', 'core');
	}
        } elseif($post->post_type == 'tribe_events') {
 	// Tribe Event
  	$type = iw_get_type($post);
   	 if ($type == 'lg_events' || $type == 'lge'){
	  $userg = get_local_group();
          $title = "ADD NEW EVENT IN " . htmlspecialchars_decode(strtoupper($userg['name']));
  	 }
        }
}

function remove_add_new(){

global $pagenow;
global $post;
$type = iw_get_type($post);
 if($pagenow == 'edit.php' && ($type == 'lg_posts' || $type == 'tg_posts')){
    // NEED TO REMOVE THE "ADD NEW" BUTTON !!!!
 }
}

add_action( 'admin_menu', 'iw_filter_topic_meta_boxes' );
add_action( 'admin_menu', 'iw_filter_event_meta_boxes');
add_action( 'admin_head', 'change_post_titles');
add_action( 'admin_head','remove_add_new');
add_action( 'save_post', 'iw_save_post',10,2);

function set_group_sidebar($post_id){
$pg = is_group_page($post_id); 
if ($pg==true){
          remove_action( 'save_post', 'iw_save_post',10,2);
	  update_post_meta( $post_id, '_wp_page_template', 'side-navigation.php' );
          add_action( 'save_post', 'iw_save_post',10,2);
	}
return;
}

function iw_save_post($post_id,$post){

  $type= iw_get_type($post);

  if ($type == 'tg_posts'){
        $result = iw_set_post_type($post_id,$post);
	topic_group_save_meta_boxes_data($post);
        set_group_sidebar($post_id);

  } elseif ($type == 'lg_posts') {
	$result = iw_set_post_type($post_id,$post);
	save_localgroup_post($post_id,$post);
	set_group_sidebar($post_id);
  } elseif ($type == 'lg_events'){
	save_localgroup_event($post_id,$post);
	events_save_meta_boxes_data($post);
   
  } else {

   // IF the Page is a group then it should have the proper template and sidebar associated with it.
   if(is_group_page($post_id)){
	set_group_sidebar($post_id);
   }
  }
	return;
}

// add_action( 'save_post_{post_type}', '{function name}, 10, 2 );


function iw_set_post_type($post_id,$post){

  $type = iw_get_type($post);
 if (!empty($type)){
	if ($type == 'lg_posts')  {
	update_post_meta($post->ID,'iw_post_type','lg_posts');
	} elseif ($type == 'tg_posts') {
	update_post_meta($post->ID,'iw_post_type','tg_posts');
	} elseif ($type == 'lg_events') {
	update_post_meta($post->ID,'iw_post_type','lg_events');
	}
  }
	return;
}


//add_action( 'save_post', 'save_localgroup_post', 10, 2);

function iw_get_type($post){
global $post;

  $type = $_REQUEST['type'];

  if(!isset($type)){
    $type = get_post_meta($post->ID,'iw_post_type',true);
  }
	if ($type=='lgp'){
		$type = 'lg_posts';
		update_post_meta($post->ID,'iw_post_type','lg_posts');
	}
	if ($type =='tgp'){
		$type = 'tg_posts';
		update_post_meta($post->ID,'iw_post_type','tg_posts');
	}
	if ( $type == 'lge'){
		$type = 'lg_events';
		update_post_meta($post->ID,'iw_post_type','lg_events');
	}
//	if (!isset($type))
//		$type='';
 return $type;
}

function is_group_page($postid=null){
 $result = false;
 $type = 'local_groups';
 if ($postid==null){
 	$postid = get_the_ID();
 }

 $group = wp_get_object_terms($postid,$type);

 if (!empty($group)){
	$result = true;
 } else {
	$group = wp_get_object_terms($postid,'topic_groups');
 }
 
 if (!empty($group)){
	$result = true;
 }
 return $result;

}

/*  ///////////////////////////////////
//
//   SET LOCAL EVENT CATEGORY
//
//////////////////////////////////////
*/

function set_local_event_category($post_id,$group_id){

        $pod = pods("local_groups",$group_id);
        $grpfield = $pod->field('related_event_category');
        $grptax = (int)$grpfield['term_id'];
        $grptax = (array)$grptax;
//	write_log('group id = ' . $group_id . ' and event id is '. $grpfield['term_id']);
        $setresult = wp_set_object_terms($post_id,$grptax,'tribe_events_cat',true);

}

function save_event_categories($post_id,$categories){

	$setresult = wp_set_object_terms($post_id,$categories,'tribe_events_cat',false);

}

function save_localgroup_event($post_id,$post){
$type = iw_get_type($post);
 if($type == 'lg_events'){
    	$userg = get_local_group();
        $groupid = $userg['term_id'];
        $groupname = $userg['name'];
        if ( !authorized_user(array('administrator')) && is_null($groupid) ) {
                if (!authorized_user('administrator')){
                 echo" Insert Redirect here... can't post without a group";
                 remove_meta_box( 'submitdiv','lg_posts','normal' ); 
        // Categories Metabox
                 wp_redirect( home_url() . '/error' ); exit;
                } else {
                // add_meta_box( 'local_groupsdiv',$type,'normal' ); // Custom Fields Metabox
                 remove_meta_box( 'submitdiv','lg_posts','normal' ); 
                }
        } else {
	     // Assign Local Group to Event Post
                $result = wp_set_object_terms($post_id, $groupname, 'local_groups');
	     // Set the Categories associated with the Event & Public Request, if applicable
	     	$eventresult =  events_save_meta_boxes_data($post_id,$post);
	     // Set the Category associated with the local group (important to do this after saving metabox data)
	     	$localresult = set_local_event_category($post_id,$groupid);
	}
 }
}

function save_localgroup_post($post_id,$post) {

$type = iw_get_type($post);

//  $type = $_POST['iw_post_type']; 
//	write_log('We ARE SAVING...' . $type);

  if($type == 'lg_posts') {
	 $userg = get_local_group();
	 $groupid = $userg['term_id'];
	 $groupname = $userg['name'];
	if ( !authorized_user(array('administrator')) && is_null($groupid) ) {
		if (!authorized_user('administrator')){
		 echo" Insert Redirect here... can't post without a group";
		 remove_meta_box( 'submitdiv','lg_posts','normal' ); 
	// Categories Metabox
		 wp_redirect( home_url() . '/error' ); exit;
		} else {
		// add_meta_box( 'local_groupsdiv',$type,'normal' ); // Custom Fields Metabox

		}
	} else {
		$result = wp_set_object_terms($post_id, $groupname, 'local_groups');
		$catresult = set_local_category($post_id, $groupid);
		 if ($post->post_type == 'page' || $post->post_type == 'post'){
		  $pod = pods('local_groups',$groupid);
		  $grpfield = $pod->field('group_page_url');
		  $grpid = $grpfield['pod_item_id'];
		  $args=array(
      		   'ID'           => $post_id,
      		   'post_parent'   => $grpid,);
		  remove_action( 'save_post', 'iw_save_post',10,2);
		  wp_update_post($args);
		  set_group_sidebar($post_id);
	//	  update_post_meta( $post_id, '_wp_page_template', 'side-navigation.php' );
      	//	  update_post_meta( $post_id, 'sidebar', 'fusion-localgroup');
		  add_action( 'save_post', 'iw_save_post',10,2);

		 }
		}
  }

	return;
}

//SHORTCODE NOT NEEDED.... HERE FOR TESTING ONLY
add_shortcode('iw-testtopic','set_topic_group_category');

function set_topic_group_category($current_group=null){
	// TOPIC GROUPS MUST BE UPDATED FIRST
	if (empty($current_group)){
	     $post = get_post();
	     $current_group = ( wp_get_object_terms($post->ID,'topic_groups') ) ? wp_get_object_terms($post->ID, 'topic_groups') : array();
	     $current_selected = array();
	foreach ($current_group as $sgroup){
	   $current_selected[] = $sgroup->term_id;
	}
	}else {
	   $current_selected[] = $current_group;
	}
	// Loop through array of Currently selected groups
	foreach($current_selected as $group){
		$groupid = $group;
  		$pod = pods("topic_groups",$groupid);
		$grpfield = $pod->field( 'related_category' );
		$grptax= $grpfield ['term_id'];
		$catdata[] = (int)$grptax;
		}
//		$setresult = wp_set_object_terms($post_id,$catdata,'category',false);
		$cleanup = wp_set_object_terms($post->ID,null,'category',false);
		$catresult = wp_set_object_terms($post->ID, $catdata, 'category', true);
//		$rmresult = wp_remove_object_terms($post->ID,1,'category'); // removes default category
}

function set_local_category($post_id,$group_id){

	$pod = pods("local_groups",$group_id);
	$grpfield = $pod->field('related_category');
	$grptax = (int)$grpfield['term_id'];
	$grptax = (array)$grptax;
	$setresult = wp_set_object_terms($post_id,$grptax,'category',false);
}

function events_save_meta_boxes_data($post_id,$post){
 if( isset( $_POST['tax_input[tribe_events_cat]'] ) ){
        $categories = (array) $_POST['tax_input[tribe_events_cat]'];
        // sanitize array
        $categories = array_map( 'sanitize_text_field', $categories );
        $categories = array_map( 'intval' , $categories );

        // Get all Data to Clean Up before Update
        $termdata = wp_get_object_terms($post->ID,'tribe_events_cat');
        foreach ($termdata as $mydata){
		
           $current_array[] = $mydata->term_id;
        }
        $alldata = array_map( 'intval', $current_array);
        $remove = wp_remove_object_terms($post->ID,$alldata,'tribe_events_cat');

        // Set the Topic Group
        $result = wp_set_object_terms($post_id, $categories, 'tribe_events_cat',false);
	

}else{

       // delete data
        $termdata = wp_get_object_terms($post->ID,'topic_groups');
        foreach ($termdata as $mydata){
           $current_array[] = $mydata->term_id;
        // NEED TO WRITE CODE TO REMOVE TERM FROM CATEGORIES
        }
        $alldata = array_map( 'intval', $current_array);
//      $remove = wp_remove_object_terms($post->ID,$alldata,'topic_groups');
}

	// IS THIS A PUBLIC REQUEST?
	$public_request = $_POST['pub_request'];
	update_post_meta($post_id,'iw_public_event',$public_request);

}

function topic_group_save_meta_boxes_data($post){
  if( isset( $_POST['tax_input[topic_groups]'] ) ){
	$topics = (array) $_POST['tax_input[topic_groups]'];
	// sanitize array
	$topics = array_map( 'sanitize_text_field', $topics );
	$topics = array_map( 'intval' , $topics);

	// Get all Data to Clean Up before Update
	$termdata = wp_get_object_terms($post->ID,'topic_groups');
	foreach ($termdata as $mydata){
	   $current_array[] = $mydata->term_id;
	}
	$alldata = array_map( 'intval', $current_array);
	$remove = wp_remove_object_terms($post->ID,$alldata,'topic_groups');

	// Set the Topic Group
	$result = wp_set_object_terms($post->ID, $topics, 'topic_groups',false);

	// Update the Categories
	set_topic_group_category($topics);

}else{
	// delete data
	$termdata = wp_get_object_terms($post->ID,'topic_groups');
	foreach ($termdata as $mydata){
	   $current_array[] = $mydata->term_id;
	// NEED TO WRITE CODE TO REMOVE TERM FROM CATEGORIES
	}
	$alldata = array_map( 'intval', $current_array);
//	$remove = wp_remove_object_terms($post->ID,$alldata,'topic_groups');
}
}

function iw_filter_topic_meta_boxes() {
global $post;
$type = iw_get_type($post);
 if ($type == 'tg_posts'){
	remove_meta_box('topic_groupsdiv', 'post', 'side');
	remove_meta_box('categorydiv', 'post', 'side');
//	remove_meta_box('topic_groupsdiv', 'post', 'advanced');
//	remove_meta_box('topic_groupsdiv', 'post', 'normal');

	add_meta_box('iw_topic_groupsdiv', __('My Topic Groups'), 'iw_filter_topic_meta_box', 'post', 'side', 'high');
//	add_meta_box('iw_topic_groupsdiv', __('My Topic Groups'), 'iw_filter_topic_meta_box', 'tg_posts', 'side', 'core');
 }
}

function iw_filter_topic_meta_box( $post ) {

	global $user_ID;


	// get all topic groups that the user belongs to

//	$current_group = ( get_post_meta( $post->ID, 'topic_groups', true ) ) ? get_post_meta( $post->ID, 'topic_groups', true ) : array();
	$current_group = ( wp_get_object_terms($post->ID,'topic_groups') ) ? wp_get_object_terms($post->ID, 'topic_groups') : array();
	$current_selected = array();
	foreach ($current_group as $sgroup){
	   $current_selected[] = $sgroup->term_id;
	}

	$cats = array();
//	$html ='';
	$trelated = get_topic_tax_groups();
    if (count($trelated) > 0){
	$html .= '<div id="taxonomy-groups" class="categorydiv">';
	$html .= '<div id="taxonomy-groups" class="category-tabs">';
	$html .= '<div id="taxonomy-groups" class="category-pop">';
	$html .= '<div id="taxonomy-groups" class="category-all">';
        foreach($trelated as $group){
                $name = $group['name'];
                $id = $group[ 'term_id' ];
                $tpod2 = pods("topic_groups",$id);
                $grpfield = $tpod2->field( 'group_page_url');
                $grpurl= $grpfield ['pod_item_id'];
		$selected = (in_array($id,$current_selected)) ? ' checked="checked"' : '';
		$html .= '<input type="checkbox" class="required" name="tax_input[topic_groups][]" value="'.$id .'"' . $selected . '/>' . $name . ' <br />';
                }

	set_topic_group_category();	
	$html .= '</div></div></div></div>';
	}
	echo $html;

}

function request_public_event($post){

	$pub = get_post_meta($post->ID,'iw_public_event',true);
	write_log ("my public event data is " . $pub);
	if ($pub == "requested" || $pub == "approved")
		$val = "checked = 'checked'";
	if($pub == 'approved')
		$val2 = "checked = 'checked'";
	$html = '<input type="checkbox" name="pub_request"  value="requested" '. $val .'/> Share with all Groups';
	$html .= '<br> &nbsp; <input type="checkbox" name="pub_approved" value = "approved" ' . $val2 . ' disabled /> Approved';
	echo $html;

}

function iw_filter_event_meta_boxes() {

global $post;
$type = iw_get_type($post);

echo "<h1>type= " . $type . "</h1>";

 if ($type == 'lg_events'){
	remove_meta_box('topic_groupsdiv', 'tribe_events', 'side');
	remove_meta_box('local_groupsdiv', 'tribe_events', 'side');
	remove_meta_box('tribe_events_catdiv' ,'tribe_events' ,'side');
//	remove_meta_box('categorydiv', 'post', 'side');
//	remove_meta_box('topic_groupsdiv', 'post', 'advanced');
//	remove_meta_box('topic_groupsdiv', 'post', 'normal');

	add_meta_box('iw_events_groupsdiv', __('My Event Categories'), 'iw_filter_event_meta_box', 'tribe_events', 'side', 'high');
 	add_meta_box('iw_events_publicdiv',__('Public Event'),'request_public_event','tribe_events','side','high');
//	add_meta_box('iw_topic_groupsdiv', __('My Topic Groups'), 'iw_filter_topic_meta_box', 'tg_posts', 'side', 'core');
 }
}

function iw_filter_event_meta_box( $post ) {

	global $user_ID;

	// get all topic groups that the user belongs to

	$current_group = ( wp_get_object_terms($post->ID,'tribe_events_cat') ) ? wp_get_object_terms($post->ID, 'tribe_events_cat') : array();
	$current_selected = array();
	foreach ($current_group as $sgroup){
	   $current_selected[] = $sgroup->term_id;
	}

	$cats = array();
	$html ='';
	$args = array('taxonomy' => 'tribe_events_cat');
	$trelated = get_terms($args);

//	echo "categories<br>";

//	var_dump($trelated);

    if (count($trelated) > 0){
	$html .= '<div id="taxonomy-events" class="categorydiv">';
	$html .= '<div id="taxonomy-events" class="category-tabs">';
	$html .= '<div id="taxonomy-events" class="category-pop">';
	$html .= '<div id="taxonomy-events" class="category-all">';

	foreach($trelated as $group){
                $name = $group->name;
                $id = $group->term_id;
		if ($name != 'Public' && $group->parent == 0){
		  $selected = (in_array($id,$current_selected)) ? ' checked="checked"' : '';
		  $html .= '<input type="checkbox" class="required" name="tax_input[tribe_events_cat][]" value="'.$id .'"' . $selected . '/>' . $name . ' <br />';
                }
	}

	// set_topic_group_category();
	$html .= '</div></div></div></div>';
    }

	echo $html;
}



// REMOVE MENU ADMIN BAR ITEMS

function remove_menus () {
    if(!authorized_user(array('administrator')))
    {
        global $menu;
        $restricted = array(__('Dashboard'), __('Posts'),__('To-Do-List'), __('Events'), __('Portfolio'), __('FAQs'), __('Media'), __('Contact'), __('Links'), __('Pages'), __('Appearance'), __('Tools'), __('Users'), __('Settings'), __('Comments'), __('Plugins'));
        end ($menu);
        while (prev($menu)){
            $value = explode(' ',$menu[key($menu)][0]);
            if(in_array($value[0] != NULL?$value[0]:"" , $restricted)){unset($menu[key($menu)]);}
        }

    }
}
add_action('admin_menu', 'remove_menus');


function ipstenu_admin_bar_remove() {
        global $wp_admin_bar;
//    if(!authorized_user(array('administrator')))
//	{ 
        /* Remove their stuff */
        $wp_admin_bar->remove_menu('todolist');
        $wp_admin_bar->remove_menu('dashboard');
        $wp_admin_bar->remove_menu('updates');
        $wp_admin_bar->remove_menu('todo-list');
        $wp_admin_bar->remove_menu('tribe-events-group');
        $wp_admin_bar->remove_menu('tribe-events-add-ons-group-container');
        $wp_admin_bar->remove_menu('tribe-events-import-group');
        $wp_admin_bar->remove_menu('tribe-events-import');
        $wp_admin_bar->remove_menu('tribe-events-settings-group');
        $wp_admin_bar->remove_menu('tribe-events');
        $wp_admin_bar->remove_menu('avada');
        $wp_admin_bar->remove_menu('comments');
        $wp_admin_bar->remove_menu('wp-logo');
        $wp_admin_bar->remove_menu('new-post');
        $wp_admin_bar->remove_menu('new-media');
        $wp_admin_bar->remove_menu('new-page');
        $wp_admin_bar->remove_menu('new-avada_portfolio');
        $wp_admin_bar->remove_menu('new-avada_faq');
        $wp_admin_bar->remove_menu('new-tribe_events');
        $wp_admin_bar->remove_menu('new-themefusion_elastic');
        $wp_admin_bar->remove_menu('new-user');
        $wp_admin_bar->remove_menu('new-slide');
        $wp_admin_bar->remove_menu('new-topic');
        $wp_admin_bar->remove_menu('new-reply');
        $wp_admin_bar->remove_menu('ab-ls-add-new');


    // Parent Properties for new-content node:
        //$new_content_node->id     // 'new-content'
        //$new_content_node->title  // '<span class="ab-icon"></span><span class="ab-label">New</span>'
        //$new_content_node->parent // false
        //$new_content_node->href   // 'http://www.somedomain.com/wp-admin/post-new.php'
        //$new_content_node->group  // false
        //$new_content_node->meta['title']   // 'Add New'

   $new_content_node = $wp_admin_bar->get_node('new-content');

    //Change href
    $new_content_node->href = '#';

    //Update Node.
    $wp_admin_bar->add_node($new_content_node);

//  }

}
 
add_action('wp_before_admin_bar_render', 'ipstenu_admin_bar_remove', 80);
// add_action('admin_bar_menu', 'ipstenu_admin_bar_remove', 80);



add_action('admin_menu', 'remove_admin_menu_links');
function remove_admin_menu_links(){
    $user = wp_get_current_user();
    if(!authorized_user(array('administrator'))) {
        remove_menu_page('tools.php');
	remove_menu_page('edit.php?post_type=topic');
	remove_menu_page('user-new.php');
	remove_menu_page('post-new.php?post_type=topic');
	remove_menu_page('edit.php?post_type=reply');
	remove_menu_page('post-new.php?post_type=reply');
        remove_menu_page('themes.php');
        remove_menu_page('options-general.php');
        remove_menu_page('plugins.php');
	remove_menu_page('users.php');
	remove_menu_page('edit-comments.php');
	remove_menu_page('page.php');
	remove_menu_page('upload.php');
	remove_menu_page( 'edit.php?post_type=page' ); 
	remove_menu_page( 'edit.php?post_type=videos' );
	remove_menu_page( 'edit.php' );
	remove_theme_support( 'avada-admin-menu' );

    }
}





/*
add_action( 'admin_menu', 'iw_set_referer' );

function iw_set_referer() {

	$screen = get_current_screen();
	if( $screen->id=='lg_posts') {
		remove_my_post_metaboxes();
		$refid = url_to_postid(wp_get_referer());
		$ref = get_group_info($refid);
	}
}
*/
function remove_my_post_metaboxes(){
$type = iw_get_type($post);
 if (!authorized_user(array('administrator'))){
	remove_post_metaboxes('post');
	remove_post_metaboxes('page');
   if ($type  == 'lg_events'){
	remove_post_metaboxes('tribe_events');
   }
} else {
	remove_admin_post_metaboxes('post');
   if ($type == 'lg_events'){
	remove_post_metaboxes('tribe_events');
   }
 }

}

function remove_admin_post_metaboxes($type){

remove_meta_box('pyre_post_options',$type,'normal');
remove_meta_box('pyre_post_options',$type,'advanced');
remove_meta_box('um-admin-access-settings',$type,'normal');
remove_meta_box('um-admin-access-settings',$type,'side');
remove_meta_box('um-admin-access-settings',$type,'advanced');
remove_meta_box('um-admin-access-settings',$type,'advanced');
remove_meta_box('um-admin-access-settings',$type,'normal');
remove_meta_box('um-admin-access-settings',$type,'side');
remove_meta_box('uma_post_access',$type,'normal');
remove_meta_box('uma_post_access',$type,'advanced');
remove_meta_box('uma_post_access',$type,'side');
remove_meta_box( 'access',$type,'normal');
remove_meta_box( 'local_groupsdiv',$type,'side' ); // Custom Fields Metabox
remove_meta_box( 'topic_groupsdiv',$type,'side' ); // Custom Fields Metabox
remove_meta_box( 'local_groupsdiv',$type,'normal' ); // Custom Fields Metabox
remove_meta_box( 'topic_groupsdiv',$type,'normal' ); // Custom Fields Metabox
remove_meta_box( 'slugdiv',$type,'normal' ); // Slug Metabox
remove_meta_box( 'trackbacksdiv',$type,'normal' ); // Trackback Metabox
remove_meta_box( 'categorydiv',$type,'normal' ); // Categories Metabox
remove_meta_box( 'formatdiv',$type,'normal' ); // Formats Metabox
// remove_meta_box( 'postimagediv',$type,'normal' ); // Featured Image Metabox

}

// REMOVE POST META BOXES
function remove_post_metaboxes($type) {

remove_meta_box('pyre_post_options',$type,'normal');
remove_meta_box('pyre_post_options',$type,'advanced');
remove_meta_box('fusion_builder_layout',$type,'normal');
remove_meta_box('mymetabox_revslider_0',$type,'normal');
remove_meta_box('mymetabox_revslider_0',$type,'advanced');
remove_meta_box('um-admin-access-settings',$type,'normal');
remove_meta_box('um-admin-access-settings',$type,'side');
remove_meta_box('um-admin-access-settings',$type,'advanced');
remove_meta_box('um-admin-access-settings',$type,'advanced');
remove_meta_box('um-admin-access-settings',$type,'normal');
remove_meta_box('um-admin-access-settings',$type,'side');
remove_meta_box('uma_post_access',$type,'normal');
remove_meta_box('uma_post_access',$type,'advanced');
remove_meta_box('uma_post_access',$type,'side');
remove_meta_box( 'access',$type,'normal');
remove_meta_box( 'authordiv',$type,'normal' ); // Author Metabox
remove_meta_box( 'avada_portfolio',$type,'normal' );
remove_meta_box( 'revolution-slider-options',$type,'normal' );
remove_meta_box( 'revolution-slider-options',$type,'advanced' );
remove_meta_box( 'local_groupsdiv',$type,'normal' ); // Custom Fields Metabox
remove_meta_box( 'topic_groupsdiv',$type,'normal' ); // Custom Fields Metabox
remove_meta_box( 'commentstatusdiv',$type,'normal' ); // Comments Status Metabox
// remove_meta_box( 'commentsdiv',$type,'normal' ); // Comments Metabox
remove_meta_box( 'postcustom',$type,'normal' ); // Custom Fields Metabox
remove_meta_box( 'pageparentdiv',$type,'normal' ); // Custom Fields Metabox
remove_meta_box( 'postexcerpt',$type,'normal' ); // Excerpt Metabox
remove_meta_box( 'revisionsdiv',$type,'normal' ); // Revisions Metabox
remove_meta_box( 'slugdiv',$type,'normal' ); // Slug Metabox
remove_meta_box( 'trackbacksdiv',$type,'normal' ); // Trackback Metabox
remove_meta_box( 'categorydiv',$type,'normal' ); // Categories Metabox
remove_meta_box( 'formatdiv',$type,'normal' ); // Formats Metabox
// remove_meta_box( 'postimagediv',$type,'normal' ); // Featured Image Metabox
// remove_meta_box( 'submitdiv',$type,'normal' ); // Categories Metabox
// remove_meta_box( 'tagsdiv-post_tag',$type,'normal' ); // Tags Metabox

}

 add_action('admin_menu','remove_my_post_metaboxes');
// add_action('after_setup_theme','remove_my_post_metaboxes');

function remove_my_page_metaboxes() {
remove_meta_box( 'local_groups','page','normal' ); // Custom Fields Metabox
remove_meta_box( 'postexcerpt','page','normal' ); // Excerpt Metabox
remove_meta_box( 'commentstatusdiv','page','normal' ); // Comments Metabox
remove_meta_box( 'trackbacksdiv','page','normal' ); // Talkback Metabox
remove_meta_box( 'slugdiv','page','normal' ); // Slug Metabox
remove_meta_box( 'authordiv','page','normal' ); // Author Metabox
remove_meta_box( 'categorydiv','page','normal' ); // Categories Metabox
remove_meta_box( 'formatdiv','page','normal' ); // Formats Metabox
// remove_meta_box( 'postimagediv','page','normal' ); // Featured Image Metabox
remove_meta_box( 'submitdiv','page','normal' ); // Categories Metabox
remove_meta_box( 'tagsdiv-post_tag','page','normal' ); // Tags Metabox


}
add_action('admin_head-post','remove_my_page_metaboxes');
