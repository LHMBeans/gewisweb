<?php

namespace Company\Controller\Factory;

use Company\Controller\AdminController;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class AdminControllerFactory implements FactoryInterface
{
    /**
     * @param ContainerInterface $container
     * @param string $requestedName
     * @param null|array $options
     *
     * @return AdminController
     */
    public function __invoke(
        ContainerInterface $container,
        $requestedName,
        array $options = null
    ): AdminController {
        return new AdminController(
            $container->get('company_service_company'),
            $container->get('company_service_companyquery'),
            $container->get('company_mapper_label'),
            $container->get('company_admin_edit_company_form'),
            $container->get('application_get_languages'),
            $container->get('company_service_acl'),
            $container->get('translator'),
        );
    }
}