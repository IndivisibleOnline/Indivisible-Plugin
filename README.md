# Indivisible-Plugin
The Custom Plugin that Powers IW Wordpress Site

Files relating to the plugin are:

indivisible.php - Standard wordpress discovery file.  Implements version checking, etc
indivisible_init.php - Standard wordpress initialization file.  Executed each time plugin is activated
Indivisible_InstallIndicator.php - Standard wordpress file - used during plugin installation
Indivisible_LifeCycle.php - Register plugin, install wp-admin handlers and submenus

admin.php - Display and functionality of the administration page(s) in wp-admin for the IW content
groups.php - Display and functionality for rendering topic group pages




Indivisible_OptionsManager.php
Indivisible_Plugin.php
Indivisible_ShortCodeLoader.php
Indivisible_ShortCodeScriptLoader.php
forums.php
groups.php
indivisible_main.php
iw_posts.php
user_mgt.php
widgets.php


There is an api throughout the code "add_shortcode"
This api is used to bind php function to a referent which can be used in the construction
of pages in the wp-admin tool or in the class definition of widgets (see relatedpages_widget)
and the mechanism by which it references [iw_related_posts])
