<?php

namespace App\Controller;

use App\Entity\Covoiturage;
use App\Entity\Utilisateur;
use App\Entity\Participe;
use App\Entity\ParametreUtilisateur;
use App\Entity\Dispose;
use App\Entity\Depose;
use App\Entity\Voiture;
use App\Entity\Configuration;
use App\Entity\Detient;
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




#[Route('/api/covoiturage')]
class CovoiturageController extends AbstractController
{
    /**
     * Liste tous les covoiturages.
     */
    #[Route(name: 'app_covoiturage_index', methods: ['GET'])]
    public function index(CovoiturageRepository $covoiturageRepository): JsonResponse
    {
        $covoiturages = $covoiturageRepository->findAll();
        return $this->json($covoiturages, JsonResponse::HTTP_OK, [], ['groups' => 'covoiturage:read']);
    }

    #[Route('/search', name: 'app_covoiturage_search', methods: ['GET'])]
public function search(Request $request, CovoiturageRepository $covoiturageRepository, SessionInterface $session): JsonResponse
{ $session->set('test_session', 'ok');
    $sessionId = session_id();
    ##dump("SESSION ID:", $sessionId, "COOKIE:", $_COOKIE, "SESSION DATA:", $session->all());
    try {
        $depart = $request->query->get('depart');
        $arrivee = $request->query->get('arrivee');
        $date = $request->query->get('date');

        if (!$depart || !$arrivee || !$date) {
            return new JsonResponse(['message' => 'Paramètres manquants'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérifier que la date est valide
        try {
            $dateObj = new \DateTime($date);
        } catch (\Exception $e) {
            return new JsonResponse(['message' => 'Format de date invalide'], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Récupération des covoiturages disponibles
        $covoiturages = $covoiturageRepository->findAvailable($depart, $arrivee, $date);


        // S'il n'y a aucun covoiturage, proposer la prochaine date disponible
        if (empty($covoiturages)) {
            $prochainCovoiturage = $covoiturageRepository->findNextAvailableCovoiturage($depart, $arrivee, $dateObj);

            return new JsonResponse([
                'message' => 'Aucun covoiturage disponible à cette date.',
                'prochain_covoiturage' => $prochainCovoiturage ? $prochainCovoiturage->getDateDepart()->format('Y-m-d') : null
            ], JsonResponse::HTTP_NOT_FOUND);
        }

        // Transformation des résultats
        $results = array_map(function (Covoiturage $covoiturage) {
            $id = $covoiturage->getId();
            if (!is_numeric($id)) {
                return null; // Évite les erreurs si l'ID n'est pas un nombre
            }
        
            $participes = $covoiturage->getParticipes(); // Supposons que cette méthode retourne une collection
            $participe = $participes->first(); // Récupère le premier élément de la collection
            if (!$participe) return null;
        
            $chauffeur = $participe->getUtilisateur();
            if (!$chauffeur) return null;
        
            return [
                'id' => (int) $id, // Conversion explicite en entier
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
        $session->save();

        return new JsonResponse(array_filter($results), JsonResponse::HTTP_OK);
    } catch (\Exception $e) {
        return new JsonResponse(['message' => 'Erreur interne : ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        ##dump(session_id(), $session->all()); exit;
    }
}

/**
 * Convertit une photo BLOB en Base64.
 */
private function getBase64Photo($photo): ?string
{
    return is_resource($photo) ? base64_encode(stream_get_contents($photo)) : null;
}

/**
 * Récupère la note moyenne du chauffeur.
 */
private function getChauffeurNoteMoyenne($chauffeur): float
{
    $totalAvis = 0;
    $totalNotes = 0;

    foreach ($chauffeur?->getDeposes() ?? [] as $depose)  // ✅ Correction ici (pluriel)
 {
        foreach ($depose->getAvis() as $avis) {
            $totalNotes += floatval($avis->getNote());
            $totalAvis++;
        }
    }

    return $totalAvis > 0 ? round($totalNotes / $totalAvis, 1) : 0;
}

/**
 * Vérifie si un covoiturage est écologique (voiture électrique).
 */
private function isVoyageEcologique(Covoiturage $covoiturage): bool
{
    $energiesEcologiques = ['électrique', 'hybride', 'hydrogène'];

    // Vérifie si un covoiturage est associé à une voiture via Utilise
    foreach ($covoiturage->getUtilise() as $utilisation) {
        $voiture = $utilisation->getVoiture();
        if ($voiture && in_array(strtolower($voiture->getEnergie()), $energiesEcologiques, true)) {
            return true;  // ✅ Covoiturage écologique !
        }
    }

    return false;  // ❌ Aucun véhicule écologique trouvé
}

#[Route('/details/{id}', name: 'app_covoiturage_detail', methods: ['GET'])]
public function getCovoiturageDetail(int $id, CovoiturageRepository $covoiturageRepository, SerializerInterface $serializer): JsonResponse
{
    // ✅ Charger le covoiturage avec toutes ses relations
    $covoiturage = $covoiturageRepository->findWithRelations($id);

    if (!$covoiturage) {
        return new JsonResponse(['message' => 'Covoiturage non trouvé'], JsonResponse::HTTP_NOT_FOUND);
    }

    // 🧑‍✈️ Récupérer le chauffeur
    $chauffeur = $covoiturage->getParticipes()->first()?->getUtilisateur();


    $utilisation = $covoiturage->getUtilise()->first(); 
    $voiture = $utilisation ? $utilisation->getVoiture() : null;
    $marque = $voiture?->getDetient()?->getMarque();

    // 🏷️ Récupérer les préférences du chauffeur (Correction ici)
    $preferences = [];
    foreach ($chauffeur?->getParametresUtilisateurs() ?? [] as $parametreUtilisateur) { 
        foreach ($parametreUtilisateur->getConfiguration()?->getDisposes() ?? [] as $dispose) { 
            $parametre = $dispose->getParametre();
            if ($parametre) {
                $preferences[] = [
                    'propriete' => $parametre->getPropriete(),
                    'valeur' => $parametre->getValeur(),
                ];
            }
        }
    }

    
    $avisList = [];
    foreach ($chauffeur?->getDeposes() ?? [] as $depose) { 
        foreach ($depose->getAvis() as $avis) {
            $avisList[] = [
                'note' => $avis->getNote(),
                'commentaire' => $avis->getCommentaire(),
            ];
        }
    }

    $response = [
        'id' => $covoiturage->getId(),
        'chauffeur' => $chauffeur ? [
            'pseudo' => $chauffeur->getPseudo(),
            'photo' => $this->getBase64Photo($chauffeur->getPhoto()),
            'note' => $this->getChauffeurNoteMoyenne($chauffeur),
        ] : null,
        'vehicule' => $voiture ? [
            'marque' => $marque?->getLibelle(),
            'modele' => $voiture->getModele(),
            'energie' => $voiture->getEnergie(),
        ] : null,
        'preferences' => $preferences,
        'avis' => $avisList,
        'placesRestantes' => $covoiturage->getNbPlace(),
        'prix' => $covoiturage->getPrixPersonne(),
        'dateDepart' => $covoiturage->getDateDepart()->format('Y-m-d'),
        'heureDepart' => $covoiturage->getHeureDepart()->format('H:i'),
        'dateArrivee' => $covoiturage->getDateArrivee()->format('Y-m-d'),
        'heureArrivee' => $covoiturage->getHeureArrivee()->format('H:i'),
    ];

    // Sérialisation de la réponse avec les groupes de sérialisation
    return new JsonResponse(
        $serializer->normalize($response, null, ['groups' => ['avis:read']]), 
        JsonResponse::HTTP_OK
    );
}

#[Route('/creer', name: 'covoiturage_creer', methods: ['POST'])]
public function creerCovoiturage(Request $request, EntityManagerInterface $entityManager): Response
{
    $user = $this->getUser();
    
    if (!$user instanceof Utilisateur || !$user->isChauffeur()) {
        return new JsonResponse(['message' => 'Accès refusé : vous devez être un chauffeur.'], Response::HTTP_FORBIDDEN);
    }
    
    // Vérifier si l'utilisateur a au moins 2 crédits
    if ($user->getCredits() < 2) {
        return new JsonResponse(['message' => 'Crédits insuffisants pour créer un covoiturage.'], Response::HTTP_PAYMENT_REQUIRED);
    }
    
    // Vérifier si le chauffeur possède une voiture
    $detentions = $entityManager->getRepository(Gere::class)->findBy(['utilisateur' => $user]);
    
    $voitures = [];
    foreach ($detentions as $detention) {
        $voitures[] = $detention->getVoiture();
    }
    
    if (empty($voitures)) {
        return new JsonResponse(['message' => 'Vous devez enregistrer une voiture avant de créer un covoiturage.'], Response::HTTP_BAD_REQUEST);
    }
    
    $data = json_decode($request->getContent(), true);
    $lieuDepart   = $data['lieuDepart']   ?? null;
    $lieuArrivee  = $data['lieuArrivee']  ?? null;
    $prixPersonne = $data['prixPersonne'] ?? null;
    $voitureId    = $data['voitureId']    ?? null;
    $dateDepart   = isset($data['dateDepart'])   ? new \DateTime($data['dateDepart'])   : null;
    $heureDepart  = isset($data['heureDepart'])  ? new \DateTime($data['heureDepart'])  : null;
    $dateArrivee  = isset($data['dateArrivee'])  ? new \DateTime($data['dateArrivee'])  : null;
    $heureArrivee = isset($data['heureArrivee']) ? new \DateTime($data['heureArrivee']) : null;
    $nbPlace      = $data['nbPlace']      ?? null;
    $statut       = $data['statut']       ?? null;
    
    if (
        !$lieuDepart || !$lieuArrivee || !$prixPersonne || !$voitureId ||
        !$dateDepart || !$heureDepart || !$dateArrivee || !$heureArrivee || !$nbPlace || !$statut
    ) {
        return new JsonResponse(['message' => 'Données incomplètes.'], Response::HTTP_BAD_REQUEST);
    }
    
    // Vérifier que la voiture appartient bien au chauffeur
    $voiture = $entityManager->getRepository(Voiture::class)->find($voitureId);
    if (!$voiture || !$voiture->getGere() || $voiture->getGere()->getUtilisateur() !== $user) {
        return new JsonResponse(['message' => 'Cette voiture ne vous appartient pas.'], Response::HTTP_FORBIDDEN);
    }
    
    // Déduire les 2 crédits pour le service
    $user->setCredits($user->getCredits() - 2);
    $entityManager->flush();
    
    // Création de l'entité Covoiturage
    $covoiturage = new Covoiturage();
    $covoiturage->setLieuDepart($lieuDepart)
        ->setLieuArrivee($lieuArrivee)
        ->setPrixPersonne($prixPersonne)
        ->setDateDepart($dateDepart)
        ->setHeureDepart($heureDepart)
        ->setDateArrivee($dateArrivee)
        ->setHeureArrivee($heureArrivee)
        ->setNbPlace($nbPlace)
        ->setStatut($statut);
    
    // Création de l'entité Participe pour associer le chauffeur au covoiturage
    $participe = new Participe();
    $participe->setUtilisateur($user);
    $participe->setCovoiturage($covoiturage);
    $covoiturage->addParticipe($participe);
    
    $entityManager->persist($covoiturage);
    $entityManager->persist($participe);
    $entityManager->flush();
    
    // Lier la voiture au covoiturage via l'entité Utilise
    $utilise = new Utilise();
    $utilise->setCovoiturage($covoiturage);
    $utilise->setVoiture($voiture);
    
    $entityManager->persist($utilise);
    $entityManager->flush();
    
    return new JsonResponse(['message' => 'Covoiturage créé avec succès'], Response::HTTP_CREATED);
}


#[Route('/filter', name: 'app_covoiturage_filter', methods: ['GET'])]
public function filter(Request $request, SessionInterface $session): JsonResponse
{
    // Debug : Vérifier si la session contient bien les données
    $sessionId = session_id();
    ##dump("SESSION ID:", $sessionId, "COOKIE:", $_COOKIE, "SESSION DATA:", $session->all());
    try {
        // 1) Récupérer le tableau "search_results" depuis la session
        $results = $session->get('search_results', []);

        // Si c'est vide, c'est que /search n'a pas été appelé
        if (empty($results)) {
            return new JsonResponse([
                'message' => 'Aucun résultat de recherche en session. Veuillez d\'abord appeler /search.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // 2) Lire les filtres dans l'URL
        $ecologiqueParam = $request->query->get('ecologique'); // ?ecologique=1
        $prixMaxParam = $request->query->get('prixMax');       // ?prixMax=10
        $dureeMaxParam = $request->query->get('dureeMax');     // ?dureeMax=120
        $noteMinParam = $request->query->get('noteMin');       // ?noteMin=4.5

        // Convertir en types
        $ecologique = ($ecologiqueParam === '1');
        $prixMax = $prixMaxParam !== null ? (float) $prixMaxParam : null;
        $dureeMax = $dureeMaxParam !== null ? (int) $dureeMaxParam : null;
        $noteMin = $noteMinParam !== null ? (float) $noteMinParam : null;

        // 3) Appliquer les filtres en PHP
        $filtered = array_filter($results, function ($cov) use ($ecologique, $prixMax, $dureeMax, $noteMin) {
            // Filtre écologique
            if ($ecologique && empty($cov['ecologique'])) {
                return false;
            }

            // Filtre prix maximum
            if ($prixMax !== null && isset($cov['prix']) && $cov['prix'] > $prixMax) {
                return false;
            }

            // Filtre durée maximum
            if ($dureeMax !== null) {
                $dateDepart = strtotime($cov['dateDepart'] . ' ' . $cov['heureDepart']);
                $dateArrivee = strtotime($cov['dateArrivee'] . ' ' . $cov['heureArrivee']);
                $diffMinutes = ($dateArrivee - $dateDepart) / 60;
                if ($diffMinutes > $dureeMax) {
                    return false;
                }
            }

            // Filtre note minimum
            if ($noteMin !== null) {
                // Note du chauffeur
                $noteChauffeur = $cov['chauffeur']['note'] ?? 0;
                if ($noteChauffeur < $noteMin) {
                    return false;
                }
            }

            // Si on arrive ici, tout est ok
            return true;
        });

        // 4) Réindexer et renvoyer
        $filtered = array_values($filtered);
        return new JsonResponse($filtered, JsonResponse::HTTP_OK);
        ##dump(session_id(), $session->all()); exit;

    } catch (\Exception $e) {
        return new JsonResponse(['message' => 'Erreur interne : ' . $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
    }
}

}


