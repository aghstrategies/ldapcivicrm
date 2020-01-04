<?php
require "__FILE__/../vendor/autoload.php";
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
]))->run();
