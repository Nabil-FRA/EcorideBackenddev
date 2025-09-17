<?php
// src/DataFixtures/VoitureFixtures.php
namespace App\DataFixtures;

use App\Entity\Detient;
use App\Entity\Gere;
use App\Entity\Voiture;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class VoitureFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        // Créer 5 voitures
        for ($i = 0; $i < 5; $i++) {
            $voiture = new Voiture();
            $voiture->setModele($faker->randomElement(['208', 'Clio', 'Model 3', 'Serie 3', 'Classe C']));
            $voiture->setImmatriculation($faker->regexify('[A-Z]{2}-\d{3}-[A-Z]{2}'));
            $voiture->setEnergie($faker->randomElement(['Essence', 'Diesel', 'Electrique']));
            $voiture->setCouleur($faker->safeColorName());
            
            // Lier à une marque via Detient
            $detient = new Detient();
            $detient->setVoiture($voiture);
            $detient->setMarque($this->getReference('marque-' . $faker->numberBetween(0, 4)));
            $manager->persist($detient);

            // Lier à un utilisateur via Gere
            $gere = new Gere();
            $gere->setVoiture($voiture);
            $gere->setUtilisateur($this->getReference('user-' . $faker->numberBetween(0, 9)));
            $manager->persist($gere);
            
            $manager->persist($voiture);
            $this->addReference('voiture-' . $i, $voiture);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            MarqueFixtures::class,
            UserFixtures::class,
        ];
    }
}