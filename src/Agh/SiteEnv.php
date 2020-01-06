<?php

namespace Agh;

class SiteEnv {
  protected $contactRecordStub;
  protected $cms;
  protected $baseUrl;
  protected $apiUrl;
  protected $apiKey;
  protected $siteKey;

  public function __construct($username, $password) {
    require __DIR__ . '/../../config/config.php';
    if (empty($ldap_user_table[$username])) {
      throw new Exception('No such user found', 100);
    }
    if (empty($ldap_user_table[$username]['password'])
      || $ldap_user_table[$username]['password'] !== $password) {
      throw new Exception('Invalid password', 150);
    }
    if (empty($ldap_user_table[$username]['site'])
      || empty($ldap_sites[$ldap_user_table[$username]['site']])) {
      throw new Exception('Cannot find site details', 200);
    }

    foreach (['cms', 'baseUrl', 'apiKey', 'siteKey'] as $detail) {
      if (empty($ldap_sites[$ldap_user_table[$username]['site']][$detail])) {
        throw new Exception("Cannot find $detail for site {$ldap_user_table[$username]['site']} in config", 250);
      }
      $this->$detail = $ldap_sites[$ldap_user_table[$username]['site']][$detail];
    }
    $this->setUrls();
  }

  protected function setUrls() {
    switch ($this->cms) {
      case 'Joomla':
        $this->contactRecordStub = "$this->baseUrl/administrator/?option=com_civicrm&task=civicrm/contact/view&reset=1&cid=";
        $this->apiUrl = "$this->baseUrl/administrator/components/com_civicrm/civicrm/extern/rest.php";
        break;

      case 'WordPress':
        $this->contactRecordStub = "$this->baseUrl/wp-admin/admin.php?page=CiviCRM&q=civicrm/contact/view&reset=1&cid=";
        $this->apiUrl = "$this->baseUrl/wp-content/plugins/civicrm/civicrm/extern/rest.php";
        break;

      case 'Drupal':
      case 'Backdrop':
      default:
        $this->contactRecordStub = "$this->baseUrl/civicrm/contact/view?reset=1&cid=";
        $this->apiUrl = "$this->baseUrl/sites/all/modules/civicrm/extern/rest.php";
    }
  }

  /**
   * Get the contact record URL for a given contact ID.
   *
   * @param int $contactId
   *   The contact ID to look up.
   *
   * @return string
   *   The full URL.
   */
  public function getUrl(int $contactId) {
    return $this->contactRecordStub . $contactId;
  }

  public function api($entity, $action, $params) {
    $urlArgs = [
      'entity' => $entity,
      'action' => $action,
      'api_key' => $this->apiKey,
      'key' => $this->siteKey,
      'json' => json_encode($params),
    ];

    // Create a stream
    $opts = [
      'http' => [
        'method' => 'GET',
        'user_agent' => 'CiviCRM LDAP',
      ],
    ];

    $context = stream_context_create($opts);

    $data = file_get_contents(
      $this->apiUrl . '?' . http_build_query($urlArgs),
      FALSE,
      $context
    );

    return json_decode($data, TRUE);
  }

}
