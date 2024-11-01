=== Store Manager ===
Contributors: dirlikdesigns
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=NLPY3A65BTSNE
Tags: store, location, custom post, distance, radius, openstreetmap
Requires at least: 4.0
Tested up to: 4.4.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Store manager with control over opening hours, location, images and much more.

== Description ==

With this plugin it is possible to manage multiple stores (or any kind of location).
There is support for: 

* address information
* contact information
* openinghours
* short description
* long description
* photo gallery
* distance calculator
* a store manager user role, so that store management can be delegated without opening up any other part of the WP admin


= Styling =
The plugin comes with a very basic and ugly template file and a form that has no styling. However, everything is wrapped in spans or divs and logical classnames are assigned to everything so that you style the frontend yourself.

You should also make file in your theme folder called single-store.php. The included template file will show how to get all the information from the store. This file is located:
[plugin_folder]/includes/template-single-store.php


= Shortcode =
This is the shortcode for the form and resultpage:
[store-manager-form]
If no attributes are given, the form will consist of a zipcode fields and a submit button.

This will add a radius field (radius is in KM):
[store-manager-form radius]

Here is an example of how to manipulate the radius field:
[store-manager-form radius radius-value=15 radius-placeholder="input placeholder text here" radius-label="label text here"]

This example shows all other form fields, each of them can be manipulated just like radius:
[store-manager-form address-r1 address-r2 zipcode place country]

This example shows how to change the submit button text:
[store-manager-form submit="Send"]


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/store-manager` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. A new menu item should appear named 'Stores'


== Frequently Asked Questions ==
* feel free to ask

== Upgrade Notice ==

= 1.0.2 =
added some features and fixed some bugs

= 1.0.1 =
prepared plugin for localization

= 1.0 =
this is the first version

== Changelog ==

= 1.0.2 =
* form results closing html tags
* added google maps
* added support for miles
* added country option

= 1.0.1 =
* prepared plugin for localization
* added dutch translation

= 1.0 =
* this is the first version

== Screenshots ==
* Coming soon


== TODO ==

* routing
* other map API's (bing/mapquest)
* maybe some global or default store as a template.
* I want to get rid of the .hover styling in the openstreepmap/google class (line 93/79)

