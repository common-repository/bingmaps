=== MapView for Bing Maps ===
Contributors: Malcolm-OPH
Tags: pages, paypal, maps, bing, mapping, ordnance survey, OS, routes, walking
Requires at least: 3.0
Tested up to: 6.3
Stable tag: 1.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The MapView for Bing Maps plugin adds an interactive map(s) to your Wordpress Page or Post, using the Bing Maps AJAX Control.

== Description ==

An interactive map can be added to your website by adding a single shortcode. The location the map can be specified either by shortcode attribute or URL parameters. Other (optional) values can be added to specify the scale of the map, screen size in pixels, and the map type.

Features Summary

* Provides a WP wrapper for the Microsoft Bing Maps AJAX Control
* Adds an interactive map to your Wordpress Post or Page
* Location specified in shortcode attribute
* Image size can be specified by optional shortcode attribute
* Map scale can be specified by optional shortcode attribute
* Displays Ordnance Survey Mapping (GB locale only)
* Shortcode Attributes can be replaced by URL Parameters
* Requires a Microsoft Account and Bing Maps Key

== Installation ==

* Download the MapView for Bing Maps plugin archive
* Open the Wordpress Dashboard for you site
* Select the "Upload" option 
* Click "Add New" under the "Plugins" menu 
* Under "Install a plugin in .zip format" browse to the plugin archive file you downloaded
* Click Install Now.
* After it has installed, activate the plugin.
* Go to the settings page and enter your Microsoft Bing Maps Key

== Frequently Asked Questions ==

= How do I set up MapView for Bing Maps? =

* Install the plugin and activate it
* Go to https://www.bingmapsportal.com/ and sign up to get a free Microsoft Bing Maps Key
* Open your Wordpress Dashboard
* Go to the MapView for Bing Maps - Settings page and enter your Microsoft Bing Maps Key
		
= How do I add a map to my site? =

Add the shortcode (see below) to either a new or existing page on your site. 

= How do I specify the map coordinates in the shortcode? =

Some typical shortcodes, where the coordinates are specified, as follows:

[bingmaps-map x&#61;51.503146 y&#61;-0.002979]

[bingmaps-map posn&#61;51.477841,-0.001548 zoom&#61;14 w&#61;800 h&#61;500 type&#61;ordnanceSurvey] (GB locale only)

= How do I pass the map coordinates in the URL? =

Shortcode Attributes can specify that a URL Parameter is to be used for the value, by enclosing the ID of the Parameter in braces. A typical shortcode where coordinates are passed in the URL is as follows:

[bingmaps-map posn&#61;{myposn} zoom&#61;{myzoom} w&#61;{mywidth} h&#61;{myheight} type&#61;{mytype}] 

The shortcode attributes above specify the ID of the URL parameter that will be used for the map coordinate etc. A typical URL for this shortcode would therefore be as follows:

[YourPageURL]?myposn=51.477841,-0.001548&myzoom=14&mytype=ordnanceSurvey

= How do I add pushpins to the map? =

The Shortcode Attributes 'pin1', 'pin2' etc. specify the position of pushpins. Alternatively the use 'pin={mypin}' to specify that the URL Parameters mypin1, mypin2 etc. are to be used for pushpin positions.

= What map types are available? =

All map types supported by the Microsoft Bing Maps AJAX Control can be displayed. The map types available depends on the locale selected in the BingMaps settings.

The Microsoft Bing Maps help page (http://msdn.microsoft.com/en-us/library/gg427625.aspx) lists all available map types.

== Screenshots ==

1. Settings Page
2. Typical output (Road Map)
3. Typical output (OS Map)

== Changelog ==

* Version History for MapView for Bing Maps Plugin

= 1.3.2 (19/07/2023) =
* Updated for compatibility with PHP 9.0
* Null parameter values to string functions trapped 
* All echo calls escaped to follow updated plugin design guidelines
* Readme updated - Compatible with WP 6.2.2

= 1.3.1 (19/04/2023) =
* Library files updated
* Added pincntr option to shortcode
* Added numbered pins to shortcode
* Readme updated - Compatible with WP 6.2

= 1.2 (12/03/2021) =
* Library files updated
* Readme updated - Compatible with WP 5.7

= 1.1.1 (24/09/2020) =
* Readme updated - Compatible with WP 5.5.1

= 1.1 (22/09/2020) =
* Added sanitization to $_COOKIE values
* Added details of Microsoft/Bing Maps account requirements to ReadMe 
* Mapcontrol script now loaded using wp_enqueue_script 

= 1.0 (20/07/2020) =
* Readme updated - Compatible with WP 5.4.2

= 0.4 (10/07/2020) =
* Form input checked using WP sanitize***** functions
* WP compatibility version updated

= 0.3 =
* Shortcode attributes can be specified by URL Parameters
* 'x' and 'y' Shortcode Attributes replaced by a 'posn' Attribute
* Added pushpins

= 0.2 =
* Shortcode help corrected
* Added Plugin URI to header

= 0.1 =
* First public release

