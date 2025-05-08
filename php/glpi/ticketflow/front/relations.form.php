<?php
include('../../../inc/includes.php');

$config = new PluginTicketflowRelations();

// Procesar la acción (add o update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $config->processForm('add', $config);
    } elseif (isset($_POST['update'])) {
        $config->processForm('update', $config);
    }
    Html::redirect($_SERVER['PHP_SELF'] . ($_POST['id'] ? '?id=' . $_POST['id'] : ''));
}

// Mostrar formulario
Html::header(
    __('Relación de Ticketflow', 'ticketflow'),
    $_SERVER['PHP_SELF'],
    'plugins',
    'ticketflow',
    'relations',
);

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $config->getFromDB($_GET['id']);
}

$config->showForm($_GET['id'] ?? 0);

Html::footer();
?>
