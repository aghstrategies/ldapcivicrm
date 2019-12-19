<?php

namespace Agh;
use FreeDSx\Ldap\Server\RequestHandler\GenericRequestHandler;
use FreeDSx\Ldap\Server\RequestContext;
use FreeDSx\Ldap\Entry\Entries;
use FreeDSx\Ldap\Entry\Entry;
use FreeDSx\Ldap\Operation\Request\SearchRequest;

class RequestHandler extends GenericRequestHandler {
  public function bind(string $username, string $password): bool {
    // TODO: implement authentication
    return true;
    return isset($this->users[$username]) && $this->users[$username] === $password;
  }

  public function search(RequestContext $context, SearchRequest $search) : Entries {
    // Dummy from example
    return new Entries(
      Entry::fromArray('cn=Foo,dc=FreeDSx,dc=local', [
          'cn' => 'Foo',
          'sn' => 'Bar',
          'givenName' => 'Foo',
      ]),
      Entry::fromArray('cn=Chad,dc=FreeDSx,dc=local', [
          'cn' => 'Chad',
          'sn' => 'Sikorra',
          'givenName' => 'Chad',
      ])
    );
  }

}
