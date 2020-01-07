<?php
/**
 * LDAP for CiviCRM
 * Copyright (C) 2020 AGH Strategies, LLC
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

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
