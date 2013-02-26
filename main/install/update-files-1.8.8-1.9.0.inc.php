<?php

/* For licensing terms, see /license.txt */

/**
 * Chamilo LMS
 *
 * Updates the Chamilo files from version 1.8.8 to version 1.9.0
 * This script operates only in the case of an update, and only to change the
 * active version number (and other things that might need a change) in the
 * current configuration file.
 * @package chamilo.install
 */
Log::notice('Entering file');

if (defined('SYSTEM_INSTALLATION')) {

    $conf_dir = api_get_path(CONFIGURATION_PATH);
    $portfolio_conf_dist = $conf_dir . 'portfolio.conf.dist.php';
    $portfolio_conf = $conf_dir . 'portfolio.conf.dist.php';

    if (!file_exists($portfolio_conf)) {
        copy($portfolio_conf_dist, $portfolio_conf);
    }

    // Edit the configuration file
    $file = file(api_get_path(CONFIGURATION_PATH) . 'configuration.php');
    $fh = fopen(api_get_path(CONFIGURATION_PATH) . 'configuration.php', 'w');
    $found_version_old = false;
    $found_stable_old = false;
    $found_version = false;
    $found_stable = false;
    $found_software_name = false;
    $found_software_url = false;
    foreach ($file as $line) {
        $ignore = false;
        if (stripos($line, '$userPasswordCrypted') !== false) {
            $line = '$_configuration[\'password_encryption\'] = \'' . ($userPasswordCrypted) . '\';' . "\r\n";
        } elseif (stripos($line, '?>') !== false) {
            $ignore = true;
        }
        if (!$ignore) {
            fwrite($fh, $line);
        }
    }
    if (!$found_version) {
        fwrite($fh, '$_configuration[\'system_version\'] = \'' . $new_version . '\';' . "\r\n");
    }
    if (!$found_stable) {
        fwrite($fh, '$_configuration[\'system_stable\'] = ' . ($new_version_stable ? 'true' : 'false') . ';' . "\r\n");
    }
    if (!$found_software_name) {
        fwrite($fh, '$_configuration[\'software_name\'] = \'' . $software_name . '\';' . "\r\n");
    }
    if (!$found_software_url) {
        fwrite($fh, '$_configuration[\'software_url\'] = \'' . $software_url . '\';' . "\r\n");
    }
    $string = "
//===========================================================================\n
// Hosting settings - Allows you to set limits to the Chamilo portal when\n
// hosting it for a third party. These settings can be overwritten by an\n
// optionally-loaded extension file with only the settings (no comments).\n
//===========================================================================\n
// Set a maximum number of users. Default (0) = no limit\n
".'$'."_configuration['hosting_limit_users'] = 0;\n
// Set a maximum number of teachers. Default (0) = no limit\n
".'$'."_configuration['hosting_limit_teachers'] = 0;\n
// Set a maximum number of courses. Default (0) = no limit\n
".'$'."_configuration['hosting_limit_courses'] = 0;\n
// Set a maximum number of sessions. Default (0) = no limit\n
".'$'."_configuration['hosting_limit_sessions'] = 0;\n
// Set a maximum disk space used, in MB (set to 1024 for 1GB, 5120 for 5GB).\n
// Default (0) = no limit\n
".'$'."_configuration['hosting_limit_disk_space'] = 0;\n
";
    fwrite($fh, $string);
    fwrite($fh, '?>');
    fclose($fh);
	//Adds events.conf file
	if (! file_exists(api_get_path(CONFIGURATION_PATH).'events.conf.php')) {
		copy(api_get_path(CONFIGURATION_PATH).'events.conf.dist.php', api_get_path(CONFIGURATION_PATH).'events.conf.php');
	}

} else {

    echo 'You are not allowed here !'. __FILE__;
}
