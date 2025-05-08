<?php
include('../../../inc/includes.php');

Html::header(
   __('Relaciones TicketFlow', 'ticketflow'),
   $_SERVER['PHP_SELF'],
   'plugins',
   'ticketflow',
   'relations'
);

$config = new PluginTicketflowRelations();
Session::checkRight('entity', READ);

// Botón "Añadir"
echo "<div class='center'>";
echo "<a class='vsubmit' href='relations.form.php'>" . __('Añadir', 'ticketflow') . "</a>";
echo "</div><br>";
$config->rawSearchOptions();
$config->showList();

Html::footer();