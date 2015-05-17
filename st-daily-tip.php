<?php
/*
Plugin Name: St-Daily-Tip
Plugin URI: http://wordpress.org/extend/plugins/st-daily-tip/
Description: A plugin to automatically refresh daily tip from a list uploaded from CSV file.
Version: 3.0
Author: Sanskruti Technologies
Author URI: http://sanskrutitech.in/
License: GPL
*/
define('WP_DAILY_TIP_VERSION', "3.0");
define('WP_DAILY_TIP_FOLDER', dirname(plugin_basename(__FILE__)));
define('WP_DAILY_TIP_URL', plugins_url('',__FILE__));

/* Load Language */
add_action( 'plugins_loaded', 'st_dailytip_load_textdomain' );

function st_dailytip_load_textdomain() {
	load_plugin_textdomain('stdailytip', false,  dirname( plugin_basename( __FILE__ ) ) . "/language/");
}

add_shortcode( 'stdailytip', 'show_daily_tip');
add_shortcode( 'stdailytiplist', 'show_daily_tip_list');

function show_daily_tip($atts){

	extract( shortcode_atts( array(
		'group' => 'Tip',
		"date"=> "hide",
		"title"=> "show",
	), $atts ) );
	
	 
	return add_daily_tip($group,$date,$title);
}
function add_daily_tip($grp,$date,$title)
{
	
	if(isset($grp))
	{
		$group = $grp;
	}
	else
	{
		$group = "Tip";
	}
	
	$today_tip = select_today_tip($group,$date,$title);	
	
	return $today_tip;
}
function show_daily_tip_list(){
	global $wpdb;
	global $table_suffix;

	$tipresult = "";
	$table_name = $wpdb->prefix . $table_suffix;
	
	$todate = current_time('mysql',0);
	
	//select all tips shown already
	$table_result = $wpdb->get_results("SELECT * FROM $table_name WHERE shown_date != '0000-00-00' AND DATE(shown_date)<DATE('$todate') ORDER BY DATE(shown_date) DESC;");

	foreach ( $table_result as $table_row )
	{
		$item_tip = $table_row->tip_text;
		$item_title = $table_row->tip_title;
		$item_lastshown = $table_row->shown_date;
		$formatted_item_lastshown = date("d-m-Y", strtotime($item_lastshown));
		if ($item_tip != null) {
			$tipresult .= "<div class='single_tip'><div class='tip_title'>" . $formatted_item_lastshown . ": " . $item_title . "</div><div class='tip_text'>" . $item_tip . "</div></div>";
		}
	}

	return $tipresult;
}

/* Runs when plugin is activated */
register_activation_hook(__FILE__,'st_daily_tip_install'); 

/* Runs on plugin deactivation*/
register_deactivation_hook( __FILE__, 'st_daily_tip_uninstall' );

global $st_daily_tip_db_ver;
global $table_suffix;

$st_daily_tip_db_ver = "1.6";
$table_suffix = "dailytipdata";

function select_today_tip($group,$date,$title){
	
	
	
	global $wpdb;
	global $table_suffix;
	
	$table_suffix = "dailytipdata";
	$table_name = $wpdb->prefix . $table_suffix;
	
	$todate = current_time('mysql',0);
	
	//Case 1 : If a tip is to be selected to display today (last shown date), display it
	$sql = "SELECT * FROM $table_name WHERE DATE(Shown_Date)=DATE('$todate') AND group_name='$group';";
	$tips = $wpdb->get_row($sql, ARRAY_A);
	if($tips['tip_text'] == null) 
	{
		//Case 2 : If the display Date is set for today, display today
		$sql = "SELECT * FROM $table_name WHERE (DATE(Display_Date)=DATE('$todate') OR DATE(Shown_Date)=DATE('$todate')) AND group_name='$group';";
		$tips = $wpdb->get_row($sql, ARRAY_A);
		
		if($tips['tip_text'] == null) 
		{ 	
			//Case 3: No tip is set to specifically display today, then select a tip that is to be displayed based on Day
			$sql = "SELECT * FROM $table_name WHERE (Display_Date='0000-00-00' AND Display_Day = DAYOFWEEK('$todate') AND group_name='$group') ORDER BY Shown_Date;";
			$tips = $wpdb->get_row($sql, ARRAY_A);
			
			if($tips['tip_text'] == null) 
			{
				//Case 4: No tip is set to specifically display today(date or day), then select a tip where Shown Date is null or today
				$sql = "SELECT * FROM $table_name WHERE (Display_Date='0000-00-00' AND Display_Day = 0 AND Shown_Date='0000-00-00' AND group_name='$group');";
				$tips = $wpdb->get_row($sql, ARRAY_A);
				
				if($tips['tip_text'] == null) 
				{ 	
					//Case 5: No tip is set to specifically display today, and no tip found that is not shown then select the oldest tip that is not set to display for a specific date
					$sql = "SELECT * FROM $table_name WHERE Display_Date='0000-00-00' AND group_name='$group' ORDER BY Shown_Date;";
					$tips = $wpdb->get_row($sql, ARRAY_A, 0); 
					
					if($tips['tip_text'] == null) 
					{
						//Case 6: Show one default tip 
						$today_tip = "No Tips to Display";
					}
				}
			}
		}
	}
	
	if($tips['tip_text'] != null) 
	{	
			
		$wpdb->query("UPDATE $table_name SET Shown_Date = DATE('$todate') WHERE ID = " . $tips['id']);
		
		// If Tips needs to be displayed yearly, update the next dis
		if($tips['Display_yearly']=='on')
		{
			$nextdate=date_create($todate);
			$nextyear = $nextdate->format('Y') + 1;
			$nextdate =  $nextyear . $nextdate->format('-m-d');
			$wpdb->query("UPDATE $table_name SET display_date = '$nextdate' WHERE ID = " . $tips['id']);
		}
		if ($tips['tip_title'] != null && $title == "show")
		{
			
			if($date=="show")
			{	
				$dat=$tips['shown_date'];
				$show_date=date(get_option("st_daily_date_format"),strtotime($dat));
				return "<div class='tip_container'><div class='tip_date'>Date: ".$show_date . "</div><div class='tip_title'>" .$tips['tip_title'] . "</div><div class='tip_text'>" .$tips['tip_text'] ."</div></div>";
			}
			else
			{
				return "<div class='tip_container'><div class='tip_title'>" .$tips['tip_title'] . "</div><div class='tip_text'>" .$tips['tip_text'] . "</div></div>";
			}
		}
		else
		{
			if($date=="show")
			{	
				$dat=$tips['shown_date'];
				$show_date=date(get_option("st_daily_date_format"),strtotime($dat));
				return "<div class='tip_container'><div class='tip_text'>" .$tips['tip_text'] . "</div><div class='tip_last_shown'> Last Shown Date: ".$show_date."</div></div>";
			}
			else
			{
				return "<div class='tip_container'><div class='tip_text'>" .$tips['tip_text'] . "</div></div>";
			}
		}
	}else{
		return "<div class='tip_container'><div class='tip_text'>$today_tip</div></div>";
	}
	
	
	
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
		//had missed to add group_name in 0.6
		if($db_ver <= 0.6){
			$wpdb->query("alter table ". $table_name ." add column group_name varchar(20) NOT NULL");
		}
		if($db_ver < 0.5){
			$wpdb->query("alter table ". $table_name ." add column Display_yearly text NOT NULL");
		}
		if($db_ver < 1.5){
			$wpdb->query("alter table ". $table_name ." change tip_text tip_text TEXT CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL");
		}
		if($db_ver < 1.6){
			$wpdb->query("alter table ". $table_name ." add column tip_title text");
		}
		update_option("st_daily_tip_db_ver", $st_daily_tip_db_ver);
	}
	/* If new installation*/ 
	else{
		$sql = "CREATE TABLE " . $table_name . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			added_date date DEFAULT '0000-00-00' NOT NULL,
			tip_title text,
			tip_text text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
			group_name varchar(20) NOT NULL,
			Display_yearly text NOT NULL,
			display_date date DEFAULT '0000-00-00' ,
			shown_date date DEFAULT '0000-00-00' ,
			display_day int(2),
			PRIMARY KEY id (id)
		) ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_general_ci;";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	
		add_option("st_daily_tip_db_ver", $st_daily_tip_db_ver);
		add_option("st_daily_date_format", 'yy-mm-dd');
	}
}

function st_daily_tip_uninstall () {
	/* do nothing */
} 

/********************************Download CSV ***********************************/
function st_daily_tip_csv_export() {
	global $wpdb;
	global $table_suffix;
	
	$table_suffix = "dailytipdata";
	$table_name = $wpdb->prefix . $table_suffix;
	
	$qry = "SELECT tip_title,tip_text,display_date,display_day,group_name,Display_yearly FROM $table_name";
	$result = $wpdb->get_results($qry, ARRAY_A);
	
	if ($wpdb->num_rows > 0) 
	{
		// Make a DateTime object and get a time stamp for the filename
		$date = new DateTime();
		$ts = $date->format("Y-m-d-G-i-s");
		
		// A name with a time stamp, to avoid duplicate filenames
		$filename = "dailytips-$ts.csv";
		
		// Tells the browser to expect a CSV file and bring up the
		// save dialog in the browser
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header('Content-Description: File Transfer');
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment;filename='.$filename);
		header("Expires: 0");
		header("Pragma: public");
		
		// This opens up the output buffer as a "file"
		$fp = fopen('php://output', 'w');
		
		// Get the first record
		$hrow = $result[0];

		// Extracts the keys of the first record and writes them
		// to the output buffer in CSV format
		fputcsv($fp, array_keys($hrow));
		
		// Then, write every record to the output buffer in CSV format            
		foreach ($result as $data) {
			fputcsv($fp, $data);
		}
		
		// Close the output buffer (Like you would a file)
		fclose($fp);
		// Make sure nothing else is sent, our file is done
		exit;
	}
}
/********************************Download CSV ***********************************/

?>
<?php
if ( is_admin() )
{
	require_once dirname( __FILE__ ) . '/st-daily-tip-admin.php';
	require_once dirname( __FILE__ ) . '/st-daily-tip-export-csv.php';
	
	/* add  css and js */
	add_action('admin_print_scripts', 'add_admin_scripts');
}

function add_admin_scripts()
{
	wp_enqueue_script('jquery');
	
	wp_register_script('sortable.js',WP_DAILY_TIP_URL.'/scripts/sortable.js');
	wp_enqueue_script('sortable.js');
	
	wp_register_script('dailytip_checkuncheck.js',WP_DAILY_TIP_URL.'/scripts/checkuncheck.js');
	wp_enqueue_script('dailytip_checkuncheck.js');

	wp_register_style('jquery-ui-datepicker.css', WP_DAILY_TIP_URL.'/css/jquery-ui-datepicker.css');
	wp_enqueue_style('jquery-ui-datepicker');
		
	wp_register_style('st-daily-tip-style.css',WP_DAILY_TIP_URL.'/css/style.css');
	wp_enqueue_style('st-daily-tip-style.css');
}
?>
<?php
/*add widget*/
require_once dirname( __FILE__ ) . '/st-daily-tip-widget.php';
?>