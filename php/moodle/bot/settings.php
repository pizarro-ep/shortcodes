<?php
/**
 * bot block settings
 *
 * @package    block_bot
 * @copyright  2024 Eusebio Piarro (https://github.com/pizarro-ep)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) { // Asegúrate de que el usuario tenga permisos de administrador.
    $settings = new admin_settingpage('block_botsettings', get_string('pluginname', 'block_bot'));

    $settings->add(new admin_setting_configtext(
        'block_bot/apikey',
        get_string('apikey', 'block_bot'),
        get_string('configapikey', 'block_bot'),
        '',
        PARAM_TEXT
    ));

    $settings->add(new admin_setting_configtext(
        'block_bot/apiurl',
        get_string('apiurl', 'block_bot'),
        get_string('configapiurl', 'block_bot'),
        '',
        PARAM_TEXT
    ));
    
    $settings->add(new admin_setting_configtext(
        'block_bot/botname',
        get_string('botname', 'block_bot'),
        get_string('configbotname', 'block_bot'),
        'SV MTC BOT',
        PARAM_TEXT

    ));

    $ADMIN->add('blocksettings', $settings);
}