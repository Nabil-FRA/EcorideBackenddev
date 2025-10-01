<?php

namespace App\Controller;

use App\Service\MongoDBService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Covoiturage;
use App\Entity\Participe;




class CovoiturageHistoryController extends AbstractController
{
    #[Route('/mongo/historique_covoiturage', name: 'insert_covoiturage_history')]
    public function insertHistory(EntityManagerInterface $em, MongoDBService $mongoDBService): Response
    {
        $db = $mongoDBService->getDatabase();
        $collection = $db->selectCollection('reservations');

        $covoiturages = $em->getRepository(Covoiturage::class)->findBy(['statut' => 'terminé']);

        foreach ($covoiturages as $covoiturage) {
            $existing = $collection->findOne(['covoiturage_id' => $covoiturage->getId()]);
            if ($existing) {
                continue;
            }

            $participants = $em->getRepository(Participe::class)->findBy(['covoiturage' => $covoiturage]);
            $passengers = [];
            foreach ($participants as $participant) {
                $user = $participant->getUtilisateur();
                $role = $user->isChauffeur() ? 'chauffeur' : ($user->isPassager() ? 'passager' : 'inconnu');
                $passengers[] = [
                    'name' => $user->getNom() . ' ' . $user->getPrenom(),
                    'role' => $role
                ];
            }

            $document = [
                'covoiturage_id' => $covoiturage->getId(),
                'participants' => $passengers,
                'departure' => $covoiturage->getLieuDepart(),
                'destination' => $covoiturage->getLieuArrivee(),
                'date_depart' => $covoiturage->getDateDepart()->format('Y-m-d'),
                'date_arrivee' => $covoiturage->getDateArrivee()->format('Y-m-d'),
                'price' => $covoiturage->getPrixPersonne(),
                'statut' => $covoiturage->getStatut()
            ];

            $collection->insertOne($document);
        }

        $history = $collection->find()->toArray();

        return $this->json($history);
    }
}
