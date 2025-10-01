<?php
// src/Controller/EmployeApiController.php

namespace App\Controller;

use App\Repository\AvisRepository;
use App\Repository\CovoiturageRepository; // Important de l'ajouter
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/employe')]
#[IsGranted('ROLE_EMPLOYE')]
class EmployeApiController extends AbstractController
{
    #[Route('/dashboard-data', name: 'api_employe_dashboard_data', methods: ['GET'])]
    public function getDashboardData(AvisRepository $avisRepo, CovoiturageRepository $covoiturageRepo): JsonResponse
    {
        $avisEnAttente = $avisRepo->findBy(['statut' => 'en_attente']);
        $dashboardData = [];

        foreach ($avisEnAttente as $avis) {
            $covoiturage = null;
            // On extrait le contexte de l'ID du covoiturage
            if (preg_match('//', $avis->getCommentaire(), $matches)) {
                $context = json_decode($matches[1], true);
                if (isset($context['covoiturage_id'])) {
                    $covoiturage = $covoiturageRepo->find($context['covoiturage_id']);
                }
            }
            
            // On nettoie le commentaire pour ne pas afficher la balise
            $cleanCommentaire = preg_replace('//', '', $avis->getCommentaire());
            $avis->setCommentaire($cleanCommentaire);

            // On ajoute l'avis et le covoiturage trouvé au tableau de données
            $dashboardData[] = [
                'avis' => $avis,
                'covoiturage' => $covoiturage,
            ];
        }

        return $this->json($dashboardData, 200, [], ['groups' => ['avis:read', 'utilisateur:read', 'covoiturage:read']]);
    }

   /**
     * Approuve un avis.
     */
    #[Route('/avis/{id}/approuver', name: 'api_employe_avis_approuver', methods: ['POST'])]
    public function approuverAvis(Avis $avis, EntityManagerInterface $em): JsonResponse
    {
        $avis->setStatut('approuve');
        $em->flush();
        return new JsonResponse(['status' => 'ok', 'message' => 'Avis approuvé.']);
    }

    /**
     * Rejette un avis.
     */
    #[Route('/avis/{id}/rejeter', name: 'api_employe_avis_rejeter', methods: ['POST'])]
    public function rejeterAvis(Avis $avis, EntityManagerInterface $em): JsonResponse
    {
        $avis->setStatut('rejete');
        $em->flush();
        return new JsonResponse(['status' => 'ok', 'message' => 'Avis rejeté.']);
    }
}