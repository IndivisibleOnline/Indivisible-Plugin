<?php

// Requries PODS and UAM Plugins

include(dirname(__FILE__).'/user_mgt.php');
include(dirname(__FILE__).'/forums.php');
include(dirname(__FILE__).'/groups.php');
include(dirname(__FILE__).'/widgets.php');
include(dirname(__FILE__).'/admin.php');


if ( ! function_exists('write_log')) {
   function write_log ( $log )  {
      if ( is_array( $log ) || is_object( $log ) ) {
         error_log( print_r( $log, true ) );
      } else {
         error_log( $log );
      }
   }
}



function authorized_user($role = null){
	$allowed_roles='';
	$result = false;

	
	if (empty($user)){
		$current_user = wp_get_current_user();
		$user_id = $current_user->ID;
		$user_role = $current_user->roles;
		
		} else {
		$user_role = $user->roles;
		}
	if (empty($role)){
		$allowed_roles = array('group_leader','iw_leadership','groups_administrator','administrator');
		} else {
		$allowed_roles = $role;
		}
	
	if (is_array($user_role)){
		$myroles = explode(",",implode(",",$user_role));
	} else {
		$myroles = $user_role;
	}
	foreach($myroles as $thisrole){
	  foreach($allowed_roles as $allow){
	    if( $allow == $thisrole)  {
		$result = true;
	    }
	  }
	}

   return $result;
}


function get_user_group_list($atts){

// Get list of Groups (topic or local based on $atts) for a specific user

 $atts = array_change_key_case((array)$atts, CASE_LOWER);
    // override default attributes with user attributes
    $atts = shortcode_atts([
                 'type' => 'local',
                  ], $atts,'grouplist');

	global $current_user;
	wp_get_current_user();

   if ($atts['type'] == 'topic'){
      // Topic Group
	     $tpod = pods("user", $current_user->ID);
	     $trelated = $tpod->field( 'topic_groups' );

	     $htmlresult = "<div>Your Working Groups:<br><ul>";

	     if (count($trelated) > 0){
      	foreach($trelated as $group){

	         $name = $group[ 'name' ];
          $id = $group[ 'term_id' ];
 	        $tpod2 = pods("topic_groups",$id);
	        $grpfield = $tpod2->field( 'group_page_url');
	        $grpurl= $grpfield ['pod_item_id'];

	        $htmlresult .= "<li><a href='" . get_permalink($grpurl) . "'>" . $name . "</a></li>";


    	   }
    	 $htmlresult .= "</ul></div>";
    	 } else {
	       $htmlresult .= "You have not joined any groups yet.</ul>";
    	 }


} elseif ($atts['type'] == 'local') {
// Local Group
	$htmlresult = '';
	$pod = pods("user", $current_user->ID);
	$related = $pod->field( 'local_group' );

    	$name = $related[ 'name' ];
    	$id = $related[ 'term_id' ];

 	$pod2 = pods("local_groups",$id);
 	$grpfield = $pod2->field( 'group_page_url');
	$grpurl= $grpfield ['guid'];
	$htmlresult .= "<div>Welcome, " . $current_user->display_name . ". <br>Your Local Group is <a href='" . $grpurl . "'>" .$name . "</a></div>";

}
    return $htmlresult;

}


function get_local_group_list(){

$param = array(
        'limit' => -1,
    );


	$allpods = pods('local_groups', $param);
//	while ($allpods->fetch()){
//	$gname = $allpods->display('name') ;
//	$grpurl= $grpfield ['pod_item_id'];

return $allpods;

}

add_shortcode('iw_usergroups','get_user_group_list');

function get_group_list($atts){

// Get list of Groups (topic or local based on $atts) for a specific user

 $atts = array_change_key_case((array)$atts, CASE_LOWER);
    // override default attributes with user attributes
    $atts = shortcode_atts([
                                  'type' => 'topic',
                                 ], $atts,'grouplist');

    global $current_user;
    wp_get_current_user();
    $htmlresult = ' no go ';
   if ($atts['type'] == 'topic'){
      // Topic Group
        $htmlresult = "<div>Check out these amazing Groups.... too...<br><ul>";
  	$param = array(
        'limit' => -1,
    );
        $allpods = pods('topic_groups', $param);
	while ($allpods->fetch()){
	$gname = $allpods->display('name') ;
	$grpfield = $allpods->field( 'group_page_url');
	$grpurl= $grpfield ['pod_item_id'];
	
        $htmlresult .= "<li><a href='" . get_permalink($grpurl) . "'>" . $gname . " </a></li>";
       }  
       }
// Loop over each item since it's an array
            
          

    $htmlresult .= "</ul></div>";

    return $htmlresult;

}

add_shortcode('iw_listgroups','get_group_list');


function get_topic_tax_groups(){

// Get list of Groups (topic or local based on $atts) for a specific user

	  global $current_user;
    	  wp_get_current_user();

	if($userid == null){
            $userid = $current_user->ID;
	}

             $tpod = pods("user", $userid);
             $trelated = $tpod->field( 'topic_groups' );


	return $trelated;

}


add_action('admin_post_joingroup','frm_join_group');
add_action('admin_post_nopriv_joingroup','frm_join_group');

function frm_join_group() {


global $current_user;

if ( ! empty( $_REQUEST ) ) {
 
 $type = $_REQUEST['type'];
 $grpid = $_REQUEST['gid'];
// $user_id = $_POST['userid'];

     $current_user = wp_get_current_user();
     $user_id = $current_user->ID;
   // Sanitize the POST field
    // Generate email content
    // Send to appropriate email

  //      global $ultimatemember;
  //      $current_user = wp_get_current_user();
  //      $user_id = $current_user->ID;
  //      $user_role = $current_user->roles;
  //      um_fetch_user($user_id);
  //      $um_role = um_user('role');
  //      $ur = new WP_User ($user_id);


	add_user_to_group($user_id,$type,$grpid);
}

 wp_redirect( get_permalink() ); exit;

}



// Prevent users from seeing administrators

add_action('pre_user_query','yoursite_pre_user_query');

function yoursite_pre_user_query($user_search) {
    $user = wp_get_current_user();

    if ( $user->roles[0] != 'administrator' ) { 
        global $wpdb;

        $user_search->query_where = 
        str_replace('WHERE 1=1', 
            "WHERE 1=1 AND {$wpdb->users}.ID IN (
                 SELECT {$wpdb->usermeta}.user_id FROM $wpdb->usermeta 
                    WHERE {$wpdb->usermeta}.meta_key = '{$wpdb->prefix}user_level' 
                    AND {$wpdb->usermeta}.meta_value = 0)", 
            $user_search->query_where
        );

    }
}



//this will prevent users from promoting others to administrator

function deny_change_to_admin( $all_roles )

{
    if ( ! current_user_can('administrator') )
        unset( $all_roles['administrator'] );

    if (
        ! current_user_can('administrator')
        AND ! current_user_can('iw_leadership')
    )
        unset( $all_roles['iw_leadership'] );

    if (
        ! current_user_can('administrator')
        AND ! current_user_can('iw_leadership')
        AND ! current_user_can('groups_administrator')
    )
        unset( $all_roles['groups_administrator'] );

    return $all_roles;
}
function deny_rolechange()
{
    add_filter( 'editable_roles', 'deny_change_to_admin' );
}
add_action( 'after_setup_theme', 'deny_rolechange' );



//Expand Author Drop down to include Group Leaders, and IW Leadership


function author_override( $output ) {
    global $post, $user_ID;

    // return if this isn't the theme author override dropdown
    if (!preg_match('/post_author_override/', $output)) return $output;

    // return if we've already replaced the list (end recursion)
    if (preg_match ('/post_author_override_replaced/', $output)) return $output;
// Add Additional Roles to Author List

//    $userarray = get_users(array( 'role' => 'group_leader' ) );
//    $userlist = implode(",",$userarray);

$role = array('group_leader','administrator','author','iw_leadership');

$query_users_ids_by_role = [
    'fields' => ['id'],
    'role__in' => $role
];

$array_of_users = get_users( $query_users_ids_by_role );

$array_of_users_ids = array_map(function ($user) {
    return $user->id;
}, $array_of_users);

$userslist = implode( ',', $array_of_users_ids );


    // replacement call to wp_dropdown_users
      $output = wp_dropdown_users(array(
        'echo' => 0,
	'include' => $userslist,
	'orderby' => 'display_name',
        'name' => 'post_author_override_replaced',
        'selected' => empty($post->ID) ? $user_ID : $post->post_author,
        'include_selected' => true,
      ));

      // put the original name back
      $output = preg_replace('/post_author_override_replaced/', 'post_author_override', $output);

    return $output;
}

add_filter('wp_dropdown_users', 'author_override');


// add_action( 'user_register', 'iw_reg_role',10,1);

add_action( 'user_register', 'iw_reg_role',10,1);

function iw_reg_role($user_id){

$user_id = wp_update_user( array( 'ID' => $user_id, 'role' => 'pending' ) );

if ( is_wp_error( $user_id ) ) {
	// There was an error, probably that user doesn't exist.
	echo 'error';
} else {
	echo ' success ';
	// Success!
}

/*

	global $ultimatemember;
	// $user_id should be the User ID
	um_fetch_user( $user_id );
	$um_role = $ultimatemember->user->get_role_name();
        // If the user is an Unverified Member we can downgrade them from Subscriber

	$unwanted_role = array('subscriber');

        $user_meta=get_userdata($user_id);
        $user_roles=$user_meta->roles;

	if (($um_role == 'Unverified Member')  && (array_intersect($unwanted_role, $user_roles) ) ){
	// If the User is unverified then we must demote them.
		$ur = new WP_User ($user_id);
		$ur->remove_role( 'subscriber' );
	}

*/
}

// just here so I remember the syntax
//  wp_redirect( home_url(‘/whereto/‘) ;

add_shortcode('iw_subscribe','forcesubscribe');

/* FORCE SUBSCRIBE
  * Forces Role to Subscriber
  * Use with CAUTION
*/
function forcesubscribe($args){

$rolearray = array('pending_member_validation','group_leader','group_member','iw_leadership','groups_administrator','administrator');
$args =  array('role__in' => $rolearray);
$userlist = get_users($args);
foreach($userlist as $user){
   $user->add_role('subscriber');
 }
}

function promote_user($userid,$newrole=null){

  global $ultimatemember;
  $user_id = $userid;
  $user = get_user_by('id',$user_id);
  $user_role = $user->roles;
  $user_meta=get_userdata($user_id);
  if(empty($newrole)){
    $new_role = "group_member";
  } else {
    $new_role = $newrole;
  }

  // Update Role

  um_fetch_user($user_id);
  //  $um_role = $ultimatemember->user->get_role_name();
  $um_role = um_user('role');
  $ur = new WP_User ($user_id);

  // Add role: Only works if User was previously Unverified Member - All other Promotions must be manual
  $allowed_roles = array('unverified-member','subscriber','pending_member_validation');
  if( (array_intersect($allowed_roles, $user_role )) ) {
	$ur->remove_role('unverified-member');
	$ur->remove_role('pending_member_validation');
    	$ur->add_role( $new_role );

  //  $user_id = wp_update_user( array( 'ID' => $user_id, 'role' => $new_role ) );
    $ultimatemember->user->set_role( 'group-member' );
    return result('pass',$user);
  }
  else
  {
  // There was an error, probably that user doesn't exist.
    return result($um_role,$user); 

}


}

function confirm_email($content=null){
	global $ultimatemember;
 	$current_user = wp_get_current_user();
	$user_id = $current_user->ID;
	$user_role = $current_user->roles;
	$user_meta=get_userdata($user_id);
//	$user_roles=$user_meta->roles;
	$new_role = "subscriber";

	// Update Role

	um_fetch_user($user_id);
//	$um_role = $ultimatemember->user->get_role_name();
	$um_role = um_user('role');
	$ur = new WP_User ($user_id);

	// Add role
	$allowed_roles = array('editor', 'administrator', 'author', 'subscriber','contributor');
	if( !(array_intersect($allowed_roles, $user_role )) ) {
	   // If they users didn't already have a role, then give them a new role
		if ($um_role == "unverified-member"){
		$ur->set_role( $new_role );
		$user_id = wp_update_user( array( 'ID' => $user_id, 'role' => 'subscriber' ) );
		$ultimatemember->user->set_role( 'subscriber' );
		return result('pass',$current_user);
	}
	else
	{
	// There was an error, probably that user doesn't exist.
		return result($um_role,$current_user);	
	}

}

}

function result($arg,$usr){

	if ($arg == 'pass'){

		$usrID = $usr->ID;
		$user_meta=get_userdata($usrID);

		$user_roles=$user_meta->roles;
		$subStatus = 'Not Subscribed';
		if (in_array('subscriber', $user_roles)){
			$subStatus = 'Subscriber';
		}

		$rtString = "Thank you for confirming your email address. Your account is now active and you are a member. Please <a href='./mygroups'>Click Here to Continue.</a>";
	} else {
		$rtString = "There is no need to confirm your email at this time, as you are a " . $arg;
	}
		return $rtString;

}

add_shortcode('iw_activate','confirm_email');



if (!function_exists('write_log')) {
    function write_log ( $log )  {
        if ( true === WP_DEBUG ) {
            if ( is_array( $log ) || is_object( $log ) ) {
                error_log( print_r( $log, true ) );
            } else {
                error_log( $log );
            }
        }
    }
}
