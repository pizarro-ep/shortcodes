<?php

use Dotenv\Dotenv;

try {
    // Cargar las variables del archivo .env
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();
$dotenv->safeLoad(); // No genera errores si el archivo .env no existe

// Definir constantes basadas en las variables de entorno
define("DB_HOST", $_ENV['DB_HOST']);
define("DB_USER", $_ENV['DB_USER']);
define("DB_PASS", $_ENV['DB_PASS']);
define("DB_NAME", $_ENV['DB_NAME']);
define("DB_PORT", (int) $_ENV['DB_PORT']);

define("PROJECT_NAME", $_ENV['PROJECT_NAME']);
define("APP_HOST", $_ENV['APP_HOST']);
define("BASE_URL", $_ENV['BASE_URL']);

if (!empty($_ENV['LDAP_SERVER'])) define("LDAP_SERVER", $_ENV['LDAP_SERVER']);
if (!empty($_ENV['LDAP_DN'])) define("LDAP_DN", $_ENV['LDAP_DN']);
if (!empty($_ENV['LDAP_USER'])) define("LDAP_USER", $_ENV['LDAP_USER']);
if (!empty($_ENV['LDAP_PASS'])) define("LDAP_PASS", $_ENV['LDAP_PASS']);

define("SMTP_HOST", $_ENV['SMTP_HOST']);
define("SMTP_PORT", (int) $_ENV['SMTP_PORT']);
if (!empty($_ENV['SMTP_AUTH'])) define("SMTP_AUTH", filter_var($_ENV["SMTP_AUTH"], FILTER_VALIDATE_BOOLEAN));
if (!empty($_ENV['SMTP_USER'])) define("SMTP_USER", $_ENV['SMTP_USER']);
if (!empty($_ENV['SMTP_PASS'])) define("SMTP_PASS", $_ENV['SMTP_PASS']);

define("SECRET_KEY", $_ENV['SECRET_KEY']);
define("ENCRYPTION_KEY", $_ENV['ENCRYPTION_KEY']);
define("ENCRYPTION_KEY_2", $_ENV['ENCRYPTION_KEY_2']);

define("OAUTH_CLIENT_ID", $_ENV['OAUTH_CLIENT_ID']);
define("OAUTH_CLIENT_SECRET", $_ENV['OAUTH_CLIENT_SECRET']);

define("ENTITY_DEFAULT", (int) $_ENV['ENTITY_DEFAULT']);
} catch (\Throwable $th) { 
    die ("Error en el sistema" . $th->getMessage());
}