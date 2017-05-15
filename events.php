<?php
add_shortcode('iw_manage_events','manage_iw_events');

function manage_iw_events(){

if(isset($_POST['approve_public_events'])){

$items = (array)$_POST['event'];
$items = array_map('intval',$items);
$count = 0;
foreach ($items as $item){

  update_post_meta($item,'iw_public_event','approved');
 $count = $count + 1;
}

 echo $count . " events updated.";

} 


// Ensure the global $post variable is in scope
global $post;
echo "<h2>Pending Event Requests</h2>";
echo "<h3>Approving Event Requests will promote them on homepage and Site Events Calendar</h3>"; 
// Retrieve the next 5 upcoming events
$events = tribe_get_events( array(
    'posts_per_page' => 25,
    'start_date' => date( 'Y-m-d H:i:s'),
) );
 
// Loop through the events: set up each one as
// the current post then use template tags to
// display the title and content
echo "<table>";
echo "<td> </td><td width='250'>Title</td><td width='150'>Start Date</td><td width='200'>Venue</td>";
echo "<form method='post'>";
echo "<input type='hidden' name='approve_public_events'value='approve'/>";
foreach ( $events as $post ) {
    	setup_postdata( $post );
	if (get_post_meta($post->ID,'iw_public_event',true) == 'requested'){
 	echo "<tr>";
    // This time, let's throw in an event-specific
    // template tag to show the date after the title!
	echo "<td align=center><input type='checkbox' name='event[]' value='" . $post->ID . "'></td>";
        echo "<td><a href='" .get_post_permalink( $post->post_id) . "'>" . $post->post_title . "</td>";
        echo "<td>" . tribe_get_start_date( $post ) ."</td>";
	echo "<td>"  .  tribe_get_venue( $post ) . "</td>";
//	echo "<td>" .  tribe_get_text_categories($post->post_id) . "</td>";
	echo "</tr>";
	}
        }
echo "<tr><td cols=4><input type='submit' value='approve'/></td></tr>";
echo "</form>";
echo "</table>";
}

if ( class_exists('Tribe__Events__Main') ){

    /* get event category names in text format */
    function tribe_get_text_categories ( $event_id = null ) { 
        if ( is_null( $event_id ) ) {
            $event_id = get_the_ID();
        }

        $event_cats = '';

        $term_list = get_post_meta( $event_id, 'event_categories',true);

        foreach( $term_list as $term_single ) {
            $event_cats .= $term_single->name . ', ';
        }

        return rtrim($event_cats, ', ');

    }

}
