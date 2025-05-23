<?php

declare(strict_types=1);

namespace spec\Odiseo\SyliusRbacPlugin\CommandHandler;

use Doctrine\Persistence\ObjectManager;
use Odiseo\SyliusRbacPlugin\Message\CreateAdministrationRole;
use PhpSpec\ObjectBehavior;
use Odiseo\SyliusRbacPlugin\Access\Model\OperationType;
use Odiseo\SyliusRbacPlugin\Entity\AdministrationRoleInterface;
use Odiseo\SyliusRbacPlugin\Factory\AdministrationRoleFactoryInterface;
use Odiseo\SyliusRbacPlugin\Model\Permission;
use Odiseo\SyliusRbacPlugin\Model\PermissionInterface;
use Odiseo\SyliusRbacPlugin\Validator\AdministrationRoleValidatorInterface;

final class CreateAdministrationRoleHandlerSpec extends ObjectBehavior
{
    function let(
        ObjectManager $administrationRoleManager,
        AdministrationRoleFactoryInterface $administrationRoleFactory,
        AdministrationRoleValidatorInterface $administrationRoleValidator
    ): void {
        $this->beConstructedWith(
            $administrationRoleManager,
            $administrationRoleFactory,
            $administrationRoleValidator,
            'sylius_rbac_admin_administration_role_create'
        );
    }

    function it_handles_command_and_persists_new_administration_role(
        ObjectManager $administrationRoleManager,
        AdministrationRoleFactoryInterface $administrationRoleFactory,
        AdministrationRoleInterface $administrationRole,
        AdministrationRoleValidatorInterface $administrationRoleValidator,
        PermissionInterface $catalogManagementPermission,
        PermissionInterface $configurationPermission
    ): void {
        $catalogManagementPermission->type()->willReturn(Permission::CATALOG_MANAGEMENT_PERMISSION);
        $catalogManagementPermission->operationTypes()->willReturn([OperationType::READ]);

        $configurationPermission->type()->willReturn(Permission::CONFIGURATION_PERMISSION);
        $configurationPermission->operationTypes()->willReturn([OperationType::READ]);

        $administrationRole->getName()->willReturn('Product Manager');
        $administrationRole->getPermissions()->willReturn([$catalogManagementPermission, $configurationPermission]);

        $administrationRoleFactory
            ->createWithNameAndPermissions('Product Manager',
                [
                    'catalog_management' => [OperationType::READ],
                    'configuration' => [OperationType::READ],
                ]
            )->willReturn($administrationRole)
        ;

        $administrationRoleValidator
            ->validate($administrationRole, 'sylius_rbac_admin_administration_role_create')
            ->shouldBeCalled()
        ;

        $administrationRoleManager->persist($administrationRole)->shouldBeCalled();
        $administrationRoleManager->flush()->shouldBeCalled();

        $this->__invoke(new CreateAdministrationRole(
            'Product Manager',
            [
                'catalog_management' => [OperationType::READ],
                'configuration' => [OperationType::READ],
            ]
        ));
    }

    function it_propagates_an_exception_when_administration_role_is_not_valid(
        AdministrationRoleInterface $administrationRole,
        AdministrationRoleFactoryInterface $administrationRoleFactory,
        AdministrationRoleValidatorInterface $administrationRoleValidator
    ): void {
        $command = new CreateAdministrationRole(
            'Product Manager',
            [
                'catalog_management' => [OperationType::READ],
                'configuration' => [OperationType::READ],
            ]
        );

        $administrationRoleFactory
            ->createWithNameAndPermissions(
                'Product Manager',
                [
                    'catalog_management' => [OperationType::READ],
                    'configuration' => [OperationType::READ],
                ]
        )->willReturn($administrationRole);

        $administrationRoleValidator
            ->validate($administrationRole, 'sylius_rbac_admin_administration_role_create')
            ->willThrow(new \InvalidArgumentException())
        ;

        $this->shouldThrow(\InvalidArgumentException::class)->during('__invoke', [$command]);
    }
}
