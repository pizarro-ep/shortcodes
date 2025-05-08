<?php
/**
 * Whatsapp block settings
 *
 * @package    block_whatsapp
 * @copyright  2024 Eusebio Piarro (https://github.com/pizarro-ep)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) { // Asegúrate de que el usuario tenga permisos de administrador.
    $settings = new admin_settingpage('block_whatsappsettings', get_string('pluginname', 'block_whatsapp'));

    $settings->add(new admin_setting_configtext(
        'block_whatsapp/apikey',
        get_string('apikey', 'block_whatsapp'),
        get_string('configapikey', 'block_whatsapp'),
        '',
    ));
    
    $settings->add(new admin_setting_configtext(
        'block_whatsapp/phonenumberid',
        get_string('phonenumberid', 'block_whatsapp'),
        get_string('configphonenumberid', 'block_whatsapp'),
        '',
    ));

    $ADMIN->add('blocksettings', $settings);
}