=== Toggle wpautop ===
Contributors: linchpin_agency, desrosj
Tags: wpautop, formatting, post content, excerpt, editor
Requires at least: 3.0
Tested up to: 3.8.1
Stable tag: 1.1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Easily disable the wpautop filter on a post by post basis.

== Description ==

Before WordPress displays a post's content, the content gets passed through multiple filters to ensure that it safely appears how you enter it within the editor.

One of these filters is [wpautop](http://codex.wordpress.org/Function_Reference/wpautop "wpautop"), which replaces double line breaks with `<p>` tags, and single line breaks with `<br />` tags. However, this filter sometimes causes issues when you are inputting a lot of HTML markup in the post editor.

This plugin displays a checkbox in the publish meta box of the post edit screen that disables the [wpautop](http://codex.wordpress.org/Function_Reference/wpautop "wpautop") filter for that post.

Also adds a 'wpautop', or 'no-wpautop' class to the post_class filter to help with CSS styling.

== Installation ==

1. Upload the plugin folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Proceed to the Settings->Writing and select which post types should have the option to disable the wpautop filter.

== Frequently Asked Questions ==

= Can I disable wpautop completely with this plugin? =

Right now, no. wpautop is a great filter, and in most cases you should not need it disabled globally. However, if there is enough demand for this feature we can add it.

== Screenshots ==

1. The disable wpautop checkbox on post edit screens.
2. Settings->Writing page with plugin settings.

== Changelog ==

= 1.1.1 =
* Fixing bug where users upgrading from 1.0 would not receive the defaults for settings that were introduced in 1.1.

= 1.1 =
* Adding the ability to choose which post types have the option to disable the wpautop filter on the Settings->Writing page.
* When activating the plugin for the first time, all post types are set to have the ability to disable the wpautop filter. This can be changed on the Settings->Writing page.
* Adding an uninstall hook to remove all traces of the plugin.

= 1.0 =
* Hello world!