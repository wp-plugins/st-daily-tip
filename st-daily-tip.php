<?php
/*
Plugin Name: St-Daily-Tip
Plugin URI: http://wordpress.org/extend/plugins/st-daily-tip/
Description: A plugin to automatically refresh daily tip from a list uploaded from CSV file.
if (function_exists('add_daily_tip')) {
		print add_daily_tip('[stdailytip group="Tip"]');
	}
	?>
Version: 0.6
Author: Dhara Shah
Author URI: http://sanskrutitech.in/
License: GPL
*/
define('WP_DAILY_TIP_VERSION', "0.6");
define('WP_DAILY_TIP_FOLDER', dirname(plugin_basename(__FILE__)));
define('WP_DAILY_TIP_URL', plugins_url('',__FILE__));


/*add_filter('the_content','add_daily_tip');

function add_daily_tip($text)
{
	$today_tip = select_today_tip();
	$text = str_replace("[stdailytip]", $today_tip, $text);
	return $text;
}*/

add_shortcode( 'stdailytip', 'add_daily_tip');
function add_daily_tip($attr)
{
	global $group;
	if(isset($attr['group']))
	{
		$group = $attr['group'];
	}
	else
	{
		$group = "Tip";
	}
	$today_tip = select_today_tip($group);	
	return $today_tip;
}
?>
<?php
/* Runs when plugin is activated */
register_activation_hook(__FILE__,'st_daily_tip_install'); 

/* Runs on plugin deactivation*/
register_deactivation_hook( __FILE__, 'st_daily_tip_uninstall' );

global $st_daily_tip_db_ver;
global $table_suffix;

$st_daily_tip_db_ver = "0.6";
$table_suffix = "dailytipdata";

function select_today_tip($group){
	global $wpdb;
	global $table_suffix;
	
	$table_suffix = "dailytipdata";
	$table_name = $wpdb->prefix . $table_suffix;
	
	//Case 1 : If a tip is set to display today (date), display it
	$tips = $wpdb->get_row("SELECT * FROM $table_name WHERE (DATE(Display_Date)=DATE(NOW()) OR DATE(Shown_Date)=DATE(NOW())) AND group_name='$group';", ARRAY_A);
	if($tips['tip_text'] == null) 
	{ 	
		//Case 2: No tip is set to specifically display today, then select a tip that is to be displayed based on Day
		$tips = $wpdb->get_row("SELECT * FROM $table_name WHERE (Display_Date='0000-00-00' AND Display_Day = DAYOFWEEK(NOW()) AND group_name='$group') ORDER BY Shown_Date;", ARRAY_A);
		if($tips['tip_text'] == null) 
		{
			//Case 3: No tip is set to specifically display today(date or day), then select a tip where Shown Date is null or today
			$tips = $wpdb->get_row("SELECT * FROM $table_name WHERE (Display_Date='0000-00-00' AND Display_Day = 0 AND Shown_Date='0000-00-00' AND group_name='$group');", ARRAY_A);
			if($tips['tip_text'] == null) 
			{ 	
				//Case 4: No tip is set to specifically display today, and no tip found that is not shown then select the oldest tip that is not set to display for a specific date
				$tips = $wpdb->get_row("SELECT * FROM $table_name WHERE Display_Date='0000-00-00' AND group_name='$group' ORDER BY Shown_Date;", ARRAY_A, 0); 
				if($tips['tip_text'] == null) 
				{
					//Case 5: Show one default tip 
					$today_tip = "No Tips to Display";
				}
			}
		}
	}
	if($tips['tip_text'] != null) 
	{
		$today_tip = $tips['tip_text'];
		$wpdb->query("UPDATE $table_name SET Shown_Date = DATE(NOW()) WHERE ID = " . $tips['id']);
		
		if($tips['Display_yearly']!=null)
		{
			global $yearlaterdate;
			$yearlaterdate=date('Y-m-d', strtotime('+1 year'));
			
			$wpdb->query("UPDATE $table_name SET display_date = '$yearlaterdate' WHERE ID = " . $tips['id']);
		}
	}
	return $today_tip; 
}

function st_daily_tip_install(){
	global $wpdb;
	global $table_suffix;
	global $st_daily_tip_db_ver;
	
	$table_name = $wpdb->prefix . $table_suffix;

	$db_ver=get_option('st_daily_tip_db_ver',0);
	$db_ver=(float) $db_ver;
	/** If Updating from an older version */
	if($db_ver!=0 && $db_ver < $st_daily_tip_db_ver)
	{
		if($db_ver < 0.5){
			$wpdb->query("alter table ". $table_name ." add column Display_yearly text NOT NULL");
		}
		$wpdb->query("alter table ". $table_name ." add column group_name varchar(20) NOT NULL");
		update_option("st_daily_tip_db_ver", $st_daily_tip_db_ver);
	}
	/* If new installation*/ 
	else{
		$sql = "CREATE TABLE " . $table_name . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			added_date date DEFAULT '0000-00-00' NOT NULL,
			tip_text text NOT NULL,
			group_name varchar(20) NOT NULL,
			Display_yearly text NOT NULL,
			display_date date DEFAULT '0000-00-00' ,
			shown_date date DEFAULT '0000-00-00' ,
			display_day int(2),
			PRIMARY KEY id (id)
		);";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	
		add_option("st_daily_tip_db_ver", $st_daily_tip_db_ver);
	}
}

function st_daily_tip_uninstall () {
	/* do nothing */
} 

?>
<?php
if ( is_admin() )
{
	require_once dirname( __FILE__ ) . '/adminDailyTip/adminDailyTip.php';
	
	/* add  css and js */
	add_action('admin_print_scripts', 'add_admin_scripts');
}

function add_admin_scripts()
{
	wp_register_script('sortable.js',WP_DAILY_TIP_URL.'/scripts/sortable.js');
	wp_enqueue_script('sortable.js');
	wp_register_script('checkuncheck.js',WP_DAILY_TIP_URL.'/scripts/checkuncheck.js');
	wp_enqueue_script('checkuncheck.js');
	wp_register_style('style.css',WP_DAILY_TIP_URL.'/css/style.css');
	wp_enqueue_style('style.css');
}
?>
<?php
/*add widget*/
require_once dirname( __FILE__ ) . '/widgetDailytip/widgetDailytip.php';
?>