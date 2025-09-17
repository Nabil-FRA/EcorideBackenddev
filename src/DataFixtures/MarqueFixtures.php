<?php
// src/DataFixtures/MarqueFixtures.php
namespace App\DataFixtures;

use App\Entity\Marque;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class MarqueFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $marques = ['Peugeot', 'Renault', 'Tesla', 'BMW', 'Mercedes'];
        foreach ($marques as $i => $nomMarque) {
            $marque = new Marque();
            $marque->setLibelle($nomMarque);
            $manager->persist($marque);
            $this->addReference('marque-' . $i, $marque);
        }
        $manager->flush();
    }
}