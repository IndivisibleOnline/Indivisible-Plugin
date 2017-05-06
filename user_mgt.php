<?php
/**
 * Get taxonomies terms links.
 *
 * @see get_object_taxonomies()
 */

function get_group_info($id=null) {
global $post;
// global $out;
    // Get post by post ID.
    if (!empty($id)){
	$post = get_post($id);
	}
    else{
	$post = get_post();
	}
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
		$out["type"] =  $taxonomy_slug;
                $out["term"]= $term->name;
		$out["id"]= $term->term_id;
            }
        }
    }
    return $out;
}


function get_group_details() {
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

	return $fb;
}

add_shortcode('group_details','get_group_details');


/*
 NAME: get_current_group (Params: Group Type)
 FUNCTION: Get the ID of the Group Associated with the Current Page
 RETURNS: Group ID
*/

function get_current_group($group_type=null){
global $post;
	$post = get_post();
	$cat = get_group_info();
	$group_type = $cat['type'];

switch ($group_type) {
    case 'topic':
    	$group_type = 'topic_groups';
    	break;
    case 'local':
    	$group_type = 'local_groups';
    	break;
    default:
    	$group_type = 'topic_groups';
    }

// Extract the slug from the URL
// $slug = pods_v('first','url' ); 

	 	$params = array(
		'where' => 't.term_id =' . $cat['id'],
	
                );

$slug = $cat['term'];

$pods = pods($group_type, $params);

$currentid='';
while ($pods->fetch() ) {

    	$title = $pods->display('name');
	$id = $pods->display('id');
	$tid = $pods->display('term_id');
	$thisslug = $pods->display('slug');
	
	if ($thisslug == $slug){
	$currentid = $id;
	}
}
return $currentid;
}

/*
 NAME: get_local_group (Params: UserID)
 FUNCTION: GET LOCAL GROUP of a User (defaults to current user)
 RETURNS: Local Group ???
*/

function get_local_group($userid=null){
// Gets the Users Local Group
// Defaults to Current User

	if (!$userid){
		global $current_user;
        wp_get_current_user();
		$userid = $current_user->ID;
	}

        $tpod = pods("user", $userid);
        $trelated = $tpod->field( 'local_group' );
        return $trelated;
}

/*
 NAME: get_topic_group (Params: UserID)
 FUNCTION: GET LOCAL GROUP of a User (defaults to current user)
 RETURNS: array of Groups
*/
function get_topic_groups($userid=null){
// Gets the Current User's Topic Groups

	if (!$userid){
		global $current_user;
        wp_get_current_user();
		$userid = $current_user->ID;
	}

      // Topic Group
	$tpod = pods("user", $userid);
	$trelated = $tpod->field( 'topic_groups' );
	$usergroups =array();
	if (count($trelated) > 0){
  	   foreach($trelated as $group) {
	        $id = $group[ 'term_id' ];
	 	array_push($usergroups,$id);
	  }
	}
	return $usergroups;
}

// Actions used to Join / Leave a Topic Group
add_action('admin_post_jointopicgroup','iw_add_user_to_topicgroup');
add_action('admin_post_nopriv_jointopicgroup','iw_add_user_to_topicgroup');

add_action('admin_post_leavetopicgroup','iw_remove_user_from_topicgroup');
add_action('admin_post_nopriv_leavetopicgroup','iw_remove_user_from_topicgroup');


/*
 NAME: iw_add_user_to_topicgroup (Params: groupID from post or parameter)
 FUNCTION: Adds the current user to a topic group
 RETURNS: Nothing
*/

function iw_add_user_to_topicgroup($gid=null){
	global $current_user;
	wp_get_current_user();
	
// Can Set GROUP (POD) ID through form data (pid) or passed argument

if (!empty($_POST['pid'])){
	$gid = $_POST['pid'];
}
	// Returns the ID of the Pod associated with current page
	$currentpod = $gid;
	if ($currentpod == ''){
		echo "there was an error. Sorry";
	}
	else {
	$upod = pods("user", $current_user->ID);
	$related = $upod->field( 'topic_groups' );
	$newdata = array('term_id'=>$currentpod);
	$upod->add_to('topic_groups',$newdata);

	wp_redirect( wp_get_referer() ); exit;
}

}

/*
 NAME: iw_remove_user_to_topicgroup (Params: groupID from post or parameter)
 FUNCTION: Removes the current user to a topic group
 RETURNS: Nothing
*/

function iw_remove_user_from_topicgroup($gid = null){
	global $current_user;
	wp_get_current_user();
	
	if (!empty($_POST['pid'])){
	$gid = $_POST['pid'];
	}

	$currentpod = $gid;
	if ($currentpod == ''){
		echo "there was an error. Sorry";
	}
	else {
	$upod = pods("user", $current_user->ID);
	$related = $upod->field( 'topic_groups' );
	$newdata = array('term_id'=>$currentpod);
	$upod->remove_from('topic_groups',$newdata);

 wp_redirect( wp_get_referer() ); exit;
}

}

/*
 NAME: iw_ispending (Params: None)
 FUNCTION: Identifies Users who are currently pending validation
 RETURNS: Array of Usre IDs who are pending validation
*/
function iw_ispending(){

	//returns users that are pending approval

$args = array(
    'role'          =>  'subscriber',
    'meta_key'      =>  'account_status',
    'meta_value'    =>  'pending'
);

$pending = get_users($args);

$user_array = array();

foreach($pending as $pendinguser) {
	array_push($user_array, $pendinduser->user_id);
}
return $user_array;
}


function is_pending(){
//returns users that are pending approval

$args = array(
    'role'          =>  'subscriber',
    'meta_key'      =>  'account_status',
    'meta_value'    =>  'pending'
);

$pending_users = get_users($args);

return $pending_users;

}



/*
 NAME: add_user_to_Group (Params: UserID, Group Type, Group ID)
 PARAMS:  Group Type: 'Topic' or 'Local'
 FUNCTION: Adds user to a group
 RETURNS: Nothing
*/

function add_user_to_group($uid,$gtype,$grpid){
// Arguments are UserID, Group Type (topic or local), and Group ID

global $current_user;
if( $gtype == 'topic' ) {
	$grouptype = 'topic_groups';
	} else {
	$grouptype = 'local_group';
	}

    $userid = $uid;   
	$user = get_user_by('id',$userid); 
	$user_role = $user->roles;

	$tpod = pods("user", $userid);
	$related = $tpod->field( $grouptype );
	$newdata = array('term_id'=>$grpid);
	$tpod->add_to($grouptype,$newdata);

}

/*
 NAME: add_users_by_role (Params: $role)
 FUNCTION: Returns an array of users for a specific role
 RETURNS: Nothing
*/
function get_users_by_role($role){
// Get the List of Users for a specific role
$userlist ='';
 if ($role != ''){
  $args = array(
	'role__in' 	=> $role,
	'orderby'	=> 'login',
	'order' 	=> 'ASC',
	'fields'	=> 'all');

 $userlist = get_users($args);

}
return $userlist;
}

/*
 NAME: iw_assigned (Params: Selected User ID)
 FUNCTION: Identifies whether the Selected User is in same group as current user
 RETURNS: True or False
*/
function iw_assigned($selecteduser){

$suid = get_local_group($selecteduser);
$auid = get_local_group();

$sid = $suid[ 'term_id' ];
$aid = $auid[ 'term_id'];
if ($sid == $aid){
	return true;
	} else {
	return false;
}

}


/*
 NAME: user_role_edit 
 Shortcode: iw-editrole
 FUNCTION: Displays form to approve or assign pending users
 RETURNS: Nothing; Displays forms
*/
add_shortcode('iw-editrole','user_role_edit');
// Build a Form to Enable a GL or Admin to approve members

function user_role_edit( ) {
if (authorized_user()){

	global $ultimatemember;
		if ( current_user_can( 'promote_users' ) ) {

		//	$um_user_role = get_user_meta($user->ID,'role',true);
		$valid_roles = array('subscriber','pending_member_validation');
		get_header();
	
		ob_start();		

		if (authorized_user(array('administrator','groups_administrator'))){ 	

			echo "<strong> ASSIGN USERS TO GROUPS FOR APPROVAL BY GROUP LEADERS</strong>";

?>
			<table class="form-table">
				<tbody>
					<tr>
						<th>  <label for="username">MEMBERSHIP APPROVAL</label>
							<label for="um_role"> & LOCAL GROUP ASSIGNMENT   </label>
						</th> 

						<tr>
						<td>
							<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" name="assign">
							<input type="hidden" name="action" value="assigntogroup" />
							<select name="user" id ="user">
							<?php 
							$pcount = 0;
							foreach(get_users_by_role($valid_roles) as $selecteduser){ ?>
							<?php um_fetch_user($selecteduser->ID);
							$pcount += 1;
							$localname='';
 							if (( $ultimatemember->user->get_role() == 'pending-validation' ) ) 
 									{
									$local = get_local_group($selecteduser->ID);
								 	if ($local){	
									$localname = " @ " .$local['name'];
									}
		
							
							?>
 							<option value="<?php echo $selecteduser->ID; ?>"> <?php echo $selecteduser->user_email . " (" . $selecteduser->display_name .") - [" . getuserroles($selecteduser->ID) . $localname . "]"; ?></option>
							<?php
									}

							 } ?> 
							</select>
							<select name="gid" id="gid">
								<?php
								$groups = get_local_group_list();
								while ($groups->fetch()){
									$gname = $groups->field('name') ;
									$gid = $groups->field('term_id');
									?>
									<option value="<?php echo $gid; ?>"> <?php echo $gname; ?></option>
							<?php
								} ?> </select>
							<?php
														
							if (authorized_user(array('administrator','groups_administrator'))){ 
								echo "<br>This will only assign the user to a Group Leader for Validation.";
							}
							?>
						        <input type="submit" name="approve" value="Assign to Group"></form>
						</td>
					</tr>
				</tbody>
			</table><br><br><br>
		<?php 
		}

	
				if (authorized_user(array('administrator','groups_administrator'))){ 	

				echo "<strong> APPROVE & ASSIGN USERS TO GROUPS (WITHOUT APPROVAL BY GROUP LEADER)</strong>";

				}
		?>
			<table class="form-table">
				<tbody>
					<tr>
						<th>  <label for="username">MEMBERSHIP APPROVAL</label>
							<label for="um_role"> & LOCAL GROUP ASSIGNMENT   </label>
						</th> 

						<tr>
						<td>
							<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" name="confirm">
							<input type="hidden" name="action" value="confirmapproval" />
							<select name="user" id ="user">
							<?php 
							$pcount = 0;
							foreach(get_users_by_role($valid_roles) as $selecteduser){ ?>
							<?php um_fetch_user($selecteduser->ID);
							$pcount += 1;
 							if (( $ultimatemember->user->get_role() == 'pending-validation' ) && (iw_assigned($selecteduser->ID)) ) 
 									{
 								?>
									<option value="<?php echo $selecteduser->ID; ?>"> <?php echo $selecteduser->user_email . " (" . $selecteduser->display_name .") - [PENDING]"; ?></option>
							
							<?php  	} elseif (authorized_user(array('administrator','groups_administrator'))){ 
								?>
									<option value="<?php echo $selecteduser->ID; ?>"> <?php echo $selecteduser->user_email . " (" . $selecteduser->display_name .") - [" . getuserroles($selecteduser->ID) . "]"; ?></option>
							<?php
									}

							 } ?> 
							</select>
							<?php 
							if (authorized_user(array('administrator','groups_administrator'))){ 
								?>
								<select name="group" id="group">
								<?php
								$groups = get_local_group_list();
								while ($groups->fetch()){
									$gname = $groups->field('name') ;
									$gid = $groups->field('term_id');
									?><option value="<?php echo $gid; ?>"> <?php echo $gname; ?></option>
							<?php
								} ?> </select><?php
							 } else {
								 $group = get_local_group();
								 $gname = $group['name'] ;
								 $gid = $group['term_id'];
								?><input type="hidden" name="group" value="<?php echo $gid; ?>"/> <br>Approve and assign to <?php echo $gname; ?>
								<?php	
							
							}
							
							if (authorized_user(array('administrator','groups_administrator'))){ 
								echo "<br>This will approve and assign the user.";
							}
							?>
						        <input type="submit" name="approve" value="Approve Membership"></form>
						</td>
					</tr>
				</tbody>
			</table>
		<?php }
ob_end_flush();
}
}

function user_role_confirm($gid=null) {

if (authorized_user()){

global $current_user;
wp_get_current_user();


if (empty ($_POST['group'])){

$group = get_local_group($current_user->ID);
$gname = group['name'];

} else {

$group = pods("local_groups",$_POST['group']);
$gname = $group->display('name');
$gid = $group->display('term_id');


}


$user = $_POST['user'];

	global $ultimatemember;
		if ( current_user_can( 'promote_users' ) ) {

		$valid_roles = array('subscriber','pending_member_validation');
		$selecteduser = get_user_by('ID',$user);


?>
<br><br>
			<table class="form-table">
				<tbody>
							<th>  <label for="username">MEMBERSHIP APPROVAL</label>
							<label for="um_role"> & LOCAL GROUP ASSIGNMENT   </label>
						</th> 


					<tr>
					<tr>
						<td>
							<form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post" name="approve">
							<input type="hidden" name="action" value="approveuser" />
							<input type="hidden" name="user" id ="user" value="<?php echo $selecteduser->ID; ?>" />
						    <?php echo $selecteduser->user_email . " (" . $selecteduser->display_name .")"; ?>
							<?php echo "<br>is being approved as a member of " . $gname; ?>
							<input type="hidden" name="gid" value= "<?php echo $gid ?>"/>
						<input type="submit" name="approve" value="CONFIRM APPROVAL">
						</form>
						<br><br><a href="<?php echo home_url(); ?>/membershipapproval">CANCEL</a>
						</td>
					</tr>
				</tbody>
			</table>
		<?php }
}
}

add_action('admin_post_confirmapproval','user_role_confirm');
add_action('admin_post_nopriv_confirmapproval','user_role_confirm');



function user_role_approve($gid=null) {

   if (authorized_user()){

	if (empty ($_POST['gid'])){

		echo "SORRy, I can't approve a user without assigning to a local group. Try again!";
		die();

	}else {

	$user = $_POST['user'];
	$gid = $_POST['gid'];



		if ( current_user_can( 'promote_users' ) ) {

			//Promote Users WP & UM Role
			promote_user($user);
			
			//Approve Account to trigger Notification	
			global $ultimatemember;
			um_fetch_user( $user );
			$ultimatemember->user->pending();
 			$ultimatemember->user->approve();

			// Set UAM Permissions
			// THis is done automatically since ROle and UAM group are related.

			// Add User to Local Group
			add_user_to_group($user,'local',$gid);

		
		echo "Success" . " <a href='" . get_site_url() . "/membershipapproval'>Click to Continue</a>";

		 }
	}
   }
}

add_action('admin_post_approveuser','user_role_approve');
add_action('admin_post_nopriv_approveuser','user_role_approve');



add_action('admin_post_assigntogroup','user_assign');
add_action('admin_post_nopriv_assigntogroup','user_assign');


function user_assign($gid=null) {

   if (authorized_user()){

	if (empty ($_POST['gid'])){

		echo "SORRy, I can't approve a user without assigning to a local group. Try again!";
		die();

	}else {

	$user = $_POST['user'];
	$gid = $_POST['gid'];

		if ( current_user_can( 'promote_users' ) ) {

			// Add User to Local Group without Approving or Promoting their membership
			$current = get_local_group($user);
			if ($current){
				$currentid = $current['term_id'];
				$upod = pods("user",$user);
			//	$oldpod = $upod->field('local_group');
				$olddata = array('term_id'=>$currentid);
				$upod->remove_from("local_group",$olddata);
			}
			add_user_to_group($user,'local',$gid);
			// get group_leader($gid);
			// send notification to group leader
			$pods = pods("local_groups", $gid);
			$lpod = $pods->field('leaders');
			$emails='';
			foreach($lpod as $ll){
				$emails .= $ll[ 'user_email'] . ",";
			}
	//		echo $emails;
			$message = "A new user has been assigned to your group. Please check the site for additional information.";
			wp_mail($emails,'New User Pending Review',$message);
			echo "<br><br>User successfully assigned." . " <a href='" . get_site_url() . "/membershipapproval'>Click to Continue</a>";

		 }
	}
   }
}


/*
 NAME: getuserroles (Params: UserID)
 FUNCTION: Returns a comma delimited list of roles for a specific user
 RETURNS: Comma-delimited list 
*/
function getuserroles($uid){
// Return the roles for a specific user (separated by commas)
$str_roles = '';
$user_meta=get_userdata($uid);
$user_roles = $user_meta->roles;
foreach($user_roles as $role){
 if($str_roles != '') {$str_roles .= ", "; }
  $str_roles .= $role;
}
return $str_roles;
}



// Let's first allow shortcodes in text widgets
add_filter ( 'widget_text', 'do_shortcode' );

// Display content only to logged-in users:
// [member]content[/member]
// Display content to users of Admin role:
// [member role='admin']content[/member]
// Display content to users NOT of Admin role:
// [member role='!admin']content[/member]
// Display content to users of Admin or Premium Member roles:
// [member role='admin,premium member']content[/member]
// Display content to users NOT of Admin or Premium Member roles:
// [member role='!admin,premium member']content[/member]
function um_member_shortcode( $atts, $content = null ) {

  extract( shortcode_atts( array ( 'role' => '' ), $atts ) );

  if ( um_is_member_role ( $role ) ) {

	$result = do_shortcode($content);
	return $result;
  }
  return '';

}
add_shortcode( 'member', 'um_member_shortcode' );

// Display content only to logged-out users:
// [non_member]content[/non_member]
function um_non_member_shortcode ( $atts, $content = null ) {

  if ( !is_user_logged_in() ) {
	return $content;
  }
  return '';

}
add_shortcode ( 'non_member', 'um_non_member_shortcode' );

// Function returns true if current member's role is in specified $role.
function um_is_member_role( $role ) {
  
  // Return true if no user role provided and user is logged in.
  if ( !$role ) {
	if ( is_user_logged_in() ) {
	  return true;
	}
	return false;
  }

  // Role names should be lowercase, let's ensure it.
  $role = strtolower ( $role );

  if ( function_exists( 'um_user' ) ) {
	$negate = false;
	$roles = array();

	// Special handler for negations.
	if ( strpos ( $role , "!" ) !== false ) {
	  $negate = true;
	  $role = str_replace ( '!' , '' , $role );
	}

	// Ensure we're working with an array, split $role by commas.
	if ( strpos ( $role , "," ) !== false ) {
	  $roles = explode ( ',' , trim ( $role ) );
	} else {
	  array_push ( $roles , trim ( $role ) );
	}

	// Get logged in user's role.
	$um_role = um_user ( 'role' );

	// Handle negations first.
	if ( $negate ) {
	  if ( !in_array ( $um_role , $roles ) ) {
		return true;
	  }
	} else {

	  // Display content to users of specified role.
	  if ( in_array ( $um_role , $roles ) ) {
		return true;
	  }
	}
  }
  return false;
}


add_shortcode('iw_updaterole','iw_updatemembership');

/*
 NAME: iw_updatemembership (Params: Role to Promote to)
 PARAMS:  ROle must be membershippending
 FUNCTION: If existing role is subscriber then it changes CURRENT USER's role to pending
 RETURNS: Nothing
*/

function iw_updatemembership($atts) {

 extract( shortcode_atts( array ( 'newrole' => '' ), $atts ) );
  $newrole = strtolower ( $newrole );
  	// This confirms that the role we should be promoting to is membership pending
 	if($newrole=="membershippending"){

		// Get logged in user's role.
		$um_role = um_user ( 'role' );
		if($um_role == "subscriber"){

  		// Add role: Only works if User was previously Subscriber - All other Promotions must be manual
			global $ultimatemember;
			global $current_user;
			wp_get_current_user();
			um_fetch_user($current_user->ID);
    			$ultimatemember->user->set_role( 'pending-validation' );
			$current_user->add_role('pending_member_validation');
			$current_user->add_role('subscriber');
		//	wp_update_user( array( 'ID' => $current_user->ID, 'role' => 'pending_member_validation' ) );
			return;

		} else {
			 echo "<strong>There's something happening here.... what it is ain't exactly clear. Please contact your Group Leader or Site Administrator.</strong>";
			 return;
		}
 	} 
 	else {
 		echo "Nothing to do here";
 		return;
 	}
 } 
