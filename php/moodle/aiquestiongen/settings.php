<?php
/**
 * Whatsapp block settings
 *
 * @package    block_aiquestiongen
 * @copyright  2024 Eusebio Piarro (https://github.com/pizarro-ep)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) { // Asegúrate de que el usuario tenga permisos de administrador.
    $settings = new admin_settingpage('block_aiquestiongensettings', get_string('pluginname', 'block_aiquestiongen'));

    $settings->add(new admin_setting_configtext(
        'block_aiquestiongen/apikey',
        get_string('apikey', 'block_aiquestiongen'),
        get_string('configapikey', 'block_aiquestiongen'),
        '',
    ));

    $settings->add(new admin_setting_configtext(
        'block_aiquestiongen/apikey',
        get_string('apikey', 'block_aiquestiongen'),
        get_string('apikeylabel', 'block_aiquestiongen'),
        ''
    ));
    
    $settings->add(new admin_setting_configselect(
        'block_aiquestiongen/model',
        get_string('model', 'block_aiquestiongen'),
        get_string('modellabel', 'block_aiquestiongen'),
        'gpt-3.5-turbo-1106',
        [
            'gpt-4' => 'gpt-4',
            'gpt-4-1106-preview' => 'gpt-4-1106-preview',
            'gpt-4-0613' => 'gpt-4-0613',
            'gpt-4-0314' => 'gpt-4-0314',
            'gpt-3.5-turbo' => 'gpt-3.5-turbo',
            'gpt-3.5-turbo-16k' => 'gpt-3.5-turbo-16k',
            'gpt-3.5-turbo-1106' => 'gpt-3.5-turbo-1106',
            'gpt-3.5-turbo-0613' => 'gpt-3.5-turbo-0613',
            'gpt-3.5-turbo-16k-0613' => 'gpt-3.5-turbo-16k-0613',
        ]
    ));

    $ADMIN->add('blocksettings', $settings);
}