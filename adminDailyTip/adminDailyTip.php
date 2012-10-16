<?php
add_action('admin_menu', 'daily_tip_admin_menu');

function daily_tip_admin_menu() 
{
	//add_options_page('Daily Tips', 'Daily Tips', 'administrator',	'daily-tip', 'daily_tip_option_page');
	add_menu_page( 'Daily Tips Page', 'Daily Tips', 'manage_options','daily-tip','daily_tip_option_page', plugins_url( 'st-daily-tip/images/icon.png' ));
	
	//add_submenu_page( __FILE__, 'About My Plugin', 'About', 'manage_options', __FILE__.'_about', daily_tip_about_page );
}
?>
<?php
function check_input($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    
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
					/** Populate the Group Name if not mentioned in CSV**/
					if ($c == 3)
					{
						if ($line_of_text[$c] == '')
						{
							$line_of_text[$c]='Tip';
						}
					}					
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
	
	$table_suffix = "dailytipdata";
	
	$table_name = $wpdb->prefix . $table_suffix;
	$column_string = "tip_text,display_date,display_day,group_name,Display_yearly";
	
?>

<div class="wrap">  

	<h2>Daily Tip Plugin</h2>
	<?php
		if (isset($_REQUEST['Delete'])) {
			//$id = check_input($_REQUEST["edit_id"]);
			//$wpdb->query("DELETE FROM $table_name WHERE ID = " .$id."");
			//echo "<div id=\"message\" class=\"updated fade\"><p><strong>Tip Deleted Successfully!</strong></p></div>";
			
			if(isset($_REQUEST['checkbox']))
			{
				//$id = check_input($_REQUEST["edit_id"]);
				$i=0;
				foreach($_REQUEST['checkbox']  as $chkid)
				{
					$wpdb->query("DELETE FROM $table_name WHERE ID = " .$chkid."");
					$i++;
				}
				echo "<div id=\"message\" class=\"updated fade\"><p><strong>$i Tip(s) Deleted Successfully!</strong></p></div>";
			}
		}		
		/*if (isset($_REQUEST['Edit'])) {
			$id = check_input($_REQUEST["edit_id"]);
			echo $id;
		}*/
		
		if (isset($_REQUEST['op']) && isset($_REQUEST['edit_id'])) {
			$id = check_input($_REQUEST["edit_id"]);
		}		
		
		//Store the Data input if data is submitted
		if (isset($_REQUEST['Submit'])) { 
			$tip_text = check_input($_REQUEST["tiptext"]);
			$display_date = check_input($_REQUEST["display_date"]); 
			$display_date = htmlspecialchars($display_date);
			$display_day = check_input($_REQUEST["display_day"]);
			$display_day = htmlspecialchars($display_day);
			$group_name = check_input($_REQUEST["group_name"]);
			$group_name = htmlspecialchars($group_name);
			if($group_name==null){
				$group_name="Tip";
			}
			if(isset($_REQUEST["chkyearly"]))
			{
				$yearly ="on";
			}
			else
			{
				$yearly="";
			}
							
			if (isset($_REQUEST['id'])) { 
				//Update
				$id = check_input($_REQUEST["id"]);
				$tip_text = mysql_real_escape_string($tip_text);
				$qry = "UPDATE $table_name SET tip_text = '" . $tip_text . "',Display_yearly='" . $yearly . "', display_date='" . $display_date . "', display_day = ". $display_day .", group_name = '".$group_name."' WHERE ID = " . $id;
				
				$wpdb->query($qry);
				echo "<div id=\"message\" class=\"updated fade\"><p><strong>Tip Updated Successfully!</strong></p></div>";
			}
			else
			{
				//Insert
				if($tip_text!=null)
				{
					if($display_date!=null)
					{
						$rows_affected = $wpdb->insert( $table_name, array( 'added_date' => current_time('mysql'), 'tip_text' => $tip_text, 'display_date' => $display_date, 'display_day' => $display_day, 'Display_yearly' =>$yearly,'group_name' => $group_name ) );
						echo "<div id=\"message\" class=\"updated fade\"><p><strong>Tip Inserted Successfully!</strong></p></div>";
					}
					else if($display_day!=0 )
					{
						$rows_affected = $wpdb->insert( $table_name, array( 'added_date' => current_time('mysql'), 'tip_text' => $tip_text, 'display_date' => $display_date, 'display_day' => $display_day,'group_name' => $group_name ) );
						echo "<div id=\"message\" class=\"updated fade\"><p><strong>Tip Inserted Successfully!</strong></p></div>";
					}
					else{
						$rows_affected = $wpdb->insert( $table_name, array( 'added_date' => current_time('mysql'), 'tip_text' => $tip_text, 'display_date' => $display_date, 'display_day' => $display_day, 'Display_yearly' =>$yearly,'group_name' => $group_name ) );
						echo "<div id=\"message\" class=\"updated fade\"><p><strong>Tip Inserted Successfully!</strong></p></div>";
					}
				}
			}
		}
		if(isset($_REQUEST['file_upload']))
		{
			$target_path = WP_CONTENT_DIR.'/plugins/'.WP_DAILY_TIP_FOLDER."/uploads/";
			$target_path = $target_path . basename( $_FILES['uploadedfile']['name']);
			
			echo '<div id="message" class="updated fade"><p><strong>';
			if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path))
			{
				//echo "The file ".  basename( $_FILES['uploadedfile']['name'])." has been uploaded";
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
	<div class="postbox-container" style="width:70%;padding-right:25px;">
	    <div class="metabox-holder">
			<div class="meta-box-sortables">
				<div id="toc" class="postbox">
					<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndle"><span>Upload a File</span></h3>
					<div class="inside">
						<form id="upload" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']."?page=daily-tip"; ?>" method="POST">
							<input type="hidden" name="file_upload" id="file_upload" value="true" />
							<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
							<p><strong>Choose a CSV file to upload: </strong><input name="uploadedfile" id="upload" type="file" size="25" /><br /></p>
							<p class="submit"><input type="submit" class="button" value="Upload File" /></p>
						</form>
						<h4>Note : </h4>
						<span class="description"><strong>The Format of CSV File must be as below :</strong><br/>
							&nbsp;&nbsp;&nbsp;&nbsp;The First line must be headers as it is ignored while uploading on database<br/>
							&nbsp;&nbsp;&nbsp;&nbsp;From the second line, the data should begin in following order :<br/>
							&nbsp;&nbsp;&nbsp;&nbsp;Tip Text, Display Date,Display Day,Group Name,Repeat Yearly.<br/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Tip Text : The Actual Statement to be displayed.<br/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Display Date : Any Specific Date in format YYYY-MM-DD when you want to display the Tip.<br/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Display Day : Day of week (number format) on which the Tip Should Come. (1 = Sunday ,2 = Monday , 3 = Tuesday, 4 = Wednessday ...7 = Saturday) <br/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Group Name : Group Name in which the tip is to be added. <strong>Group name is Must. Keep "Tip" Group Name in case single group</strong><br/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Repeat Yearly : <strong>on</strong> - To repeat yearly. Leave blank otherwise.<br/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong>Please Note:</strong>Display Day is ignored if Display Date is mentioned.<br/></span>
					</div>
				</div>
				<div id="toc" class="postbox">
					<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndle"><span>Enter Manual Data</span></h3>
					<div class="inside">
					<form id="edit_data" action="<?php echo $_SERVER['PHP_SELF']."?page=daily-tip"; ?>" method="post">
						<?php  if (isset($_REQUEST['op'])&&isset($_REQUEST['edit_id'])) { echo "<input type='hidden'name=\"id\" value=\"" . check_input($_REQUEST["edit_id"]) . "\" />"; }  ?>
						<div><label>Tip Text<span style="color:red;vertical-align:top;">*</span><br/>
						<span style="font-weight:normal;font-size:.8em;"><em>(Use HTML tags for Formatting.e.g. &lt;strong&gt;, &lt;em&gt;, etc.)</em></span>
						</label><textarea name="tiptext" rows="5" cols="62"><?php if (isset($_REQUEST['op'])&&isset($_REQUEST['edit_id'])) { echo check_input($_REQUEST["edit_tip_text"]); } ?></textarea></div>
						
						<div><label>Display Date</label><input name="display_date" class="regular-text code" value="<?php if (isset($_REQUEST['op'])&&isset($_REQUEST['edit_id'])) { echo check_input($_REQUEST["edit_display_date"]); } ?>"/><span> (YYYY-MM-DD)</span></div>
						<div><label>Display Day</label><select name="display_day">
						<option value='0' <?php if (isset($_REQUEST['op'])&&isset($_REQUEST['edit_id'])) { if(check_input($_REQUEST["edit_display_day"])=='0') {echo "selected=\"selected\"";}} ?>></option>
						<?php
							for ($i=1; $i<=7; $i++)
							{
								if (isset($_REQUEST['op'])&&isset($_REQUEST['edit_id']))
								{ 
										if(check_input($_REQUEST["edit_display_day"])==$i) 
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
						
						<?php 
						global $showyearly;
						if (isset($_REQUEST['op'])&&isset($_REQUEST['edit_id'])) {
							if($_REQUEST["edit_display_yearly"]=="on")
							{
								$showyearly=checked;
							}
							else
							{
								$showyearly="";
							}
						} ?>
						<div><label>Repeat Yearly?</label><input type="checkbox" name="chkyearly" <?php echo $showyearly;?>></input></div>
						<div><label>Group Name</label><input name="group_name" class="regular-text code" value="<?php if (isset($_REQUEST['op'])&&isset($_REQUEST['edit_id'])) { echo check_input($_REQUEST["edit_group_name"]); }?>"/><span></span></div>
						<p class="submit">
							<input class="button" type="submit" name="Submit" value="Submit" />
							<input class="button" type="submit" name="Cancel" value="Cancel" />
						</p>
					</form>
				</div>
			</div>
			<div id="toc" class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndle"><span>Tips</span></h3>
					<div class="inside">
		<?php 
		
			$count = $wpdb->query("SELECT * FROM $table_name");
											
			/* Instantiate class */
			//require_once("pager.php");
			//require_once dirname( __FILE__ ) . '/adminDailyTip/pager.php';
			require_once dirname( __FILE__ ) . '/pager.php';
			$p = new pager();
 
			/* Show many results per page? */
			$limit = 15;
 
			/* Find the start depending on $_GET['page'] (declared if it's null) */
			$start = $p->findStart($limit);
 
			/* Find the number of rows returned from a query; Note: Do NOT use a LIMIT clause in this query */
			$count = $wpdb->query("SELECT * FROM $table_name");
 
			/* Find the number of pages based on $count and $limit */
			$pages = $p->findPages($count, $limit);
 
			/* Now we use the LIMIT clause to grab a range of rows */
			$table_result = $wpdb->get_results("SELECT * FROM $table_name LIMIT ".$start.", ".$limit);
	
			/* Or you can use a simple "Previous | Next" listing if you don't want the numeric page listing */
			//$next_prev = $p->nextPrev($_GET['paged'], $pages);
			//echo $next_prev;
			/* From here you can do whatever you want with the data from the $result link. */
			
			
			echo "<table class=\"sort\" id=\"display_data\" >";
			echo "<thead><tr><th class=\"unsortable\"><input type='checkbox' name='checkall' onclick='checkedAll();'></th><th>Tip Text</th><th>Display Date</th><th>Display Day</th><th class=\"unsortable\">Last Shown On</th><th>Group Name</th><th class=\"unsortable\"></th></tr></thead>";	
			echo "<tbody>";
			echo "<form id=\"myform\" action=\"" .$_SERVER["PHP_SELF"] . "?page=daily-tip\" method=\"post\">";
			echo "<input type=\"submit\" name=\"Delete\" value=\"Delete\" id=\"btnsubmit\" class=\"button\" />";
			
			foreach ( $table_result as $table_row ) 
			{
				echo "<tr>";
				//echo "<form action=\"" .$_SERVER["REQUEST_URI"] . "\" method=\"post\">";
				echo "<input type=\"hidden\" name=\"edit_id\" value=\"" . $table_row->id . "\" />";
				echo "<input type=\"hidden\" name=\"edit_tip_text\" value=\"" . $table_row->tip_text . "\" />";
				echo "<input type=\"hidden\" name=\"edit_display_date\" value=\"" . $table_row->display_date . "\" />";
				echo "<input type=\"hidden\" name=\"edit_display_day\" value=\"" . $table_row->display_day . "\" />";
				echo "<input type=\"hidden\" name=\"edit_display_yearly\" value=\"" . $table_row->Display_yearly . "\" />";
				echo "<input type=\"hidden\" name=\"edit_group_name\" value=\"" . $table_row->group_name . "\" />";
				echo "<td><input type=\"checkbox\" name=\"checkbox[]\" value=\"" . $table_row->id . "\"></input></td>";
				//echo "<td>" . $table_row->id . "</td>";
				echo "<td>" . $table_row->tip_text . "</td>";
				echo "<td>" . $table_row->display_date . "</td>";
				echo "<td>" . $weekdays[$table_row->display_day] . "</td>";
				echo "<td>" . $table_row->shown_date . "</td>";
				echo "<td>" . $table_row->group_name . "</td>";
				//echo "<td><input type=\"submit\" name=\"Edit[]\" value=\"Edit\" id=\"btnsubmit\" class=\"button\" /></td>";
				echo "<td><a href=\"".$_SERVER['PHP_SELF']."?page=daily-tip&op=edit&edit_id=".$table_row->id."&edit_tip_text=".$table_row->tip_text."&edit_display_date=".$table_row->display_date."&edit_display_day=".$table_row->display_day."&edit_display_yearly=".$table_row->Display_yearly."&edit_group_name=".$table_row->group_name."\" class=\"button\" style=\"color:#41411D;\">Edit</a></td>";
				//echo "</form>";
				echo "</tr>";
			}
			echo "</form>";
			echo "</tbody>";
			echo "</table>";
			
			/* Now get the page list and echo it */
			$pagelist = $p->pageList($_GET['paged'], $pages);
			echo $pagelist;
		?>
	</div>
				</div>
	
			</div>
		</div>
	</div>
	<div class="postbox-container side" style="width:20%;">
	    <div class="metabox-holder">
			<div class="meta-box-sortables">
				<div id="toc" class="postbox">
					<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndle"><span>How to Use</span></h3>
					<div class="inside">
					<strong>1. Create Tips List</strong><br/>
					You can upload list of tips from CSV file or Manually Entering Tips<br/>
					<strong>2. Display Tips</strong>
					You can use widget or the short code
					</div>
				</div>
				<div id="toc" class="postbox">
					<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndle"><span>Show your Support</span></h3>
					<div class="inside">
						<p>
						<strong>Want to help make this plugin even better? All donations are used to improve this plugin, so donate $20, $50 or $100 now!</strong>
						</p>
						<form method="post" action="https://secure.payza.com/checkout" >
							<input type="hidden" name="ap_productid" value="eLMA2gU1OLMg14iF2RzCUQ=="/>
							<input type="hidden" name="ap_quantity" value="1"/>
							<input type="image" name="ap_image" src="https://secure.payza.com/PayNow/1446835800E0468388C857DA1D6DE3B3d0en.gif"/>
						</form>
						<p>Or you could:</p>
						<ul>
							<li><a href="http://wordpress.org/extend/plugins/st-daily-tip/">Rate the plugin 5 star on WordPress.org</a></li>
							<li><a href="http://wordpress.org/tags/st-daily-tip">Help out other users in the forums</a></li>
							<li>Blog about it &amp; link to the <a href="http://sanskrutitech.in/wordpress-plugins/wordpress-plugins-st-daily-tip/">plugin page</a></li>				
						</ul>
					</div>
				</div>
				<div id="toc" class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndle"><span>Connect With Us </span></h3>
					<div class="inside">
					<a class="facebook" href="https://www.facebook.com/sanskrutitech"></a>
					<a class="twitter" href="https://twitter.com/#!/sanskrutitech"></a>
					<a class="googleplus" href="https://plus.google.com/107541175744077337034/posts"></a>
					</div>
				</div>
		
			</div>
		</div>
	</div>
<?php
}
?>