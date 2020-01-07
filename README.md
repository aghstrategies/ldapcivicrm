# LDAP for CiviCRM

This is an LDAP server using [FreeDSx LDAP](https://github.com/FreeDSx/LDAP), a
pure PHP library, to serve contacts from one or more CiviCRM sites using the
REST API.

It is unrelated to the ldapjs-based https://github.com/TechToThePeople/ldapcivi,
though it is intended to be an equivalent.

## Known issues and limitations

1.  Complex searches are simplified: ANDs become ORs.  This works for typical
    autocomplete usage in an email client, assuming that more detailed searches
    would be performed from within CiviCRM.  This is just a limitation out of
    laziness--improvements are possible and welcome.

2.  The only fields that are queried are First Name, Last Name, Email, and
    Display Name.  Any filters on other fields get filtered against Display
    Name.  This is also just a limitation out of laziness--improvements are
    possible and welcome.

3.  All queries are applied according to the permissions of the CiviCRM contact
    with the API key that you are using for the site configuration.  If you wish
    to have varying levels of access for a single site, you configure multiple
    site instances, each with the same base URL, site key, and CMS, but with API
    keys of different CiviCRM contacts.  This is a structural limitation from
    using the REST API as a single contact rather than authenticating each LDAP
    user against CiviCRM's CMS.

4.  There is no support for user self-service.  It would be natural to have a
    database backend for users and site configuration instead of storing
    everything in config.php, ideally manageable over the web.  That would
    simply be a whole chunk of features to build out.

5.  Paths to civicrm/extern/rest.php are defaults for each CMS.  It would be
    ideal to allow a per-site override in case the REST endpoint is in a
    non-standard location.

6.  This supports (and assumes) LDAP with STARTTLS on port 389 but not LDAPS on
    port 636.  I think this is a limitation with the upstream FreeDSx LDAP
    project.

## Setup

(Tested on Ubuntu 18.04)

1.  Put this repository as `/srv/ldapcivicrm` (or somewhere else if you edit the
    `ldapcivicrm.service` file).

2.  Copy `service/ldapcivicrm.service` to `/etc/systemd/system/ldapcivicrm.service`

3.  Copy `config/config.php.example` to `config/config.php` and edit it
    to configure one or more users and one or more sites.

4.  Make sure port 389 is open on your server's firewall.

5.  Start the service and enable it for startup on boot.

    > sudo systemctl start ldapcivicrm.service

    > sudo systemctl enable ldapcivicrm.service
