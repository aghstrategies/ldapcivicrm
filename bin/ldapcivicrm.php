<?php
require "__FILE__/../vendor/autoload.php";
use FreeDSx\Ldap\LdapServer;
use Agh\RequestHandler;

$server = (new LdapServer(['request_handler' => RequestHandler::class]))->run();
