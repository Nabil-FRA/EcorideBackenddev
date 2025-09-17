<?php
namespace App\DataFixtures;
use App\Entity\Role;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class RoleFixtures extends Fixture
{
    public const ADMIN_ROLE_REFERENCE = 'role-admin';
    public const USER_ROLE_REFERENCE = 'role-user';

    public function load(ObjectManager $manager): void
    {
        $roleAdmin = new Role();
        $roleAdmin->setLibelle('ROLE_ADMIN');
        $manager->persist($roleAdmin);
        $this->addReference(self::ADMIN_ROLE_REFERENCE, $roleAdmin);

        $roleUser = new Role();
        $roleUser->setLibelle('ROLE_USER');
        $manager->persist($roleUser);
        $this->addReference(self::USER_ROLE_REFERENCE, $roleUser);

        $manager->flush();
    }
}
