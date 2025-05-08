<?php

class PluginTicketflowTicketflow extends CommonDBTM
{
    public static $rightname = 'ticketflow';
    public $item = null;
    public $template_id = 0;

    public static function getTypeName($nb = 0)
    {
        return _n('TicketFlow', 'TicketFlows', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return __('Configuración');
    }

    public function defineTabs($options = [])
    {
        $ong = [];
        $this->addStandardTab(__CLASS__, $ong, $options);
        return $ong;
    }

    public static function canCreate()
    {
        return self::canUpdate();
    }

    public static function canView()
    {
        return Session::haveRight('config', 1);
    }

    public static function canUpdate()
    {
        return Session::haveRight('config', 1);
    }



    public function rawSearchOptions()
    {
        $options = [];

        $options[] = [
            'id' => 'common',
            'name' => __('Characteristics')
        ];

        $options[] = [
            'id' => 1,
            'table' => self::getTable(),
            'field' => 'name',
            'name' => __('Name'),
            'datatype' => 'itemlink'
        ];

        $options[] = [
            'id' => 2,
            'table' => self::getTable(),
            'field' => 'id',
            'name' => __('ID')
        ];

        $options[] = [
            'id' => 3,
            'table' => self::getTable(),
            'field' => 'id',
            'name' => __('Number of associated assets', 'myplugin'),
            'datatype' => 'count',
            'forcegroupby' => true,
            'usehaving' => true,
            'joinparams' => [
                'jointype' => 'child',
            ]
        ];


        return $options;
    }


    public function updateTicketFloor()
    {
        if ($this->item::getType() !== Ticket::getType())
            return false;

        global $DB;

        $ticket_id = $this->item->getID(); // ID del ticket actual
        $requested_user = $this->getRequestedUser($ticket_id);
        $floor_id = $this->getFloorIdByUser($requested_user);

        if (isset($floor_id)) {
            $this->insertOrUpdateFloorTicket($ticket_id, $floor_id);
        }

        return true;
    }

    private function insertOrUpdateFloorTicket($ticket_id, $floor_id)
    {
        global $DB;
        $res = $DB->request(['SELECT' => ['id'], 'FROM' => TF_TABLE_FLOORFIELDS, 'WHERE' => ['items_id' => $ticket_id], 'LIMIT' => 1]);
        if ($res->count() === 0) {
            $DB->insert(TF_TABLE_FLOORFIELDS, [
                'items_id' => $ticket_id,
                'itemtype' => Ticket::getType(),
                'plugin_fields_containers_id' => 12, // VERIFICAR
                'entities_id' => $this->item->fields['entities_id'],
                TF_TABLE_FLOORFIELD_DROPDOWNS_ID => $floor_id
            ]);
        } else {
            $DB->update(TF_TABLE_FLOORFIELDS, [TF_TABLE_FLOORFIELD_DROPDOWNS_ID => $floor_id], ['id' => $res->current()['id']]);
        }
    }

    private function getRequestedUser($ticket_id)
    {
        global $DB;

        $res = $DB->request(['SELECT' => ['users_id'], 'FROM' => 'glpi_tickets_users', 'WHERE' => ['tickets_id' => $ticket_id, 'type' => 1], 'LIMIT' => 1]);

        if (count($res)) {
            $user_id = $res->current()['users_id'];
            $user = $DB->request(['SELECT' => ['id', 'comment'], 'FROM' => 'glpi_users', 'WHERE' => ['id' => $user_id], 'LIMIT' => 1])->current();
            return $user ?: null;
        }

        return null;
    }


    public function getFloorIdByUser($requested_user)
    {
        global $DB;

        $floor = $requested_user['comment'];

        if (empty($floor))
            return false;

        // Buscar si ya existe
        $res = $DB->request(['SELECT' => ['id'], 'FROM' => TF_TABLE_FLOORFIELD_DROPDOWNS, 'WHERE' => ['name' => $floor], 'LIMIT' => 1]);

        if ($res->count())
            return $res->current()['id'];

        $new_id = $DB->insert(TF_TABLE_FLOORFIELD_DROPDOWNS, ['name' => $floor, 'completename' => $floor, 'level' => 1]);
        return $new_id;
    }


    public function canCreateTicketChild(): bool
    {
        if ($this->item->fields['status'] !== Ticket::CLOSED)
            return false;

        global $DB;
        $parent_id = $this->item->getID();
        $res = $DB->request(['SELECT' => ['id'], 'FROM' => 'glpi_tickets_tickets', 'WHERE' => ['tickets_id_2' => $parent_id], 'LIMIT' => 1]);

        return ($res->count() === 0);
    }


    public function existTemplateCategory()
    {
        $itilcategories_id = $this->item->fields['itilcategories_id'];

        global $DB;

        $res = $DB->request(['SELECT' => ['template_id'], 'FROM' => TF_TABLE_PLUGIN_TICKETFLOW, 'WHERE' => ['itilcategories_id' => $itilcategories_id], 'LIMIT' => 1]);

        if ($res->count() === 0)
            return false;

        $row = $res->current();
        $this->template_id = $row['template_id'];

        $template = new TicketTemplate();
        return $template->getFromDB($this->template_id);
    }


    public function createTicketByTemplate()
    {
        $ttf = new TicketTemplatePredefinedField();

        $fields = $ttf->find(['tickettemplates_id' => $this->template_id]);

        // Armar el input manualmente
        $input = [];
        $new_ticket = new Ticket();
        $fields_t = $new_ticket->rawSearchOptions();

        // Indexar $fields por 'num'
        $fields_indexed = [];
        foreach ($fields as $f) {
            $fields_indexed[$f['num']] = $f['value'];
        }

        $fields_to_add = array_reduce($fields_t, function ($carry, $item) use ($fields_indexed) {
            if (isset($fields_indexed[$item['id']])) {
                $carry[] = array_merge($item, ['value' => $fields_indexed[$item['id']]]);
            }
            return $carry;
        }, []);

        // Asegurar campos básicos necesarios
        $input['entities_id'] = $this->item->fields['entities_id'];
        $input['status'] = Ticket::INCOMING;

        // Asignar todos los campos definidos en la plantilla
        foreach ($fields_to_add as $key => $field) {
            if (!empty($field['table']) && $field['table'] === "glpi_tickets") {
                $input[$field['field']] = $field['value'];
            } else if (!empty($field['table']) && $field['table'] === "glpi_itilcategories") {
                $input['itilcategories_id'] = $field['value'];
            } else if (!empty($field['table']) && $field['table'] === "glpi_requesttypes") {
                $input['requesttypes_id'] = $field['value'];
            }
        }

        // Relacionar como subticket
        $input['tickets_id'] = $this->item->getID();
        // Crear el ticket
        $new_ticket = new Ticket();
        $id_addeted = $new_ticket->add($input);

        if ($id_addeted) {
            $this->createChildTicket($this->item->getID(), $id_addeted);
        }

        return true;
    }

    private function createChildTicket($parent_id, $child_id)
    {
        global $DB;

        $DB->insert("glpi_tickets_tickets", [
            'tickets_id_1' => $parent_id,
            'tickets_id_2' => $child_id,
            'link' => 3
        ]);
    }

    public static function installBaseData(Migration $migration, $version)
    {
        /** @var DBmysql $DB */
        global $DB;

        $default_charset = DBConnection::getDefaultCharset();
        $default_collation = DBConnection::getDefaultCollation();
        $default_key_sign = DBConnection::getDefaultPrimaryKeySignOption();

        $table = TF_TABLE_PLUGIN_TICKETFLOW;

        if (!$DB->tableExists($table)) {
            $migration->displayMessage(sprintf(__('Installing %s'), $table));

            $query = "CREATE TABLE IF NOT EXISTS `$table` (
                  `id`                          INT                 {$default_key_sign} NOT NULL AUTO_INCREMENT,
                  `description`                 VARCHAR(255)        NOT NULL,
                  `template_id`                 INT                 NOT NULL,
                  `itilcategories_id`           INT                 NOT NULL,
                  `created_at`                  TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
                  `updated_at`                  TIMESTAMP           NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=$default_charset COLLATE=$default_collation ROW_FORMAT=DYNAMIC;";
            $DB->doQuery($query) or die($DB->error());
        }
    }

    public static function uninstall()
    {
        /** @var DBmysql $DB */
        global $DB;

        $DB->doQuery('DROP TABLE IF EXISTS `' . TF_TABLE_PLUGIN_TICKETFLOW . '`');

        return true;
    }
}
