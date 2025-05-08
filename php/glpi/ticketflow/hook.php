<?php

function ticketflow_updateitem_called(CommonDBTM $item)
{
    $tf = new PluginTicketflowTicketflow();
    $tf->item = $item;

    // Actualizar piso
    if (!$tf->updateTicketFloor())
        return false;

    //🆗

    // Verificar si se puede crear ticket hijo
    if (!$tf->canCreateTicketChild())
        return false;

    // Verifcar si existe la categoría
    if (!$tf->existTemplateCategory())
        return false;

    // Crear ticket desde template
    $tf->createTicketByTemplate();
}

function plugin_ticketflow_install()
{
    PluginTicketflowTicketflow::installBaseData(new Migration('1.0.0'), '1.0.0');
}

function plugin_ticketflow_uninstall()
{
    PluginTicketflowTicketflow::uninstall();
}


function plugin_ticketflow_getAddSearchOptionsNew($itemtype)
{
    $sopt = [];

    if ($itemtype == 'ticketflow') {
        //$sopt['id'] = 12345;
        $sopt['table'] = TF_TABLE_PLUGIN_TICKETFLOW;
        $sopt['field'] = 'name';
        $sopt['name'] = __('Flujo de tickets', 'ticketflow');
        $sopt['datatype'] = 'itemlink';
        $sopt['forcegroupby'] = true;
        $sopt['usehaving'] = true;
    }

    return $sopt;
}

