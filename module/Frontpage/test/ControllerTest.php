<?php

namespace FrontpageTest;

use ApplicationTest\BaseControllerTest;
use Frontpage\Service\AclService;

class ControllerTest extends BaseControllerTest
{
    protected string $authServiceClassName = AclService::class;
    protected string $authServiceName = 'frontpage_service_acl';

    public function testIndexActionCanBeAccessed(): void
    {
        $this->dispatch('/');
        $this->assertResponseStatusCode(200);
    }

    public function testLangEnDoesRedirect(): void
    {
        $this->dispatch('/lang/en/');
        $this->assertResponseStatusCode(302);
    }

    public function testLangNlDoesRedirect(): void
    {
        $this->dispatch('/lang/nl/');
        $this->assertResponseStatusCode(302);
    }

    public function testPollHistoryActionCanBeAccessed(): void
    {
        $this->dispatch('/poll/history');
        $this->assertResponseStatusCode(200);
    }

    public function testPollRequestActionIsForbidden(): void
    {
        $this->dispatch('/poll/request');
        $this->assertResponseStatusCode(403);
    }

    public function testPollRequestActionCanBeAccessedAsUser(): void
    {
        $this->setUpWithRole();
        $this->dispatch('/poll/request');
        $this->assertResponseStatusCode(200);
    }

    public function testAssociationCommitteesActionCanBeAccessed(): void
    {
        $this->dispatch('/association/committees');
        $this->assertResponseStatusCode(200);
    }

    public function testAssociationFraternitiesActionCanBeAccessed(): void
    {
        $this->dispatch('/association/fraternities');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminNewsActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/news');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminPageActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/page');
        $this->assertResponseStatusCode(200);
    }

    public function testAdminPollActionCanBeAccessedAsAdmin(): void
    {
        $this->setUpWithRole('admin');
        $this->dispatch('/admin/poll');
        $this->assertResponseStatusCode(200);
    }
}
