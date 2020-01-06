<?php
require __DIR__ . '/../vendor/autoload.php';
use FreeDSx\Ldap\LdapServer;
use Agh\RequestHandler;

function getSslDetails() {
  require __DIR__ . '/../config/config.php';
  return [
    $ssl_cert_path,
    $ssl_key_path,
  ];
}

list($ssl_cert_path, $ssl_key_path) = getSslDetails();

$server = (new LdapServer([
  'request_handler' => RequestHandler::class,
  'ssl_cert' => $ssl_cert_path,
  'ssl_cert_key' => $ssl_key_path,
  'dse_naming_contexts' => 'dc=ldap,dc=aghstrategies,dc=com',
  'dse_vendor_name' => 'LDAP-CiviCRM',
  'dse_vendor_version' => '1.0',
]))->run();
