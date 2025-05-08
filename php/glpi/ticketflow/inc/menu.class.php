<?php

class PluginTicketflowMenu extends CommonGLPI
{
    public static $rightname = 'entity';

    public static function getMenuName()
    {
        return __("Flujo de tickets", 'ticketflow');
    }

    public static function getMenuContent()
    {
        if (!Session::haveRight(static::$rightname, READ)) {
            return false;
        }

        $menu['title'] = self::getMenuName();
        $menu['page'] = Plugin::getPhpDir('ticketflow', false) . '/front/relations.php';
        $menu['links']['search'] = Plugin::getPhpDir('ticketflow', false) . '/front/relations.php';
        $menu['icon'] = 'fas fa-cogs';
        $itemtypes = ['PluginTicketflowRelations' => 'ticketflow'];

        foreach ($itemtypes as $itemtype => $option) {
            $menu['options'][$option] = [
                'title' => $itemtype::getTypeName(2),
                'page' => $itemtype::getSearchURL(false),
                'links' => [
                    'search' => $itemtype::getSearchURL(false),
                ],
            ];

            if ($itemtype::canCreate()) {
                $menu['options'][$option]['links']['add'] = $itemtype::getFormURL(false);
            }
        }
        return $menu;
    }
}
