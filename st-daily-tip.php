<?php
/*
Plugin Name: St-Daily-Tip
Plugin URI: http://sanskrutitech.in/index.php/wordpress-plugins/
Description: A plugin to automatically refresh daily tip from a list uploaded from CSV file.
Version: 0.2
Author: Dhara Shah
Author URI: http://sanskrutitech.in/
License: GPL
*/
define('WP_DAILY_TIP_VERSION', "0.2");
define('WP_DAILY_TIP_FOLDER', dirname(plugin_basename(__FILE__)));
define('WP_DAILY_TIP_URL', plugins_url('',__FILE__));

add_filter('the_content','add_daily_tip');

function add_daily_tip($text)
{
	$today_tip = select_today_tip();
	$text = str_replace("[stdailytip]", $today_tip, $text);
	return $text;
}
?>
<?php
/* Runs when plugin is activated */
register_activation_hook(__FILE__,'st_daily_tip_install'); 

/* Runs on plugin deactivation*/
register_deactivation_hook( __FILE__, 'st_daily_tip_uninstall' );

global $st_daily_tip_db_ver;
global $table_suffix;

$st_daily_tip_db_ver = "0.2";
$table_suffix = "dailytipdata";

function select_today_tip(){
	global $wpdb;
	global $table_suffix;
	
	$table_name = $wpdb->prefix . $table_suffix;
	
	//Case 1 : If a tip is set to display today (date), display it
	$tips = $wpdb->get_row("SELECT * FROM $table_name WHERE DATE(Display_Date)=DATE(NOW()) OR DATE(Shown_Date)=DATE(NOW());", ARRAY_A);
	if($tips['tip_text'] == null) 
	{ 	
		//Case 2: No tip is set to specifically display today, then select a tip that is to be displayed based on Day
		$tips = $wpdb->get_row("SELECT * FROM $table_name WHERE (Display_Date='0000-00-00' AND Display_Day = DAYOFWEEK(NOW())) ORDER BY Shown_Date;", ARRAY_A);
		if($tips['tip_text'] == null) 
		{
			//Case 3: No tip is set to specifically display today(date or day), then select a tip where Shown Date is null or today
			$tips = $wpdb->get_row("SELECT * FROM $table_name WHERE (Display_Date='0000-00-00' AND Display_Day = 0 AND Shown_Date='0000-00-00');", ARRAY_A);
			if($tips['tip_text'] == null) 
			{ 	
				//Case 4: No tip is set to specifically display today, and no tip found that is not shown then select the oldest tip that is not set to display for a specific date
				$tips = $wpdb->get_row("SELECT * FROM $table_name WHERE Display_Date='0000-00-00' ORDER BY Shown_Date;", ARRAY_A, 0); 
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
	}
	return $today_tip; 
}

function st_daily_tip_install(){
	global $wpdb;
	global $table_suffix;
	global $st_daily_tip_db_ver;
	
	$table_name = $wpdb->prefix . $table_suffix;
	
	$sql = "CREATE TABLE " . $table_name . " (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		added_date date DEFAULT '0000-00-00' NOT NULL,
		tip_text text NOT NULL,
		display_date date DEFAULT '0000-00-00' ,
		shown_date date DEFAULT '0000-00-00' ,
		display_day int(2),
		PRIMARY KEY id (id)
	);";
	
	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);
	
	add_option("st_daily_tip_db_ver", $st_daily_tip_db_ver);
}

function st_daily_tip_uninstall () {
	/* do nothing */
} 

?>
<?php
if ( is_admin() ){

	/* Call the html code */
	add_action('admin_menu', 'daily_tip_admin_menu');

	function daily_tip_admin_menu() {
		add_options_page('Daily Tips', 'Daily Tips', 'administrator',	'daily-tip', 'daily_tip_option_page');
	}
}

?>
<?php
function check_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function get_abs_path_from_src_file($src_file)
{
	if(preg_match("/http/",$src_file))
	{
		$path = parse_url($src_file, PHP_URL_PATH);
		$abs_path = $_SERVER['DOCUMENT_ROOT'].$path;
		$abs_path = realpath($abs_path);
		if(empty($abs_path)){
			$wpurl = get_bloginfo('wpurl');
			$abs_path = str_replace($wpurl,ABSPATH,$src_file);
			$abs_path = realpath($abs_path);			
		}
	}
	else
	{
		$relative_path = $src_file;
		$abs_path = realpath($relative_path);
	}
	return $abs_path;
}
function readAndDump($src_file,$table_name,$column_string="",$start_row=2)
{
	global $wpdb;
	$errorMsg = "";
	
	if(empty($src_file))
	{
            $errorMsg .= "<br />Input file is not specified";
            return $errorMsg;
    }
	
	$file_path = get_abs_path_from_src_file($src_file);	
	
	$file_handle = fopen($file_path, "r");
	if ($file_handle === FALSE) {
		// File could not be opened...
		$errorMsg .= 'Source file could not be opened!<br />';
		$errorMsg .= "Error on fopen('$file_path')";	// Catch any fopen() problems.
		return $errorMsg;
	}
	
	$row = 1;
	while (!feof($file_handle) ) 
	{
		$line_of_text = fgetcsv($file_handle, 1024);
		if ($row < $start_row)
		{
			// Skip until we hit the row that we want to read from.
			$row++;
			continue;
		}
		$columns = count($line_of_text);
		//echo "<br />Column Count: ".$columns."<br />";
		
		if ($columns>1)
		{
	        	$query_vals = "'".$wpdb->escape($line_of_text[0])."'";
	        	for($c=1;$c<$columns;$c++)
	        	{
	        		$line_of_text[$c] = utf8_encode($line_of_text[$c]);
					$line_of_text[$c] = addslashes($line_of_text[$c]);
	                $query_vals .= ",'".$wpdb->escape($line_of_text[$c])."'";
	        	}
	        	//echo "<br />Query Val: ".$query_vals."<br />";
                        $query = "INSERT INTO $table_name ($column_string) VALUES ($query_vals)";
						
                        //echo "<br />Query String: ". $query;
                        $results = $wpdb->query($query);
                        if(empty($results))
                        {
                            $errorMsg .= "<br />Insert into the Database failed for the following Query:<br />";
                            $errorMsg .= $query;
                        }
	                //echo "<br />Query result".$results;
	    }
		$row++;
	}
	fclose($file_handle);
	
	return $errorMsg;
}
function daily_tip_option_page() {

	$weekdays = array(1 => "Sunday",2 => "Monday", 3 => "Tuesday",4 => "Wednessday", 5 => "Thursday",6 => "Friday",7 => "Saturday");

	global $wpdb;
	global $table_suffix;	
	
	$table_name = $wpdb->prefix . $table_suffix;
	$column_string = "tip_text,display_date,display_day";
	
?>
<div>
	<style type="text/css">
			//Data Table
			#display_data{
				border:1px solid #686868;
				border-collapse:collapse;
				width:70%;				
			}
			#display_data thead{
				align:left;
			}
			#display_data th{
				background-color:#2a2a2a;
				color:white;
				padding:5px;
			}
			#display_data tr:nth-child(even)
			{
				background-color:#CFCFCF;
			}
			#display_data tr:nth-child(odd)
			{
				background-color:#EBEBEB;
			}
			#display_data td{
				padding:5px;
			}
			.display_box {
				display:block;
				padding:5px;
				margin:15px;
			}
			#edit_data label{
				display:inline-block;
				width:150px;
				height:20px;
				font-size:1.2em;
				font-weight:bold;
				vertical-align:top;
			}
			#edit_data textarea{
				display:inline-block;
				width:250px;
				border-radius:5px;
				padding:2px;
				font-size:1.2em;
			}
			#edit_data textarea:focus{
				border:2px solid #9B9B9B;
				background:#ffffff;
				
			}
			#upload input , #edit_data input{
				border-radius:5px;
				width:240px;
				height:20px;
				padding:2px;
				font-size:1.2em;
			}
			#upload input:focus ,#edit_data input:focus{
				border:2px solid #9B9B9B;
				background:#ffffff;
			}
			#edit_data select{
				width:250px;
				height:20px;
				border-radius:5px;
				padding:2px;
				font-size:1.2em;
			}
			#edit_data input.button{
				border-radius:8px;
				padding:2px;
				font-size:1.2em;
				height:20px;
			}
			#edit_data input.button:hover{
				cursor:pointer;
				border:2px solid #9B9B9B;
			}
		</style>
	<h2>Daily Tip Plugin</h2>
	<?php 
		if (isset($_POST['Delete'])) {
			$id = check_input($_POST["id"]);
			$wpdb->query("DELETE FROM $table_name WHERE ID = " . $id);
			echo "<div id=\"message\" class=\"updated fade\"><p><strong>Tip Deleted Successfully!</strong></p></div>";
		}
		//Store the Data input if data is submitted
		if (isset($_POST['Submit'])) { 
			$tip_text = check_input($_POST["tiptext"]);
			$display_date = check_input($_POST["display_date"]); 
			$display_day = check_input($_POST["display_day"]);
				
			if (isset($_POST['id'])) { 
				//Update
				$id = check_input($_POST["id"]);
				$wpdb->query("UPDATE $table_name SET tip_text = '" . $tip_text . "', display_date='" . $display_date . "', display_day = ". $display_day ." WHERE ID = " . $id);
				echo "<div id=\"message\" class=\"updated fade\"><p><strong>Tip Updated Successfully!</strong></p></div>";
			}
			else
			{
				//Insert
				$rows_affected = $wpdb->insert( $table_name, array( 'added_date' => current_time('mysql'), 'tip_text' => $tip_text, 'display_date' => $display_date, 'display_day' => $display_day ) );
				echo "<div id=\"message\" class=\"updated fade\"><p><strong>Tip Inserted Successfully!</strong></p></div>";
			}
			
		}
		if(isset($_POST['file_upload']))
		{
			$target_path = WP_CONTENT_DIR.'/plugins/'.WP_DAILY_TIP_FOLDER."/uploads/";
			$target_path = $target_path . basename( $_FILES['uploadedfile']['name']);
			
			echo '<div id="message" class="updated fade"><p><strong>';
			if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path))
			{
				echo "The file ".  basename( $_FILES['uploadedfile']['name'])." has been uploaded";
				$file_name = WP_DAILY_TIP_URL.'/uploads/'.basename( $_FILES['uploadedfile']['name']);
			} 
			else
			{
				echo "There was an error uploading the file, please try again!";
			}
            echo '</strong></p></div>';
			
			$errorMsg = readAndDump($file_name,$table_name,$column_string);
        
			echo '<div id="message" class="updated fade"><p><strong>';
			if(empty($errorMsg))
			{
				echo 'File content has been successfully imported into the database!';
			}
			else
			{
				echo "Error occured while trying to import!<br />";
				echo $errorMsg;
			}
			echo '</strong></p></div>';
		}
	?>
	<div class="display_box">
		<h2>Upload a File</h2>
		
		<form id="upload" enctype="multipart/form-data" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="POST">
			<input type="hidden" name="file_upload" id="file_upload" value="true" />
			<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
			<strong>Choose a CSV file to upload: </strong><input name="uploadedfile" type="file" /><br />
			<input type="submit" value="Upload File" />
		</form>
		<h3>Note : </h3>
		<p><strong>The Format of CSV File must be as below :</strong><br/>
			&nbsp;&nbsp;&nbsp;&nbsp;The First line must be headers as it is ignored while uploading on database<br/>
			&nbsp;&nbsp;&nbsp;&nbsp;From the second line, the data should begin in following order :<br/>
			&nbsp;&nbsp;&nbsp;&nbsp;Tip Text, Display Date,Display Day.<br/>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tip Text : The Actual Statement to be displayed.<br/>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Display Date : Any Specific Date in format YYYY-MM-DD when you want to display the Tip.<br/>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Display Day : Day of week (number format) on which the Tip Should Come. (1 = Sunday ,2 = Monday , 3 = Tuesday, 4 = Wednessday ...7 = Saturday) <br/>
			&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Please Note:</strong>Display Day is ignored if Display Date is mentioned.</p>
	</div>
	<div class="display_box">
	<h2>OR</h2>
	<h2>Enter Manual Data</h2>
	<form id="edit_data" action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post">
		<?php  if (isset($_POST['Edit'])) { echo "<input type='hidden'name=\"id\" value=\"" . check_input($_POST["edit_id"]) . "\" />"; }  ?>
 		<div><label>Tip Text</label><textarea name="tiptext" rows="5" cols="100"><?php if (isset($_POST['Edit'])) { echo check_input($_POST["edit_tip_text"]); } ?></textarea></div>
		<div><label>Display Date</label><input name="display_date" value="<?php if (isset($_POST['Edit'])) { echo check_input($_POST["edit_display_date"]); } ?>"/><span> (YYYY-MM-DD)</span></div>
		<div><label>Display Day</label><select name="display_day">
		<option value='0' <?php if (isset($_POST['Edit'])) { if(check_input($_POST["edit_display_day"])=='0') {echo "selected=\"selected\"";}} ?>></option>
		<?php
			for ($i=1; $i<=7; $i++)
			{
				if (isset($_POST['Edit'])) 
				{ 
						if(check_input($_POST["edit_display_day"])==$i) 
						{
							echo "<option value='$i' selected=\"selected\">$weekdays[$i]</option>";
						}
						else
						{
							echo "<option value='$i'>$weekdays[$i]</option>";
						}
				}
				else
				{
					echo "<option value='$i'>$weekdays[$i]</option>";
				}
			
			}
			
		?>
		</select></div>
		<input class="button" type="submit" name="Submit" value="Submit" />
 	</form>
	</div>
	<div class="display_box">
		
		<?php 

			$table_result = $wpdb->get_results("SELECT * FROM $table_name");
			
			echo "<table id=\"display_data\" >";
			echo "<thead><tr><th>ID</th><th>Tip Text</th><th>Display Date</th><th>Display Day</th><th>Last Shown On</th><th>Edit</th><th>Delete</th></tr></thead>";	
			echo "<tbody>";
			foreach ( $table_result as $table_row ) 
			{
				echo "<tr>";
				echo "<form action=\"" .$_SERVER["REQUEST_URI"] . "\" method=\"post\">";
				echo "<input type=\"hidden\" name=\"edit_id\" value=\"" . $table_row->id . "\" />";
				echo "<input type=\"hidden\" name=\"edit_tip_text\" value=\"" . $table_row->tip_text . "\" />";
				echo "<input type=\"hidden\" name=\"edit_display_date\" value=\"" . $table_row->display_date . "\" />";
				echo "<input type=\"hidden\" name=\"edit_display_day\" value=\"" . $table_row->display_day . "\" />";
				echo "<td>" . $table_row->id . "</td>";
				echo "<td>" . $table_row->tip_text . "</td>";
				echo "<td>" . $table_row->display_date . "</td>";
				echo "<td>" . $weekdays[$table_row->display_day] . "</td>";
				echo "<td>" . $table_row->shown_date . "</td>";
				echo "<td><input type=\"submit\" name=\"Edit\" value=\"Edit\" id=\"btnsubmit\" /></td>";
				echo "<td><input type=\"submit\" name=\"Delete\" value=\"Delete\" id=\"btnsubmit\"/></td>";
				echo "</form>";
				echo "</tr>";
			}
			echo "</tbody>";
			echo "</table>";

		?>
		
	</div>
	
	
</div>
<?php
}
?>