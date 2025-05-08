<?php

class PluginTicketflowRelations extends CommonDBTM
{
    public static $rightname = 'relations';

    public static function getTypeName($nb = 0)
    {
        return _n('TicketFlow', 'TicketFlows', $nb);
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        return __('Relación Plantilla - Categoría');
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


    public function processForm($action, $config)
    {
        if ($action === 'add' && $config->canCreate()) {
            $validationErrors = $this->validateData($_POST);
            if (empty($validationErrors)) {
                $result = $config->add($_POST);
                $message = $result ? __('Configuración guardada correctamente', 'ticketflow') : __('Error al guardar la configuración', 'ticketflow');
                $status = $result ? INFO : ERROR;
            } else {
                $message = implode('<br>', $validationErrors);
                $status = ERROR;
            }
            Session::addMessageAfterRedirect($message, false, $status);
        } elseif ($action === 'update' && PluginTicketflowTicketflow::canUpdate()) {
            $validationErrors = $this->validateData($_POST);
            if (empty($validationErrors)) {
                $result = $config->update($_POST);
                $message = $result ? __('Configuración actualizada correctamente', 'ticketflow') : __('Error al guardar la configuración', 'ticketflow');
                $status = $result ? INFO : ERROR;
            } else {
                $message = implode('<br>', $validationErrors);
                $status = ERROR;
            }
            Session::addMessageAfterRedirect($message, false, $status);
        } else {
            Html::displayRightError();
        }
    }

    public function validateData($data)
    {
        // Validaciones de campos
        $validations = [
            'description' => function ($value) {
                return empty($value) ? 'El campo nombre es obligatorio.' : null;
            },
            'itilcategories_id' => function ($value) {
                return !is_numeric($value) || $value <= 0 ? 'Categoría inválida.' : null;
            },
            'template_id' => function ($value) {
                return !is_numeric($value) || $value <= 0 ? 'Plantilla inválida.' : null;
            }
        ];

        // Ejecutar las validaciones
        foreach ($validations as $field => $validate) {
            if (isset($data[$field])) {
                $error = $validate($data[$field]);
                if ($error) {
                    $errors[] = $error;
                }
            } else {
                $errors[] = "El campo {$field} es requerido.";
            }
        }

        return $errors;
    }


    public function showForm($ID, $options = [])
    {
        $this->initForm($ID, $options);
        // Mostrar encabezado del formulario (navegación con flechas)
        if ($ID) {
            // Si se trata de una actualización (ID existe), mostramos las flechas de navegación
            $this->showFormHeader(['back_to_list' => true]); // Esto mostrará las flechas de navegación de GLPI
        } else {
            // Si es un formulario de creación, solo mostramos el título
            $this->showFormHeader();
        }

        echo isset($this->field['id'])
            ? Html::hidden('add', ['value' => '1'])
            : Html::hidden('update', ['value' => '1']);

        // Campo de texto: description
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Descripción') . "</td><td>";
        echo Html::input("description", ["value" => $this->fields['description']]);
        echo "</td>";
        echo "</tr>";

        // Campo de número: template_id
        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Plantilla de ticket') . "</td>";
        echo "<td>";
        Dropdown::show('TicketTemplate', [
            'name' => 'template_id',
            'value' => $this->fields["template_id"]
        ]);
        echo "</>";
        echo "</tr>";

        echo "<tr class='tab_bg_1'>";
        echo "<td>" . __('Categoría ITIL') . "</td>";
        echo "<td>";
        Dropdown::show('ITILCategory', [
            'name' => 'itilcategories_id',
            'value' => $this->fields["itilcategories_id"]
        ]);
        echo "</td>";
        echo "</tr>";

        $this->showFormButtons($options); // Agrega botones de cancelar, etc.
        Html::closeForm();

        return true;
    }

    public function showList()
    {
        global $DB;

        $result = $DB->request([
            "SELECT" => [
                "glpi_plugin_ticketflow_relations.*",
                "glpi_itilcategories.name AS category_name",
                "glpi_tickettemplates.name AS template_name"
            ],
            "FROM" => "glpi_plugin_ticketflow_relations",
            "LEFT JOIN" => [
                "glpi_itilcategories" => ["FKEY" => ["glpi_plugin_ticketflow_relations" => "itilcategories_id", "glpi_itilcategories" => "id"]],
                "glpi_tickettemplates" => ["FKEY" => ["glpi_plugin_ticketflow_relations" => "template_id", "glpi_tickettemplates" => "id"]]
            ]
        ]);

        echo "<div class='center'>";
        echo "<table class='tab_cadre_fixehov'>";
        echo "<tr class='noHover'><th>ID</th><th>Descripción</th><th>Ticket Template</th><th>Categoría ITIL</th></tr>";

        foreach ($result as $row) {
            $edit_url = "relations.form.php?id=" . $row['id'];
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td><a class='py-3' href='$edit_url'>" . $row['description'] . "</a></td>";
            echo "<td>" . $row['template_name'] . "</td>";
            echo "<td>" . $row['category_name'] . "</td>";
            echo "</tr>";
        }

        echo "</table>";
        echo "</div>";
    }
}
