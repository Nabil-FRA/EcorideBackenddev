<?php

namespace App\Repository;

use App\Entity\Covoiturage;
use App\Entity\Utilisateur; // N'oubliez pas d'ajouter cette ligne
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Covoiturage>
 */
class CovoiturageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Covoiturage::class);
    }
    
    public function findAvailable(string $depart, string $arrivee, string $date)
    {
        return $this->createQueryBuilder('c')
            ->where('c.lieuDepart = :depart')  
            ->andWhere('c.lieuArrivee = :arrivee')  
            ->andWhere('c.dateDepart = :date')
            ->andWhere('c.nbPlace > 0')
            ->setParameter('depart', $depart)
            ->setParameter('arrivee', $arrivee)
            ->setParameter('date', new \DateTime($date))
            ->getQuery()
            ->getResult();
    }

    public function findNextAvailableCovoiturage(string $depart, string $arrivee, \DateTimeInterface $date): ?Covoiturage
    {
        return $this->createQueryBuilder('c')
            ->where('c.lieuDepart = :depart')
            ->andWhere('c.lieuArrivee = :arrivee')
            // Recherche d'un covoiturage dont la date de départ est après la date spécifiée
            ->andWhere('c.dateDepart > :date')
            ->andWhere('c.nbPlace > 0')
            ->setParameter('depart', $depart)
            ->setParameter('arrivee', $arrivee)
            ->setParameter('date', $date)
            // Trie par date de départ ascendante pour obtenir le plus proche dans le futur
            ->orderBy('c.dateDepart', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve un covoiturage avec toutes ses relations chargées.
     */
    public function findWithRelations(int $id): ?Covoiturage
    {
        return $this->createQueryBuilder('c')
            // Charger la participation (conducteur)
            ->leftJoin('c.participes', 'p')->addSelect('p')
            ->leftJoin('p.utilisateur', 'u')->addSelect('u')

            // Charger la voiture utilisée pour le covoiturage
            ->leftJoin('c.utilise', 'us')->addSelect('us')  
            ->leftJoin('us.voiture', 'v')->addSelect('v')

            // Charger la marque du véhicule via Detient
            ->leftJoin('v.detient', 'det')->addSelect('det')
            ->leftJoin('det.marque', 'm')->addSelect('m')

            // Charger les avis du chauffeur
            ->leftJoin('u.deposes', 'dep')->addSelect('dep')  
            ->leftJoin('dep.avis', 'a')->addSelect('a')

            // Charger les préférences du chauffeur
            ->leftJoin('u.parametresUtilisateurs', 'pu')->addSelect('pu')  
            ->leftJoin('pu.configuration', 'conf')->addSelect('conf')
            ->leftJoin('conf.disposes', 'disp')->addSelect('disp')  
            ->leftJoin('disp.parametre', 'param')->addSelect('param')  

            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByFilters(?bool $ecologique, ?float $prixMax, ?int $dureeMax, ?float $noteMin)
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.participes', 'p')
            ->leftJoin('p.utilisateur', 'u')
            ->leftJoin('c.utilise', 'ut')  // Alias unique pour éviter le conflit
            ->leftJoin('ut.voiture', 'v')  // Relation correcte avec Voiture
            ->leftJoin('u.deposes', 'd')
            ->leftJoin('d.avis', 'a')
            ->groupBy('c.id');
    
        if ($ecologique) {
            $qb->andWhere('v.energie = :energie')
            ->setParameter('energie', 'Électrique');
        }
    
        if ($prixMax !== null) {
            $qb->andWhere('c.prixPersonne <= :prixMax')
                ->setParameter('prixMax', $prixMax);
        }
    
        if ($noteMin !== null) {
            $qb->having('AVG(a.note) >= :noteMin')
                ->setParameter('noteMin', $noteMin);
        }
    
        $results = $qb->getQuery()->getResult();
    
        // **Filtrage en PHP pour la durée**
        if ($dureeMax !== null) {
            $results = array_filter($results, function ($covoiturage) use ($dureeMax) {
                $dateDepart = $covoiturage->getDateDepart()->format('Y-m-d') . ' ' . $covoiturage->getHeureDepart()->format('H:i:s');
                $dateArrivee = $covoiturage->getDateArrivee()->format('Y-m-d') . ' ' . $covoiturage->getHeureArrivee()->format('H:i:s');
    
                $diff = (strtotime($dateArrivee) - strtotime($dateDepart)) / 60; // Différence en minutes
                
                return $diff <= $dureeMax;
            });
    
            // Convertir `array_filter()` en tableau indexé
            $results = array_values($results);
        }
    
        return $results;
    }
    
    // ===================================================================
    // MÉTHODE AJOUTÉE (requise par CovoiturageController)
    // ===================================================================
    
    /**
     * Trouve les covoiturages à venir (statut disponible/confirmé) pour un utilisateur donné.
     * @return Covoiturage[]
     */
    public function findUpcomingForUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participes', 'p')
            ->where('p.utilisateur = :user')
            ->andWhere("c.statut IN ('disponible', 'confirmé')")
            ->andWhere('c.dateDepart >= :today')
            ->setParameter('user', $user)
            ->setParameter('today', new \DateTime())
            ->orderBy('c.dateDepart', 'ASC')
            ->getQuery()
            ->getResult();
    }
}