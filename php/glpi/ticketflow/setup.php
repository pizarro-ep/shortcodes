<?php

include __DIR__ . '/inc/define.php';

function plugin_init_ticketflow()
{
   global $PLUGIN_HOOKS;
   $PLUGIN_HOOKS['csrf_compliant']['ticketflow'] = true;
   // Hook al crear un ticket
   //$PLUGIN_HOOKS['add_item']['ticketflow'] = ['Ticket' => 'plugin_ticketflow_ticket_created'];
   $PLUGIN_HOOKS['item_update']['ticketflow'] = ['Ticket' => 'ticketflow_updateitem_called'];

   // add link in plugin page
   $PLUGIN_HOOKS['config_page']['ticketflow'] = 'front/relations.php';

   // add entry to configuration menu
   $PLUGIN_HOOKS['menu_toadd']['ticketflow'] = ['plugins' => 'PluginTicketflowMenu'];

   Plugin::registerClass('PluginTicketflowMenu');
}

function plugin_version_ticketflow()
{
   $version['name'] = 'Ticket Flow';
   $version['version'] = PLUGIN_TICKETFLOW_VERSION;
   $version['author'] = 'Eusebio Pizarro';
   $version['license'] = 'MIT';
   $version['homepage'] = 'https://github.com/pizarro-ep';
   $version['requirements']['glpi']['min'] = PLUGIN_TICKETFLOW_MIN_GLPI_VERSION;
   $version['requirements']['glpi']['max'] = PLUGIN_TICKETFLOW_MAX_GLPI_VERSION;

   return $version;
}

function plugin_ticketflow_check_prerequisites()
{
   return true;
}

function plugin_ticketflow_check_config($verbose = false)
{
   if (true) { // Your configuration check
      return true;
   }

   if ($verbose) {
      //_e('Installed / not configured', 'ticketflow');
   }

   return false;
}

