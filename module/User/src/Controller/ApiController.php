<?php

namespace User\Controller;

use Decision\Service\MemberInfo as MemberInfoService;
use Laminas\Http\{
    PhpEnvironment\Response as EnvironmentResponse,
    Response,
};
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Stdlib\ResponseInterface;
use User\Service\AclService;

class ApiController extends AbstractActionController
{
    public function __construct(
        private readonly AclService $aclService,
        private readonly MemberInfoService $memberInfoService,
    ) {
    }

    public function validateAction(): Response|ResponseInterface
    {
        if ($this->aclService->hasIdentity()) {
            $identity = $this->aclService->getIdentity();
            /** @var EnvironmentResponse $response */
            $response = $this->getResponse();

            $response->setStatusCode(200);
            $headers = $response->getHeaders();
            $headers->addHeaderLine('GEWIS-MemberID', (string) $identity->getLidnr());

            if (null != $identity->getMember()) {
                $member = $identity->getMember();
                $name = $member->getFullName();
                $headers->addHeaderLine('GEWIS-MemberName', $name);
                $headers->addHeaderLine('GEWIS-MemberEmail', $member->getEmail());
                $memberships = $this->memberInfoService->getOrganMemberships($member);
                $headers->addHeaderLine('GEWIS-MemberGroups', implode(',', array_keys($memberships)));

                return $response;
            }
            $headers->addHeaderLine('GEWIS-MemberName', '');

            return $response;
        }

        $response = $this->getResponse();
        $response->setStatusCode(401);

        return $response;
    }
}
