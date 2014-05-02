<?php
add_action('admin_menu', 'daily_tip_admin_menu');

function daily_tip_admin_menu() 
{
	$page = add_menu_page( 'Daily Tips Page', 'Daily Tips', 'manage_options','daily-tip','daily_tip_option_page', plugins_url( 'st-daily-tip/images/icon.png' ));
	add_action('admin_print_scripts-' . $page, 'daily_tips_admin_scripts');

}
function daily_tips_admin_scripts() {
	
}

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
//Upload CSV File
function readAndDump($src_file,$table_name,$column_string="",$start_row=2)
{
	ini_set('auto_detect_line_endings', true);
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
		$errorMsg .= "Source file could not be opened!<br />";
		$errorMsg .= "Error opening ('$file_path')";	// Catch any fopen() problems.
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
		
		if ($columns>1)
		{
	        	$query_vals = "'".esc_sql($line_of_text[0])."'";
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
					//$line_of_text[$c] = addslashes($line_of_text[$c]);
	                $query_vals .= ",'".esc_sql($line_of_text[$c])."'";
					
	        	}
				//Added Date
				$query_vals .= ",'" . current_time('mysql') . "'";
				$query = "INSERT INTO $table_name ($column_string) VALUES ($query_vals)";
				
				$results = $wpdb->query($query);
				if(empty($results))
				{
					$errorMsg .= "<br />" . _e('Insert into the Database failed for the following Query:','stdailytip') . "<br />";
					$errorMsg .= $query;
				}
		}
		$row++;
	}
	fclose($file_handle);
	
	return $errorMsg;
}
function daily_tip_option_page() {
	
	$weekdays = array(1 => "Sunday",2 => "Monday", 3 => "Tuesday",4 => "Wednesday ", 5 => "Thursday",6 => "Friday",7 => "Saturday");

	global $wpdb;
	global $table_suffix;	
	
	$table_suffix = "dailytipdata";
	
	$table_name = $wpdb->prefix . $table_suffix;
	$column_string = "tip_title,tip_text,display_date,display_day,group_name,Display_yearly,added_date";
?>

<div class="wrap">  
	
	<h2><?php _e('Daily Tip Plugin','stdailytip')?></h2>
	<?php
		if (isset($_REQUEST['Delete'])) {
			if(isset($_REQUEST['checkbox']))
			{
				$i=0;
				foreach($_REQUEST['checkbox']  as $chkid)
				{
					$wpdb->query("DELETE FROM $table_name WHERE ID = " .$chkid."");
					$i++;
				}
				echo "<div id=\"message\" class=\"updated fade\"><p><strong>$i ";
				echo _e('Tip(s) Deleted Successfully!','stdailytip');
				echo "</strong></p></div>";
			}
		}		
		
		if (isset($_REQUEST['chngdatefrmt'])){
			if($_REQUEST["datfrmt"]=="Y-m-d")
			{
				update_option("st_daily_date_format", 'Y-m-d');
			}
			elseif($_REQUEST["datfrmt"]=="d-m-Y")
			{
				update_option("st_daily_date_format", 'd-m-Y');
			}
			elseif($_REQUEST["datfrmt"]=="m-d-Y")
			{
				update_option("st_daily_date_format", 'm-d-Y');
			}
			elseif($_REQUEST["datfrmt"]=="F j, Y")
			{
				update_option("st_daily_date_format", 'F j, Y');
			}
			elseif($_REQUEST["datfrmt"]=="l jS F, Y")
			{
				update_option("st_daily_date_format", 'l jS F, Y');
			}
		}


		if (isset($_REQUEST['op']) && isset($_REQUEST['edit_id'])) {
			global $wpdb;
			global $table_suffix;
	
			$table_suffix = "dailytipdata";
			$table_name = $wpdb->prefix . $table_suffix;
			
			$id = check_input($_REQUEST["edit_id"]);
			$edit_tip = $wpdb->get_row("SELECT * FROM $table_name WHERE id='$id';", ARRAY_A);
			$edit_added_date = $edit_tip['added_date'];
			$edit_tip_title = check_input($edit_tip['tip_title']);
			$edit_tip_text = check_input($edit_tip['tip_text']);
			$edit_group_name = $edit_tip['group_name'];
			$edit_display_yearly = $edit_tip['Display_yearly'];
			$edit_display_date = $edit_tip['display_date'];
			$edit_shown_date = $edit_tip['shown_date'];
			$edit_display_day = $edit_tip['display_day'];
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
			$tip_title = check_input($_REQUEST["tip_title"]);
			//$tip_title = htmlspecialchars($tip_title);
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
				$wpdb->update( $table_name , array( 'tip_title' => $tip_title,'tip_text' => $tip_text,'Display_yearly' => $yearly,'display_date'=>$display_date,'display_day'=>$display_day,'group_name'=>$group_name), array('ID' => $id)); 
				
				echo "<div id=\"message\" class=\"updated fade\"><p><strong>" . _e('Tip Updated Successfully!','stdailytip') . "</strong></p></div>";
			}
			else
			{
				//Insert
				if($tip_text!=null)
				{
					if($display_date!=null)
					{
						$rows_affected = $wpdb->insert( $table_name, array( 'added_date' => current_time('mysql'), 'tip_text' => $tip_text,'tip_title' => $tip_title, 'display_date' => $display_date, 'display_day' => $display_day, 'Display_yearly' =>$yearly,'group_name' => $group_name ) );
						echo "<div id=\"message\" class=\"updated fade\"><p><strong>". _e('Tip Inserted Successfully!','stdailytip') ."</strong></p></div>";
					}
					else if($display_day!=0 )
					{
						$rows_affected = $wpdb->insert( $table_name, array( 'added_date' => current_time('mysql'), 'tip_text' => $tip_text,'tip_title' => $tip_title, 'display_date' => $display_date, 'display_day' => $display_day,'group_name' => $group_name ) );
						echo "<div id=\"message\" class=\"updated fade\"><p><strong>". _e('Tip Inserted Successfully!','stdailytip') ."</strong></p></div>";
					}
					else{
						$rows_affected = $wpdb->insert( $table_name, array( 'added_date' => current_time('mysql'), 'tip_text' => $tip_text,'tip_title' => $tip_title, 'display_date' => $display_date, 'display_day' => $display_day, 'Display_yearly' =>$yearly,'group_name' => $group_name ) );
						echo "<div id=\"message\" class=\"updated fade\"><p><strong>Tip Inserted Successfully!</strong></p></div>";
					}
				}
			}
		}
		if(isset($_REQUEST['file_upload']))
		{
			$target_path = WP_CONTENT_DIR.'/plugins/'.WP_DAILY_TIP_FOLDER."/uploads/";
			$target_path = $target_path . basename( $_FILES['uploadedfile']['name']);
			
			
			if(move_uploaded_file($_FILES['uploadedfile']['tmp_name'], $target_path))
			{
				$file_name = $target_path;
			} 
			else
			{
				echo '<div id="message" class="error"><p><strong>';
				echo  _e('There was an error uploading the file, please try again!','stdailytip');
				echo '</strong></p></div>';
			}
            
			
			$errorMsg = readAndDump($file_name,$table_name,$column_string);
        
			
			if(empty($errorMsg))
			{
				echo '<div id="message" class="updated fade"><p><strong>';
				echo _e('File content has been successfully imported into the database!','stdailytip');
				echo '</strong></p></div>';
			}
			else
			{
				echo '<div id="message" class="error"><p><strong>';
				echo _e('Error occurred while trying to import!','stdailytip') . "<br />";
				echo $errorMsg;
				echo '</strong></p></div>';
			}
			
		}
	?>
	<div class="postbox-container" style="width:70%;padding-right:25px;">
	    <div class="metabox-holder">
			<div class="meta-box-sortables">
				<div id="toc" class="postbox">
					<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndle"><span><?php _e('Upload a File','stdailytip') ?></span></h3>
					<div class="inside">
						<form id="upload" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']."?page=daily-tip"; ?>" method="POST">
							<input type="hidden" name="file_upload" id="file_upload" value="true" />
							<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
							<strong><?php _e('Choose a CSV file to upload:','stdailytip') ?></strong><input name="uploadedfile" id="upload" type="file" size="25" />
							<input type="submit" class="button-primary" value="Upload File" />
						</form>
						<br/>
						<h4>Note : </h4>
						<span class="description"><strong><?php _e('The Format of CSV File must be as below :','stdailytip') ?></strong><br/>
							&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('The First line must be headers as it is ignored while uploading on database','stdailytip') ?><br/>
							&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('From the second line, the data should begin in following order :','stdailytip') ?><br/>
							&nbsp;&nbsp;&nbsp;&nbsp;<strong><?php _e('Tip Title, Tip Text, Display Date,Display Day,Group Name,Repeat Yearly.','stdailytip') ?></strong><br/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Tip Title : If you want to add title to tip.','stdailytip') ?><br/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Tip Text : The Actual Statement to be displayed.','stdailytip') ?><br/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong><?php _e('To insert a tip with comma (,) place the tip between two inverted commas ". e.g. "Like , this" ','stdailytip') ?></strong><br/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Display Date : Any Specific Date in format YYYY-MM-DD when you want to display the Tip.','stdailytip') ?><br/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Display Day : Day of week (number format) on which the Tip Should Come. (1 = Sunday ,2 = Monday , 3 = Tuesday, 4 = Wednesday  ...7 = Saturday) ','stdailytip') ?><br/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Group Name : Group Name in which the tip is to be added. <strong>Group name is Must. Keep "Tip" Group Name in case single group','stdailytip') ?></strong><br/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php _e('Repeat Yearly','stdailytip')?> : <strong>on</strong> - <?php _e('To repeat yearly. Leave blank otherwise.','stdailytip') ?><br/>
							&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<strong><?php _e('Please Note','stdailytip')?>:</strong><?php _e('Display Day is ignored if Display Date is mentioned.','stdailytip') ?><br/></span>
					</div>
				</div>
				<div id="toc" class="postbox">
					<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndle"><span><?php _e('Enter Single Tip','stdailytip') ?></span></h3>
					<div class="inside">
					<form id="edit_data" action="<?php echo $_SERVER['PHP_SELF']."?page=daily-tip"; ?>" method="post">
						<?php  if (isset($_REQUEST['op'])&&isset($_REQUEST['edit_id'])) 
								{ 
									echo "<input type='hidden' name=\"id\" value=\"" . check_input($_REQUEST["edit_id"]) . "\" />"; 
								}  
						?>
						<div><label><?php _e('Tip Title','stdailytip') ?></label><input name="tip_title" class="regular-text code" value="<?php if (isset($_REQUEST['op'])&&isset($_REQUEST['edit_id'])) { echo $edit_tip_title; }?>"/><span></span></div>
						<div>
							<label><?php _e('Tip Text','stdailytip') ?><span style="color:red;vertical-align:top;">*</span><br/>
								<span style="font-weight:normal;font-size:.8em;"><em><?php _e('(Use HTML tags for Formatting.e.g. &lt;strong&gt;, &lt;em&gt;, etc.)','stdailytip') ?></em></span>
							</label>
							<textarea name="tiptext" class="regular-text code" rows="5" cols="42"><?php if (isset($_REQUEST['op'])&&isset($_REQUEST['edit_id'])) { echo $edit_tip_text; } ?></textarea>
						</div>
						<div>
						<script type="text/javascript">
							jQuery(document).ready(function() {
								jQuery('#display_date').datepicker({
									dateFormat : 'yy-mm-dd'
								});
							});
						</script>
							<label>Display Date</label>
							<input name="display_date" id="display_date" class="regular-text code" value="<?php if (isset($_REQUEST['op'])&&isset($_REQUEST['edit_id'])) { echo $edit_display_date; } ?>"/>
							<span> (YYYY-MM-DD)</span>
						</div>
						<div>
							<label>Display Day</label>
							<select name="display_day">
						<option value='0' <?php if (isset($_REQUEST['op'])&&isset($_REQUEST['edit_id'])) { if($edit_display_day=='0') {echo "selected=\"selected\"";}} ?>></option>
						<?php
							for ($i=1; $i<=7; $i++)
							{
								if (isset($_REQUEST['op'])&&isset($_REQUEST['edit_id']))
								{ 
										if($edit_display_day==$i) 
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
							if($edit_display_yearly=="on")
							{
								$showyearly=checked;
							}
							else
							{
								$showyearly="";
							}
						} ?>
						<div><label><?php _e('Repeat Yearly?','stdailytip') ?></label><input type="checkbox" name="chkyearly" <?php echo $showyearly;?>></input></div>
						<div><label><?php _e('Group Name','stdailytip') ?></label><input name="group_name" class="regular-text code" value="<?php if (isset($_REQUEST['op'])&&isset($_REQUEST['edit_id'])) { echo $edit_group_name; }?>"/><span></span></div>
						<p class="submit">
							<input class="button-primary" type="submit" name="Submit" value="Submit" />
							<input class="button-secondary" type="submit" name="Cancel" value="Cancel" />
						</p>
					</form>
				</div>
			</div>
			<div id="toc" class="postbox">
				<h3 class="hndle"><span><?php _e('Change Date Format to display on Front Page','stdailytip') ?></span></h3>
				<div class="inside">
				<form id="chngdatefrmt" enctype="multipart/form-data" action="<?php echo $_SERVER['PHP_SELF']."?page=daily-tip"; ?>" method="POST">
					<select name="datfrmt">
						<option value="Y-m-d" <?php if(get_option("st_daily_date_format")=="Y-m-d"){echo "selected=\"selected\"";}?>>yy-mm-dd (e.g. 2013-10-25)</option>
						<option value="d-m-Y" <?php if(get_option("st_daily_date_format")=="d-m-Y"){echo "selected=\"selected\"";}?>>dd-mm-yy (e.g. 25-10-2013)</option>
						<option value="m-d-Y" <?php if(get_option("st_daily_date_format")=="m-d-Y"){echo "selected=\"selected\"";}?>>mm-dd-yy (e.g. 10-25-2013)</option>
						<option value="F j, Y" <?php if(get_option("st_daily_date_format")=="F j, Y"){echo "selected=\"selected\"";}?>>F j, Y (e.g. October 25, 2013)</option>
						<option value="l jS F, Y" <?php if(get_option("st_daily_date_format")=="l jS F, Y"){echo "selected=\"selected\"";}?>>l jS F, Y (e.g. Friday 25th October, 2013)</option>
					</select>
					<input class="button-primary" type="submit" name="chngdatefrmt" value="Change" />
				</form>
				</div>
		</div>
			<div id="toc" class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndle"><span><?php _e('Tips','stdailytip') ?></span></h3>
					<div class="inside">
					<script type="text/javascript" charset="utf-8">
						$(document).ready(function() {
							$('#display_data').dataTable( {
								"aaSorting": [[ 1, "desc" ]]
							} );
						} );
					</script>
					<?php 
						$table_result = $wpdb->get_results("SELECT * FROM $table_name ");
						echo "<form id=\"myform\" action=\"" .$_SERVER["PHP_SELF"] . "?page=daily-tip\" method=\"post\">";
						echo "<div class=\"dataTables_wrapper\" role=\"grid\">";
						echo "<table class=\"display sortable\" id=\"display_data\" style=\"width:100%;\" >";
						echo "<thead><tr>";
						echo "<th class=\"unsortable\"><span><input type='checkbox' name='checkall' onclick='checkedAll();'/> Select All<span/> </th>";
						echo "<th>Id</th>";
						echo "<th>Tip Title</th>";
						echo "<th>Tip Text</th>";
						echo "<th>Display Date</th>";
						echo "<th>Display Day</th>";
						echo "<th>Last Shown On</th>";
						echo "<th>Group Name</th>";
						echo "<th>Repeat Yearly</th>";
						echo "<th></th>";
						echo "</tr></thead><tbody>";
						
						echo "<input type=\"submit\" name=\"Delete\" value=\"Delete\" id=\"btnsubmit\" class=\"button\" />";
						echo "<a href=\"".plugin_dir_url(__FILE__)."export_csv.php"."\" class=\"button\" style=\"color:#41411D;float:right;\">Export to CSV</a>";
						
						foreach ( $table_result as $table_row ) 
						{
							echo "<tr>";
							echo "<input type=\"hidden\" name=\"edit_id\" value=\"" . $table_row->id . "\" />";
							echo "<td><input type=\"checkbox\" name=\"checkbox[]\" value=\"" . $table_row->id . "\"></input></td>";
							echo "<td>" . $table_row->id . "</td>";
							echo "<td>" . $table_row->tip_title . "</td>";
							echo "<td>" . $table_row->tip_text . "</td>";
							echo "<td>" . $table_row->display_date . "</td>";
							echo "<td>" . $weekdays[$table_row->display_day] . "</td>";
							echo "<td>" . $table_row->shown_date . "</td>";
							echo "<td>" . $table_row->group_name . "</td>";
							echo "<td>" . $table_row->Display_yearly . "</td>";
							echo "<td><a href=\"".$_SERVER['PHP_SELF']."?page=daily-tip&op=edit&edit_id=".$table_row->id."\" class=\"button\" style=\"color:#41411D;\">Edit</a></td>";
							echo "</tr>";
						}
						
						echo "</tbody>";
						echo "</table>";
						echo "<div style=\"clear:both;\"></div>";
						echo "</div>";
						echo "</form>";
						
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
					<h3 class="hndle"><span><?php _e('How to Use','stdailytip')?></span></h3>
					<div class="inside">
					<strong><?php _e('1. Create Tips List','stdailytip')?></strong><br/>
					<?php _e('You can upload list of tips from CSV file or Manually Entering Tips','stdailytip')?><br/>
					<strong><?php _e('2. Display Tips','stdailytip')?></strong><br/>
					<?php _e('You can use widget or the short code:','stdailytip')?> <br/>[stdailytip group="Tip" date="show" title="show"]<br/>
					<?php _e('If you do not want to show last date then replace "show" with "hide" in date','stdailytip')?><br/>
					<?php _e('If you do not want to show title then replace "show" with "hide" in title','stdailytip')?><br/>
					<strong><?php _e('3. Use classes','stdailytip')?></strong><br/>
					<?php _e('Use classes tip_title, tip_text, and single_tip to style the tips','stdailytip')?>
					</div>
				</div>
				<div id="toc" class="postbox">
					<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndle"><span><?php _e('Show your Support','stdailytip')?></span></h3>
					<div class="inside">
						<p>
						<strong><?php _e('Want to help make this plugin even better? All donations are used to improve this plugin, so donate now!','stdailytip')?></strong>
						</p>
						<a href="http://sanskrutitech.in/wordpress-plugins/wordpress-plugins-st-daily-tip/"><?php _e('Donate','stdailytip')?></a>
						<p>Or you could:</p>
						<ul>
							<li><a href="http://wordpress.org/extend/plugins/st-daily-tip/"><?php _e('Rate the plugin 5 star on WordPress.org','stdailytip')?></a></li>
							<li><a href="http://wordpress.org/tags/st-daily-tip"><?php _e('Help out other users in the forums','stdailytip')?></a></li>
							<li><?php _e('Blog about it &amp; link to the ','stdailytip')?><a href="http://sanskrutitech.in/wordpress-plugins/wordpress-plugins-st-daily-tip/"><?php _e('plugin page','stdailytip')?></a></li>				
						</ul>
					</div>
				</div>
				<div id="toc" class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndle"><span><?php _e('Connect With Us ','stdailytip')?></span></h3>
					<div class="inside">
					<a class="facebook" href="https://www.facebook.com/sanskrutitech"></a>
					<a class="twitter" href="https://twitter.com/#!/sanskrutitech"></a>
					<a class="googleplus" href="https://plus.google.com/107541175744077337034/posts"></a>
					</div>
				</div>
				<div id="toc" class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div>
					<h3 class="hndle"><span><?php _e('Special Thanks','stdailytip')?></span></h3>
					<div class="inside">
						<a href="http://www.datatables.net">DataTables</a>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
}
?>