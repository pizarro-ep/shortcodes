<?php

// plugin config constants
define('PLUGIN_TICKETFLOW_VERSION', '1.2.7');
// Minimal GLPI version, inclusive
define("PLUGIN_TICKETFLOW_MIN_GLPI_VERSION", "10.0.0");
// Maximum GLPI version, exclusive
define("PLUGIN_TICKETFLOW_MAX_GLPI_VERSION", "10.0.99");

// PLUGIN CONSTANTS
define('TF_PREFIX', 'glpi_plugin_');
define('TF_TABLE_PLUGIN_TICKETFLOW', TF_PREFIX . 'ticketflow_relations');
define('TF_TABLE_PLUGIN_FIELDS', TF_PREFIX . 'fields');
define('TF_TABLE_FLOORFIELDS', TF_TABLE_PLUGIN_FIELDS . '_ticketpisos');
define('TF_TABLE_FLOORFIELD_DROPDOWNS', TF_TABLE_PLUGIN_FIELDS . '_pisofielddropdowns');
define('TF_TABLE_FLOORFIELD_DROPDOWNS_ID', str_replace('glpi_', '', TF_TABLE_FLOORFIELD_DROPDOWNS) . "_id");
