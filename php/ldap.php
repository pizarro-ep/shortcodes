<?php 

function ldapConnect($host, $port = 389)
{
    $connection = ldap_connect($host, $port);

    if (!$connection) {
        logError("No se pudo conectar al servidor LDAP en $host:$port");
        throw new \Exception("No se pudo conectar al servidor LDAP en $host:$port");
    }

    // Configurar LDAP para usar LDAPv3
    ldap_set_option($connection, LDAP_OPT_PROTOCOL_VERSION, 3);
    ldap_set_option($connection, LDAP_OPT_REFERRALS, 0); // Evitar redirecciones
    return $connection;
}

/**
 * Función para autenticar al usuario usando LDAP.
 * @param string $username Nombre de usuario.
 * @param string $password Contraseña del usuario.
 * @return mixed Devuelve un array con la información del usuario si la autenticación es exitosa, false si no lo es.
 * @throws \Exception Si ocurre un error durante el proceso.
 */
function authenticateUserByLDPA(string $username, #[\SensitiveParameter] string $password)
{
    try {
        if (empty($username) || empty($password)) {
            throw new InvalidDataException("Las credenciales no pueden estar vacías.");
        }

        // Insertar Datos LDAP ----------------------------
        $this->insertConfigLDAP();
        // Obtener confiuguración ldpa
        $ldap_server = $this->getConfig('ldap_server');
        $ldap_dn = $this->getConfig('ldap_dn');
        $ldap_user = $this->getConfig('ldap_user');
        $ldap_password = $this->getConfig('ldap_password');

        // Establecer la conexión LDAP
        $connection = $this->ldapConnect($ldap_server);

        Helper::startSession(); // Inicia la sesión solo si no está ya iniciada
        if (!empty($ldap_user) && !empty($ldap_password)) {
            $bind = ldap_bind($connection, $ldap_user, $ldap_password);
            if (!$bind)
                throw new \Exception("LDAP bind falló para el usuario {$ldap_user}: " . ldap_error($connection));
        } else { // Intentar una bind anónima, si AD lo permite
            $bind = ldap_bind($connection);
            if (!$bind)
                throw new \Exception("LDAP bind anónimo falló: " . ldap_error($connection));
        }

        // Escapar el username para prevenir inyección LDAP
        $escapedUsername = ldap_escape($username, "", LDAP_ESCAPE_FILTER);

        // Construir el filtro LDAP
        $filter = "(sAMAccountName={$escapedUsername})";
        $attributes = ["dn", "cn", "givenname", "sn", "mail"];
        $result = ldap_search($connection, $ldap_dn, $filter, $attributes);

        if (!$result) {
            throw new Exceptions("No se pudo buscar el usuario en Active Directory.");
        }

        $entries = ldap_get_entries($connection, $result);

        if ($entries["count"] == 0) {
            throw new InvalidDataException("El usuario no está registrado en Active Directory.");
        }

        // Obtener el DN del usuario encontrado
        $user_dn = $entries[0]["dn"];
        if (@ldap_bind($connection, $user_dn, $password)) {
            // Retornar información del usuario
            return [
                "username" => $username,
                "cn" => $entries[0]["cn"][0],
                "email" => isset($entries[0]["mail"][0]) ? $entries[0]["mail"][0] : '',
                "name" => $entries[0]["givenname"][0] ?? '',
                "surname" => $entries[0]["sn"][0] ?? ''
            ];
        }
        return false; // Autenticación fallida

    } catch (InvalidDataException $i) {
        throw $i;
    } catch (Exceptions $e) {
        throw $e;
    } catch (\Throwable $th) {
        throw $th;
    } finally {
        $this->closeBindConnection($connection);
    }
}