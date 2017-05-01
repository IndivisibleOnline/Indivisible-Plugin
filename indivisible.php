<?php
/*
   Plugin Name: Indivisible Groups
   Plugin URI: http://www.indivisiblewestchester.org
   Version: 1.0
   Author: Howard Stevens
   Description: Modifies Wordpress site to suit our needs
   Text Domain: indivisible
   License: GPLv3
  */


$Indivisible_minimalRequiredPhpVersion = '5.0';

/**
 * Check the PHP version and give a useful error message if the user's version is less than the required version
 * @return boolean true if version check passed. If false, triggers an error which WP will handle, by displaying
 * an error message on the Admin page
 */

function Indivisible_noticePhpVersionWrong() {
    global $Indivisible_minimalRequiredPhpVersion;
    echo '<div class="updated fade">' .
      __('Error: plugin "Indivisible" requires a newer version of PHP to be running.',  'indivisible').
            '<br/>' . __('Minimal version of PHP required: ', 'indivisible') . '<strong>' . $Indivisible_minimalRequiredPhpVersion . '</strong>' .
            '<br/>' . __('Your server\'s PHP version: ', 'indivisible') . '<strong>' . phpversion() . '</strong>' .
         '</div>';
}


function Indivisible_PhpVersionCheck() {
    global $Indivisible_minimalRequiredPhpVersion;
    if (version_compare(phpversion(), $Indivisible_minimalRequiredPhpVersion) < 0) {
        add_action('admin_notices', 'Indivisible_noticePhpVersionWrong');
        return false;
    }
    return true;
}


/**
 * Initialize internationalization (i18n) for this plugin.
 * References:
 *      http://codex.wordpress.org/I18n_for_WordPress_Developers
 *      http://www.wdmac.com/how-to-create-a-po-language-translation#more-631
 * @return void
 */
function Indivisible_i18n_init() {
    $pluginDir = dirname(plugin_basename(__FILE__));
    load_plugin_textdomain('indivisible', false, $pluginDir . '/languages/');
}


//////////////////////////////////
// Run initialization
/////////////////////////////////

// Initialize i18n
add_action('plugins_loadedi','Indivisible_i18n_init');

// Run the version check.
// If it is successful, continue with initialization for this plugin
if (Indivisible_PhpVersionCheck()) {
    // Only load and run the init function if we know PHP version can parse it
    include_once('indivisible_init.php');
    Indivisible_init(__FILE__);
}
