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

namespace Agh;

use FreeDSx\Ldap\Server\RequestHandler\GenericRequestHandler;
use FreeDSx\Ldap\Server\RequestContext;
use FreeDSx\Ldap\Entry\Entries;
use FreeDSx\Ldap\Entry\Rdn;
use FreeDSx\Ldap\Entry\Entry;
use FreeDSx\Ldap\Operation\Request\SearchRequest;

class RequestHandler extends GenericRequestHandler {

  const STANDARD_PARAMS = [
    'sequential' => 1,
    'contact_type' => 'Individual',
    'options' => ['limit' => 25],
  ];

  const FIELD_MAP = [
    'cn' => 'sort_name',
    'mail' => 'email',
    'givenname' => 'first_name',
    'sn' => 'last_name',
    'title' => 'job_title',
    'co' => 'country',
    'l' => 'city',
    'st' => 'state_province',
    'street' => 'street_address',
    'postaladdress' => 'street_address',
    'postalcode' => 'postal_code',
    'telephonenumber' => 'phone',
    'o' => 'current_employer',
    'company' => 'current_employer',
    'displayName' => 'display_name',
  ];

  protected $users = [];

  /**
   * @var Agh\SiteEnv
   */
  protected $site;

  public function bind(string $username, string $password): bool {
    try {
      $this->site = new SiteEnv($username, $password);
    }
    catch (Exception $e) {
      switch ($e->getCode()) {
        case 200:
          // Can't cross-reference site
          // TODO: log this
        case 250:
          // Can't find site detail
          // TODO: log this

        case 100:
        case 150:
          // No such user
          return FALSE;
      }
    }
    return TRUE;
  }

  public function search(RequestContext $context, SearchRequest $search) : Entries {

    $params = self::STANDARD_PARAMS;
    $params['options']['limit'] = $search->getSizeLimit();

    $filter = $search->getFilter();

    if (is_a($filter, 'FreeDSx\Ldap\Search\Filter\PresentFilter')) {
      // get contact id from `cn` after the `civi_` prefix
      $baseDn = $search->getBaseDn();
      $rdn = $baseDn->getRdn();
      if ($rdn->getName() == 'cn') {
        $contactId = substr($rdn->getValue(), 5);
        return $this->singleContact($contactId, $baseDn);
      }
    }
    else {
      $searches = $vals = [];
      $this->apiValsFromFilter($vals, $filter, $searches);
      if (empty($vals)) {
        return new Entries();
      }
      return $this->formatContacts($vals, $search->getBaseDn());
    }
  }

  protected function apiValsFromFilter(&$vals, $filter, &$searches) {
    switch (get_class($filter)) {
      case 'FreeDSx\Ldap\Search\Filter\SubstringFilter':
      case 'FreeDSx\Ldap\Search\Filter\EqualityFilter':
        $params = self::paramsFromFilter($filter);
        $searchJson = json_encode($params);
        if (in_array($searchJson, $searches)) {
          break;
        }
        $searches[] = $searchJson;
        $result = $this->site->api('contact', 'get', $params);
        if (!empty($result['values'])) {
          // Cheat to cut out repeats
          foreach ($result['values'] as $val) {
            $vals[$val['id']] = $val;
          }
        }
        break;

      case 'FreeDSx\Ldap\Search\Filter\AndFilter':
        // We'll cheat a bit and treat "AND" as "OR" since search isn't usually
        // this complex.
      case 'FreeDSx\Ldap\Search\Filter\OrFilter':
        $filters = $filter->get();
        foreach ($filter as $f) {
          $this->apiValsFromFilter($vals, $f, $searches);
        }
        break;
    }
  }

  /**
   * Get CiviCRM API parameters from a filter
   *
   * @param FreeDSx\Ldap\Search\Filter\FilterInterface|FreeDSx\Ldap\Search\Filter\EqualityFilter $filter
   *   The filter used on this search.
   *
   * @return array
   *   The parameters to send to the CiviCRM API.
   */
  protected static function paramsFromFilter($filter) {
    $params = self::STANDARD_PARAMS;
    $params['return'] = array_merge(
      array_values(self::FIELD_MAP),
      [
        'supplemental_address_1',
        'supplemental_address_2',
      ]
    );
    switch ($filter->getAttribute()) {
      case 'mail':
        $filterField = 'email';
        break;

      case 'givenName':
        $filterField = 'first_name';
        break;

      case 'sn':
        $filterField = 'last_name';
        break;

      case 'displayName':
      default:
        if (is_a($filter, 'FreeDSx\Ldap\Search\Filter\EqualityFilter')) {
          $params['display_name'] = $filter->getValue();
          return $params;
        }

        // display_name and sort_name are kind of broken in CiviCRM APIv3 - see
        // https://issues.civicrm.org/jira/browse/CRM-17042
        // We'll do this workaround and return.
        if ($startsWith = $filter->getStartsWith()) {
          $params['display_name'] = $startsWith;
        }
        if ($endsWith = $filter->getEndsWith()) {
          $params['display_name'] = $endsWith;
        }
        if ($contains = $filter->getContains()) {
          // Cheating and just pulling the first item from contains
          $params['display_name'] = array_shift($contains);
        }
        return $params;
    }

    if (is_a($filter, 'FreeDSx\Ldap\Search\Filter\EqualityFilter')) {
      $params[$filterField] = $filter->getValue();
    }
    else {
      if ($startsWith = $filter->getStartsWith()) {
        $params[$filterField] = ['LIKE' => "$startsWith%"];
      }
      if ($endsWith = $filter->getEndsWith()) {
        $params[$filterField] = ['LIKE' => "%$endsWith"];
      }
      if ($contains = $filter->getContains()) {
        // Cheating and just pulling the first item from contains
        $fcontains = array_shift($contains);
        $params[$filterField] = ['LIKE' => "%$fcontains%"];
      }
    }
    return $params;
  }

  /**
   * Format API results as LDAP entries.
   *
   * @param array $values
   *   Values from the CiviCRM API.
   * @param FreeDSx\Ldap\Entry\Dn $baseDn
   *   The base dn from the search.
   *
   * @return FreeDSx\Ldap\Entry\Entries
   */
  protected function formatContacts($values, $baseDn) {
    $entries = new Entries();
    foreach ($values as $value) {
      $attribs = self::prepEntry($value);
      $attribs['homeurl'] = $this->site->getUrl($value['id']);
      if (!empty($value['supplemental_address_1'])) {
        $attribs['postaladdress'] .= ", {$value['supplemental_address_1']}";
      }
      if (!empty($value['supplemental_address_2'])) {
        $attribs['postaladdress'] .= ", {$value['supplemental_address_2']}";
      }
      $attribs['info'] = "CiviCRM contact record: {$attribs['homeurl']}";
      $entries->add(Entry::fromArray(self::dnWithCiviId($value['id'], $baseDn), $attribs));
    }

    return $entries;
  }

  /**
   * Retrieve a single contact by ID.
   *
   * @param int $contactId
   * @param FreeDSx\Ldap\Entry\Dn $baseDn
   *
   * @return FreeDSx\Ldap\Entry\Entries
   */
  protected function singleContact($contactId, $baseDn) {
    $result = $this->site->api('contact', 'get', [
      'id' => $contactId,
      'option.limit' => 1,
      'return' => array_values(self::FIELD_MAP),
    ]);
    if (!empty($result['values'][0])) {
      return new Entries(
        Entry::fromArray($baseDn->toString(), self::prepEntry($result['values'][0]))
      );
    }
  }

  /**
   * Prepare LDAP attributes from a CiviCRM API row
   *
   * @param array $row
   *   A record from an API request.
   * @return array
   *   Attributes ready to publish as an LDAP entry.
   */
  protected static function prepEntry($row) {
    $attribs = [];
    foreach (self::FIELD_MAP as $lField => $cField) {
      if (array_key_exists($cField, $row)) {
        $attribs[$lField] = $row[$cField];
      }
    }
    return $attribs;
  }

  /**
   * Set up a dn starting with "civi_" and the contact ID.
   *
   * @param int $id
   *   The contact ID.
   * @param FreeDSx\Ldap\Entry\Dn $baseDn
   *   The base dn from the search.
   * @return string
   *   The new dn string.
   */
  protected static function dnWithCiviId($id, $baseDn) {
    $dnArray = $baseDn->toArray();
    array_unshift($dnArray, new Rdn('cn', 'civi_' . $id));
    array_walk($dnArray, ['self', 'rdnToString']);
    return implode(',', $dnArray);
  }

  /**
   * Callback for setting items to a string
   *
   * @param FreeDSx\Ldap\Entry\Rdn $val
   *   The piece from the search's baseDn.  It gets reset as a string.
   * @param int $i
   *   Array index (not used)
   */
  protected static function rdnToString(&$val, $i) {
    $val = $val->toString();
  }

}
