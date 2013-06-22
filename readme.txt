=== Plugin Name ===
Contributors: dharashah
Donate link: http://sanskrutitech.in/index.php/wordpress-plugins/
Tags: daily tips 
Requires at least: 3.3.1
Tested up to: 3.5
Stable tag: 1.3
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simple plugin to display different daily tips from a list. Option to select specific date or day of week to display a tip.

== Description ==

This plugin simply displays daily tip on your page.

If you want to display different Expert tips on any topic on your website, you can simply upload the tips to this plugin.

** Features **

1. Use Widget or Short Code to display Tips
2. Add the tips manually, or upload in batch from a CSV file.
3. Add Tips with HTML formatting
4. Group the Tips and display different Tips on different Locations
5. Mention a Specific date or Specific Day of Week to Display Tip
6. Repeat the tips Yearly on Specific Date
7. The Tips that are not displayed will be displayed first before repeating the Tips. The oldest tip ( that was displayed first) will be displayed if no un-displayed tips are left.
9. Repeat the tips Yearly on Specific Date.
 

== Installation ==

1. Download the Plugin using the Install Plugins 
   OR 
   Upload folder `st-daily-tip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Add Tips in  Daily Tips (See How to use in Other Notes)
3. Place [stdailytip] in your page/post where you want to display the daily tip
4. You may also use the Daily Tip Widget to display the tips

== How To Use ==
1. Go To **Daily Tips** In Side Menu
2. Create **List of Tips** by :
a. Uploading a CSV file of List of Tips

   The Format of CSV File must be as below :
     *The First line must be headers as it is ignored while uploading.*
     From the second line, the data should begin in following order :
		**Tip Text,Display Date,Display Day,Group Name,Display Yearly.**
         *Tip Text* : The Actual Statement to be displayed.
         *Display Date* : Any Specific Date in format YYYY-MM-DD when you want to display the Tip.
         *Display Day* : Day of week (number format) on which the Tip Should Come. (1 = Sunday ,2 = Monday , 3 = Tuesday, 4 = Wednessday ...7 = Saturday) 
		 *Group Name* : Name Of Group. By Default this is Tip. You can assign a Group to a Tip and can display the tips Group wise.
        **Please Note:Display Day is ignored if Display Date is mentioned.**
b. Adding the tips one by one using the **Enter Manual Data**
	Enter the **Tip Text**
	*You can also enter the text in HTML Format*
	**Display Date (optional) ** in format YYYY-MM-DD
	Select **Display Day (optional)**
	Select **Repeat Yearly? (optional)** to repeat the tip on same date every year
	Select **Group Name** to divide the tips in several Groups
	and press Submit
3.  To Display The tips, you have two ways:
	a. Use Widget . Mention The Group Name of the Tips you want to display.
	b. Use Short Code  [stdailytip group="<group name>"] e.g.[stdailytip group="Tip"]
	c. Developers may also use the PHP Code 
	'<?php
		if (function_exists('add_daily_tip')) {	print add_daily_tip('<group_name>');	}
	?>'
	Replace <group_name> with Tip or any other Group Name you want to display
4.  The Added Tips will be shown in the table Below
5. 	You can also edit or Delete the Tip using the **Edit** and **Delete** button 
6.  If *Display Date* and *Display Day* are not specified, the tip that is not shown yet (or the oldest shown tip) will be displayed
7.  If you have specified a *Display Date* , the tip will be displayed only on that particular date
8. 	If *Display Date* is not specified and *Display Day* is specified, the tip will only be shown on that date

== Changelog ==
= 1.2 =
* Date Bug Fixed according to suggestion from xenoalien

= 1.1 =
* Minor Bug Fixing

= 1.0 =
* Use of DataTables to display Tips
* Provision to Search Tips

= 0.9 =
* Removed Few Bugs
* Added Little Formatting to Admin Panel

= 0.8 =
* Solved Problem of Blank Group Name in CSV

= 0.7 =
* Bug removal - Update Strings with quotes.

= 0.6 =
* Create Tips Group Wise. Display Different Tips in different Groups.

= 0.5 =
* Repeat the tips Yearly on Same Date
* Add tips with HTML Formatting
* Delete Tips in Bulk
* See Daily Tips in Side Panel

= 0.4 =
* Widget Added
* Pagination added for Tips

= 0.2 =
* First Deployed Version
* Provision to Upload a CSV File
* Provision to Enter Manual Data


== Upgrade Notice ==
= 1.0 =
* Use of DataTables to display Tips

= 0.8 =
* Solved Problem of Blank Group Name in CSV

= 0.7 =
* Bug removal - Update Strings with quotes.

= 0.6 =
* Repeat the tips Yearly on Same Date

= 0.5 =
* Repeat the tips Yearly on Same Date
* Add tips with HTML Formatting
* Delete Tips in Bulk
* See Daily Tips in Side Panel

= 0.4 =
* Daily Tip Widget 
* Tips will now be shown page wise. With 15 tips on each Page

= 0.2 =
* First Deployed version









