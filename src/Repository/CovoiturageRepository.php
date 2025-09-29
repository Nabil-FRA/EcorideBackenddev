<?php

namespace App\Repository;

use App\Entity\Covoiturage;
use App\Entity\Utilisateur;
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
            ->andWhere('c.dateDepart > :date')
            ->andWhere('c.nbPlace > 0')
            ->setParameter('depart', $depart)
            ->setParameter('arrivee', $arrivee)
            ->setParameter('date', $date)
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
            ->leftJoin('c.participes', 'p')->addSelect('p')
            ->leftJoin('p.utilisateur', 'u')->addSelect('u')
            ->leftJoin('c.utilise', 'us')->addSelect('us')  
            ->leftJoin('us.voiture', 'v')->addSelect('v')
            ->leftJoin('v.detient', 'det')->addSelect('det')
            ->leftJoin('det.marque', 'm')->addSelect('m')
            ->leftJoin('u.deposes', 'dep')->addSelect('dep')  
            ->leftJoin('dep.avis', 'a')->addSelect('a')
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
            ->leftJoin('c.utilise', 'ut')
            ->leftJoin('ut.voiture', 'v')
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
    
        if ($dureeMax !== null) {
            $results = array_filter($results, function ($covoiturage) use ($dureeMax) {
                $dateDepart = $covoiturage->getDateDepart()->format('Y-m-d') . ' ' . $covoiturage->getHeureDepart()->format('H:i:s');
                $dateArrivee = $covoiturage->getDateArrivee()->format('Y-m-d') . ' ' . $covoiturage->getHeureArrivee()->format('H:i:s');
    
                $diff = (strtotime($dateArrivee) - strtotime($dateDepart)) / 60;
                
                return $diff <= $dureeMax;
            });
    
            $results = array_values($results);
        }
    
        return $results;
    }
    
    /**
     * Trouve les covoiturages à venir pour un utilisateur donné.
     * @return Covoiturage[]
     */
    public function findUpcomingForUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participes', 'p')
            ->where('p.utilisateur = :user')
            ->andWhere("c.statut IN ('disponible', 'confirmé', 'complet')")
            ->andWhere('c.dateDepart >= :today')
            ->setParameter('user', $user)
            ->setParameter('today', new \DateTime())
            ->orderBy('c.dateDepart', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // === MÉTHODE AJOUTÉE POUR CORRIGER LE PROBLÈME ===
    /**
     * Récupère tous les covoiturages (passés et futurs) auxquels un utilisateur participe.
     * @return Covoiturage[]
     */
    public function findAllForUser(Utilisateur $user): array
    {
        return $this->createQueryBuilder('c')
            ->innerJoin('c.participes', 'p')
            ->where('p.utilisateur = :user')
            ->setParameter('user', $user)
            ->orderBy('c.dateDepart', 'DESC')
            ->getQuery()
            ->getResult();
    }
}