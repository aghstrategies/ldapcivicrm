<?php

namespace Agh;

class SiteEnv {
  protected $contactRecordStub;
  protected $cms;
  protected $baseUrl;
  protected $apiUrl;
  protected $apiKey;
  protected $siteKey;

  public function __construct() {
    require __DIR__ . '/../../config/config.php';
    $this->cms = $civicrm_cms;
    $this->baseUrl = $civicrm_base_url;
    $this->apiKey = $civicrm_api_key;
    $this->siteKey = $civicrm_site_key;
    $this->setUrls();
  }

  public static function getUsers() {
    require __DIR__ . '/../../config/config.php';
    return $ldap_user_table;
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
    // TODO: stop debug
    print_r($params);
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

    $result = json_decode($data, TRUE);
    // TODO: stop debug
    print_r($result);
    return $result;
  }

}
