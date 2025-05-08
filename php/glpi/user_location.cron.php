<?php

include(__DIR__ . "/../inc/includes.php");

function getUsersWithoutLocation($db, $excludes)
{
    $sql = "SELECT id, name, user_dn, locations_id FROM glpi_users 
            WHERE is_active = 1 AND user_dn IS NOT NULL AND user_dn != ''
            AND user_dn NOT REGEXP '{$excludes}'";
    return $db->doQuery($sql);
}

function proccessUser($user, $db, &$insert_count, &$query_count, &$location_cache)
{
    preg_match_all('/OU=([^,]+)/', $user['user_dn'], $matches);
    $ou_list = array_reverse($matches[1] ?? []);

    $level = 0;
    $parent_id = 0;
    $location_id = null;
    $skip_ous = ['MTC-Usuarios'];

    foreach ($ou_list as $ou_name) {
        if (in_array($ou_name, $skip_ous))
            continue;

        $key = "{$parent_id}|{$ou_name}";
        if (isset($location_cache[$key])) {
            $location_id = $location_cache[$key]['id'];
            $level = $location_cache[$key]['level'];
        } else {
            $location = new Location();
            if ($location->getFromDBByCrit(['name' => $ou_name, 'locations_id' => $parent_id, 'entities_id' => 0])) {
                $query_count++;
                $location_id = $location->fields['id'];
                $level = $location->fields['level'];
            } else {
                $level++;
                $comment = "Ubicación creada desde el cron";
                $input = ['name' => $ou_name, 'comment' => $comment, 'locations_id' => $parent_id, 'level' => $level, 'entities_id' => 0];
                $location_id = $location->add($input);
                $insert_count++;
            }
            $location_cache[$key] = ['id' => $location_id, 'level' => $level];
        }

        $parent_id = $location_id;
    }

    return $location_id;
}

function updateLocations(&$completed = false)
{
    global $DB;
    $db = $DB;

    if (!$db)
        die("No se pudo obtener la conexión de GLPI.");

    $excludes = implode('|', ["OU=Cuentas especiales", "OU=Deshabilitados", "OU=Cuentas Externas", "OU=Lab - ITG", 'OU=Con Privilegios', 'OU=Sharepoint']);

    $query_count = 0;
    $insert_count = 0;
    $update_count = 0;
    $location_cache = [];

    $result = getUsersWithoutLocation($db, $excludes);
    $query_count++;

    while ($user = $result->fetch_assoc()) {
        $location_id = proccessUser($user, $db, $insert_count, $query_count, $location_cache);

        // Solo actualiza si cambió la ubicación
        if ($location_id && $user['locations_id'] != $location_id) {
            $db->update('glpi_users', ['locations_id' => $location_id], ['id' => $user['id']]);
            $update_count++;
        }
    }

    $completed = true;
    return [$query_count, $insert_count, $update_count];
}

// ----------- EJECUCIÓN ------------
try {
    echo "Iniciando ejecución de cron...<br>";
    [$q, $i, $u] = updateLocations($completed);
    echo "Ejecución de tarea para actualizar ubicación de usuarios<br>";
    echo "... Consultas realizadas: $q<br>";
    echo "... Ubicaciones creadas: $i<br>";
    echo "... Usuarios actualizados: $u<br>";
    echo $completed ? "✅ Cron ejecutado correctamente" : "❌ Error en la ejecución del cron";
} catch (Throwable $e) {
    echo "❌ Error en la ejecución del cron: " . $e->getMessage();
}
?>