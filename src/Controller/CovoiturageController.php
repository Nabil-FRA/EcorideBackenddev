<?php

namespace App\Controller;

use App\Entity\Covoiturage;
use App\Entity\Utilisateur;
use App\Entity\Participe;
use App\Entity\Gere;
use App\Entity\Utilise;
use App\Repository\CovoiturageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use OpenApi\Attributes as OA;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

#[Route('/api/covoiturage')]
#[OA\Tag(name: 'Covoiturage')]
class CovoiturageController extends AbstractController
{
    /**
     * Liste tous les covoiturages.
     */
    #[OA\Response(
        response: 200,
        description: "Retourne la liste de tous les covoiturages.",
        content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Covoiturage'))
    )]
    #[Route(name: 'app_covoiturage_index', methods: ['GET'])]
    public function index(CovoiturageRepository $covoiturageRepository): JsonResponse
    {
        $covoiturages = $covoiturageRepository->findAll();
        return $this->json($covoiturages, JsonResponse::HTTP_OK, [], ['groups' => 'covoiturage:read']);
    }

    /**
     * Recherche des covoiturages disponibles.
     */
    #[OA\Parameter(name: 'depart', in: 'query', description: "Lieu de départ", required: true)]
    #[OA\Parameter(name: 'arrivee', in: 'query', description: "Lieu d'arrivée", required: true)]
    #[OA\Parameter(name: 'date', in: 'query', description: "Date du voyage (YYYY-MM-DD)", required: true)]
    #[OA\Response(response: 200, description: "Retourne les covoiturages correspondants.")]
    #[OA\Response(response: 400, description: "Paramètres manquants ou format de date invalide.")]
    #[OA\Response(response: 404, description: "Aucun covoiturage disponible.")]
    #[Route('/search', name: 'app_covoiturage_search', methods: ['GET'])]
    public function search(Request $request, CovoiturageRepository $covoiturageRepository, SessionInterface $session): JsonResponse
    {
        try {
            $depart = $request->query->get('depart');
            $arrivee = $request->query->get('arrivee');
            $date = $request->query->get('date');

            if (!$depart || !$arrivee || !$date) {
                return new JsonResponse(['message' => 'Paramètres manquants'], JsonResponse::HTTP_BAD_REQUEST);
            }
            $dateObj = new \DateTime($date);

            $covoiturages = $covoiturageRepository->findAvailable($depart, $arrivee, $date);

            if (empty($covoiturages)) {
                $prochainCovoiturage = $covoiturageRepository->findNextAvailableCovoiturage($depart, $arrivee, $dateObj);
                return new JsonResponse([
                    'message' => 'Aucun covoiturage disponible à cette date.',
                    'prochain_covoiturage' => $prochainCovoiturage ? $prochainCovoiturage->getDateDepart()->format('Y-m-d') : null
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $results = array_map(function (Covoiturage $covoiturage) {
                $chauffeur = $covoiturage->getParticipes()->first()?->getUtilisateur();
                if (!$chauffeur) return null;
                return [
                    'id' => $covoiturage->getId(),
                    'chauffeur' => [
                        'pseudo' => $chauffeur->getPseudo(),
                        'photo' => $this->getBase64Photo($chauffeur->getPhoto()),
                        'note' => $this->getChauffeurNoteMoyenne($chauffeur)
                    ],
                    'placesRestantes' => $covoiturage->getNbPlace(),
                    'prix' => $covoiturage->getPrixPersonne(),
                    'dateDepart' => $covoiturage->getDateDepart()->format('Y-m-d'),
                    'heureDepart' => $covoiturage->getHeureDepart()->format('H:i'),
                    'dateArrivee' => $covoiturage->getDateArrivee()->format('Y-m-d'),
                    'heureArrivee' => $covoiturage->getHeureArrivee()->format('H:i'),
                    'ecologique' => $this->isVoyageEcologique($covoiturage)
                ];
            }, $covoiturages);

            $session->set('search_results', $results);
            return new JsonResponse(array_filter($results), JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Erreur interne : ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function getBase64Photo($photo): ?string
    {
        return is_resource($photo) ? base64_encode(stream_get_contents($photo)) : null;
    }

    private function getChauffeurNoteMoyenne($chauffeur): float
    {
        $totalAvis = 0;
        $totalNotes = 0;
        foreach ($chauffeur?->getDeposes() ?? [] as $depose) {
            foreach ($depose->getAvis() as $avis) {
                $totalNotes += floatval($avis->getNote());
                $totalAvis++;
            }
        }
        return $totalAvis > 0 ? round($totalNotes / $totalAvis, 1) : 0;
    }

    private function isVoyageEcologique(Covoiturage $covoiturage): bool
    {
        $energiesEcologiques = ['électrique', 'hybride', 'hydrogène'];
        foreach ($covoiturage->getUtilise() as $utilisation) {
            $voiture = $utilisation->getVoiture();
            if ($voiture && in_array(strtolower($voiture->getEnergie()), $energiesEcologiques, true)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Récupère les détails complets d'un covoiturage.
     */
    #[Route('/details/{id}', name: 'app_covoiturage_detail', methods: ['GET'])]
    public function getCovoiturageDetail(int $id, CovoiturageRepository $covoiturageRepository, SerializerInterface $serializer): JsonResponse
    {
        $covoiturage = $covoiturageRepository->findWithRelations($id);
        if (!$covoiturage) {
            return new JsonResponse(['message' => 'Covoiturage non trouvé'], JsonResponse::HTTP_NOT_FOUND);
        }

        $chauffeur = $covoiturage->getParticipes()->first()?->getUtilisateur();
        $voiture = $covoiturage->getUtilise()->first()?->getVoiture();
        $marque = $voiture?->getDetient()?->getMarque();

        $preferences = [];
        foreach ($chauffeur?->getParametresUtilisateurs() ?? [] as $parametreUtilisateur) {
            foreach ($parametreUtilisateur->getConfiguration()?->getDisposes() ?? [] as $dispose) {
                $parametre = $dispose->getParametre();
                if ($parametre) {
                    $preferences[] = ['propriete' => $parametre->getPropriete(), 'valeur' => $parametre->getValeur()];
                }
            }
        }

        $avisList = [];
        foreach ($chauffeur?->getDeposes() ?? [] as $depose) {
            foreach ($depose->getAvis() as $avis) {
                $avisList[] = ['note' => $avis->getNote(), 'commentaire' => $avis->getCommentaire()];
            }
        }

        $response = [
            'id' => $covoiturage->getId(),
            'chauffeur' => $chauffeur ? ['pseudo' => $chauffeur->getPseudo(), 'photo' => $this->getBase64Photo($chauffeur->getPhoto()), 'note' => $this->getChauffeurNoteMoyenne($chauffeur)] : null,
            'vehicule' => $voiture ? ['marque' => $marque?->getLibelle(), 'modele' => $voiture->getModele(), 'energie' => $voiture->getEnergie()] : null,
            'preferences' => $preferences,
            'avis' => $avisList,
            'placesRestantes' => $covoiturage->getNbPlace(),
            'prix' => $covoiturage->getPrixPersonne(),
            'dateDepart' => $covoiturage->getDateDepart()->format('Y-m-d'),
            'heureDepart' => $covoiturage->getHeureDepart()->format('H:i'),
            'dateArrivee' => $covoiturage->getDateArrivee()->format('Y-m-d'),
            'heureArrivee' => $covoiturage->getHeureArrivee()->format('H:i'),
        ];

        return new JsonResponse($serializer->normalize($response, null, ['groups' => ['avis:read']]), JsonResponse::HTTP_OK);
    }

    /**
     * Crée un nouveau covoiturage.
     */
    #[Route('/creer', name: 'covoiturage_creer', methods: ['POST'])]
    public function creerCovoiturage(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user instanceof Utilisateur || !$user->isChauffeur()) {
            return new JsonResponse(['message' => 'Accès refusé : vous devez être un chauffeur.'], Response::HTTP_FORBIDDEN);
        }
        if ($user->getCredits() < 2) {
            return new JsonResponse(['message' => 'Crédits insuffisants pour créer un covoiturage.'], Response::HTTP_PAYMENT_REQUIRED);
        }
        if (empty($entityManager->getRepository(Gere::class)->findBy(['utilisateur' => $user]))) {
            return new JsonResponse(['message' => 'Vous devez enregistrer une voiture avant de créer un covoiturage.'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $voitureId = $data['voitureId'] ?? null;
        $voiture = $voitureId ? $entityManager->getRepository(Voiture::class)->find($voitureId) : null;
        
        $gere = $voiture ? $entityManager->getRepository(Gere::class)->findOneBy(['voiture' => $voiture, 'utilisateur' => $user]) : null;
        if (!$voiture || !$gere) {
             return new JsonResponse(['message' => 'Cette voiture ne vous appartient pas.'], Response::HTTP_FORBIDDEN);
        }

        $covoiturage = new Covoiturage();
        $covoiturage->setLieuDepart($data['lieuDepart'])
            ->setLieuArrivee($data['lieuArrivee'])
            ->setPrixPersonne($data['prixPersonne'])
            ->setDateDepart(new \DateTime($data['dateDepart']))
            ->setHeureDepart(new \DateTime($data['heureDepart']))
            ->setDateArrivee(new \DateTime($data['dateArrivee']))
            ->setHeureArrivee(new \DateTime($data['heureArrivee']))
            ->setNbPlace($data['nbPlace'])
            ->setStatut($data['statut']);

        $participe = new Participe();
        $participe->setUtilisateur($user);
        $participe->setCovoiturage($covoiturage);
        
        $utilise = new Utilise();
        $utilise->setCovoiturage($covoiturage);
        $utilise->setVoiture($voiture);

        $user->setCredits($user->getCredits() - 2);

        $entityManager->persist($covoiturage);
        $entityManager->persist($participe);
        $entityManager->persist($utilise);
        $entityManager->flush();

        return new JsonResponse(['message' => 'Covoiturage créé avec succès'], Response::HTTP_CREATED);
    }

    /**
     * Filtre les résultats d'une recherche précédente.
     */
    #[Route('/filter', name: 'app_covoiturage_filter', methods: ['GET'])]
    public function filter(Request $request, SessionInterface $session): JsonResponse
    {
        try {
            $results = $session->get('search_results', []);
            if (empty($results)) {
                return new JsonResponse(['message' => 'Aucun résultat de recherche en session. Veuillez d\'abord appeler /search.'], JsonResponse::HTTP_BAD_REQUEST);
            }

            $ecologique = $request->query->get('ecologique') === '1';
            $prixMax = $request->query->get('prixMax') !== null ? (float) $request->query->get('prixMax') : null;
            $dureeMax = $request->query->get('dureeMax') !== null ? (int) $request->query->get('dureeMax') : null;
            $noteMin = $request->query->get('noteMin') !== null ? (float) $request->query->get('noteMin') : null;

            $filtered = array_filter($results, function ($cov) use ($ecologique, $prixMax, $dureeMax, $noteMin) {
                if ($ecologique && empty($cov['ecologique'])) return false;
                if ($prixMax !== null && isset($cov['prix']) && $cov['prix'] > $prixMax) return false;
                if ($dureeMax !== null) {
                    $diffMinutes = (strtotime($cov['dateArrivee'] . ' ' . $cov['heureArrivee']) - strtotime($cov['dateDepart'] . ' ' . $cov['heureDepart'])) / 60;
                    if ($diffMinutes > $dureeMax) return false;
                }
                if ($noteMin !== null && ($cov['chauffeur']['note'] ?? 0) < $noteMin) return false;
                return true;
            });

            return new JsonResponse(array_values($filtered), JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Erreur interne : ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Récupère les covoiturages (passés et à venir) pour l'utilisateur connecté.
     */
    #[Route('/mes-covoiturages', name: 'app_covoiturage_mes_covoiturages', methods: ['GET'])]
    public function mesCovoiturages(CovoiturageRepository $repo, EntityManagerInterface $entityManager): JsonResponse
    {
        /** @var \App\Entity\Utilisateur $userFromToken */
        $userFromToken = $this->getUser();
        if (!$userFromToken) {
            return new JsonResponse(['message' => 'Utilisateur non connecté.'], Response::HTTP_UNAUTHORIZED);
        }

        // CORRECTION : On recharge l'utilisateur complet depuis la base de données
        // pour s'assurer que Doctrine peut correctement utiliser ses relations.
        $user = $entityManager->getRepository(Utilisateur::class)->find($userFromToken->getId());
        if (!$user) {
            // Sécurité supplémentaire si l'utilisateur du token n'existe plus en BDD
            return new JsonResponse(['message' => 'Utilisateur non trouvé.'], Response::HTTP_UNAUTHORIZED);
        }

        // On utilise maintenant l'utilisateur complet et "géré" pour la requête
        $covoiturages = $repo->findAllForUser($user);

        return $this->json($covoiturages, 200, [], ['groups' => 'covoiturage:read']);
    }

    /**
     * Annule la participation à un covoiturage (passager) ou le covoiturage entier (chauffeur).
     */
    #[Route('/{id}/annuler', name: 'app_covoiturage_annuler', methods: ['DELETE'])]
    public function annuler(Covoiturage $covoiturage, EntityManagerInterface $em, MailerInterface $mailer): JsonResponse
    {
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non connecté.'], Response::HTTP_UNAUTHORIZED);
        }

        if (!in_array($covoiturage->getStatut(), ['disponible', 'confirmé', 'complet'])) {
            return new JsonResponse(['message' => 'Ce covoiturage ne peut plus être annulé.'], Response::HTTP_BAD_REQUEST);
        }

        $chauffeur = $covoiturage->getChauffeur();

        if ($chauffeur === $user) {
            $participations = $em->getRepository(Participe::class)->findBy(['covoiturage' => $covoiturage]);

            foreach ($participations as $participation) {
                $passager = $participation->getUtilisateur();
                if ($passager !== $user) {
                    $passager->setCredits($passager->getCredits() + $covoiturage->getPrixPersonne());

                    $email = (new Email())
                        ->from('support@ecoride.fr')
                        ->to($passager->getEmail())
                        ->subject('Annulation de votre covoiturage EcoRide')
                        ->html("<p>Bonjour {$passager->getPseudo()},</p><p>Le covoiturage de <strong>{$covoiturage->getLieuDepart()}</strong> à <strong>{$covoiturage->getLieuArrivee()}</strong> prévu le {$covoiturage->getDateDepart()->format('d/m/Y')} a été annulé par le chauffeur. Vous avez été intégralement remboursé.</p>");
                    
                    $mailer->send($email);
                    
                    $em->remove($participation);
                }
            }
            
            $participationChauffeur = $em->getRepository(Participe::class)->findOneBy(['covoiturage' => $covoiturage, 'utilisateur' => $user]);
            if ($participationChauffeur) {
                $em->remove($participationChauffeur);
            }
            
            $covoiturage->setStatut('annulé');
            $user->setCredits($user->getCredits() + 2); 

            $em->flush();

            return $this->json([
                'message' => 'Covoiturage annulé. Les passagers ont été notifiés et remboursés.',
                'covoiturage' => $covoiturage
            ], 200, [], ['groups' => 'covoiturage:read']);
        }
        
        $participationPassager = $em->getRepository(Participe::class)->findOneBy(['covoiturage' => $covoiturage, 'utilisateur' => $user]);

        if ($participationPassager) {
            $user->setCredits($user->getCredits() + $covoiturage->getPrixPersonne());
            $covoiturage->setNbPlace($covoiturage->getNbPlace() + 1);
            $em->remove($participationPassager);
            
            $em->flush();

            return new JsonResponse(['message' => 'Votre participation a été annulée et vos crédits ont été remboursés.'], Response::HTTP_OK);
        }

        return new JsonResponse(['message' => 'Action non autorisée.'], Response::HTTP_FORBIDDEN);
    }
}