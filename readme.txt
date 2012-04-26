=== Plugin Name ===
Contributors: dharashah
Donate link: http://sanskrutitech.in/index.php/wordpress-plugins/
Tags: daily tip, upload
Requires at least: 3.3.1
Tested up to: 3.3.2
Stable tag: 0.4

Simple plugin to display different daily tips from a list. Option to select specific date or day of week to display a tip.

== Description ==

This plugin simply displays daily tip on your page.

If you want to display different Expert tips on any topic on your website, you can simply upload the tips to this plugin.

You can add the tips manually, or can upload the tips in batch from a CSV file.

You can decide a specific Date to display a tip, Or a specific day of the week to display the tip.
If the Date to display a tip is specified, the tip will only be displayed on that date.
If the Day of week is specified for a tip, the tip will only be displayed on that day of week.

Also, the tip once displayed will not be displayed again untill there are tips that are not displayed.
The oldest tip ( that was displayed first) will be displayed if no un-displayed tips are left


== Installation ==

1. Download the Plugin using the Install Plugins 
   OR 
   Upload folder `st-daily-tip` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Place [stdailytip] in your page/post where you want to display the daily tip

== How To Use ==
1. Go To **Settings > Daily Tips**
2. Here you can upload a CSV file of List of Tips

   The Format of CSV File must be as below :
     *The First line must be headers as it is ignored while uploading.*
     From the second line, the data should begin in following order :
     **Tip Text, Display Date,Display Day.**
         *Tip Text* : The Actual Statement to be displayed.
         *Display Date* : Any Specific Date in format YYYY-MM-DD when you want to display the Tip.
         *Display Day* : Day of week (number format) on which the Tip Should Come. (1 = Sunday ,2 = Monday , 3 = Tuesday, 4 = Wednessday ...7 = Saturday) 
        **Please Note:Display Day is ignored if Display Date is mentioned.**
3. You can also add the tips one by one using the **Enter Manual Data**
	Enter the **Tip Text**
	**Display Date (optional) ** in format YYYY-MM-DD
	Select **Display Day (optional)**
	and press Submit
	
4.  The Added Tips will be shown in the table Below
5. 	You can also edit or Delete the Tip using the **Edit** and **Delete** button 
6.  If *Display Date* and *Display Day* are not specified, the tip that is not shown yet or the oldest shown tip will be displayed
7.  If you have specified a *Display Date* , the tip will be displayed only on that particular date
8. 	If *Display Date* is not specified and *Display Day* is specified, the tip will only be shown on that date

== Changelog ==

= 0.2 =
* First Deployed Version
* Provision to Upload a CSV File
* Provision to Enter Manual Data

= 0.4 =
* Widget Added
* Pagination added for Tips

== Upgrade Notice ==

= 0.2 =
* First Deployed version

= 0.4 =
* Daily Tip Widget 
* Tips will now be shown page wise. With 15 tips on each Page





