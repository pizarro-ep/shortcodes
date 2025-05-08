<?php

function ldapConnect($host, $port = 389)
{
    $connection = ldap_connect($host, $port);

    if (!$connection) {
        throw new \Exception("No se pudo conectar al servidor LDAP en $host:$port");
    }

    // Configurar LDAP para usar LDAPv3
    ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($connection, LDAP_OPT_REFERRALS, 0); // Evitar redirecciones
    return $connection;
}

function searchUserInLDAP(string $username)
{
    try {
        if (empty($username)) {
            throw new Exception("El nombre de usuario no puede estar vacío.");
        }

        // Obtener configuración LDAP 
        $ldap_server = "ldap://25.0.1.25";
        $ldap_dn = "OU=MTC-Usuarios,DC=mtc,DC=gob,DC=pe";
        $ldap_user = "mtc\\hrojas-prov";
        $ldap_password = "Mtclima2024*";

        // Establecer la conexión LDAP
        $connection = ldapConnect($ldap_server);

        // Realizar bind con las credenciales de configuración si están disponibles
        if (!empty($ldap_user) && !empty($ldap_password)) {
            $bind = ldap_bind($connection, $ldap_user, $ldap_password);
            if (!$bind) {
                throw new \Exception("LDAP bind falló para el usuario {$ldap_user}: " . ldap_error($connection));
            }
        } else { // Intentar bind anónimo si es posible
            $bind = ldap_bind($connection);
            if (!$bind) {
                throw new \Exception("LDAP bind anónimo falló: " . ldap_error($connection));
            }
        }

        // Escapar el nombre de usuario para prevenir inyección LDAP
        $escapedUsername = ldap_escape($username, "", LDAP_ESCAPE_FILTER);
        //$escapedUsername = $username;
        // Construir el filtro de búsqueda LDAP para encontrar al usuario
        $filter = "(sAMAccountName={$escapedUsername})";
        $attributes = array( 'street', '+' );
        $result = ldap_search($connection, $ldap_dn, $filter, $attributes);

        if (!$result) {
            throw new \Exception("No se pudo buscar el usuario en Active Directory.");
        }

        $entries = ldap_get_entries($connection, $result);

        if ($entries["count"] == 0) {
            throw new Exception("El usuario no está registrado en Active Directory.");
        }

        // Obtener los detalles del usuario
        Header('Content-Type: application/json');
        echo json_encode($entries);

    } catch (\Exception $e) {
        echo $e->getMessage();  // Lanzar la excepción con el mensaje de error
    } catch (\Throwable $th) {
        echo $th->getMessage();  // Cualquier otra excepción no capturada
    } finally {
        ldap_close($connection);
    }
}


searchUserInLDAP("epizarro");