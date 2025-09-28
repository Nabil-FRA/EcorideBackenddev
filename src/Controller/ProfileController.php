<?php

namespace App\Controller;

use App\Entity\Marque;
use App\Entity\Voiture;
use App\Entity\Gere;
use App\Entity\Detient;
use App\Entity\Parametre;
use App\Entity\Configuration;
use App\Entity\Dispose;
use App\Entity\ParametreUtilisateur;
use App\Repository\UtilisateurRepository;
use App\Repository\VoitureRepository;
use App\Repository\MarqueRepository;
use App\Repository\GereRepository;
use App\Repository\DetientRepository;
use App\Repository\ParametreRepository;
use App\Repository\ConfigurationRepository;
use App\Repository\DisposeRepository;
use App\Repository\ParametreUtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use OpenApi\Attributes as OA;

#[Route('/api/profile', name: 'api_profile_')]
#[OA\Tag(name: 'Profile')]
class ProfileController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

    /**
     * Met à jour le statut (chauffeur/passager) de l'utilisateur.
     */
    #[OA\Security(name: "Bearer")]
    #[OA\RequestBody(
        description: "Données pour la mise à jour du statut.",
        required: true,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'email', type: 'string', example: 'utilisateur@test.com'),
            new OA\Property(property: 'isChauffeur', type: 'boolean', example: true),
            new OA\Property(property: 'isPassager', type: 'boolean', example: true)
        ])
    )]
    #[OA\Response(response: 200, description: "Statut mis à jour avec succès.")]
    #[OA\Response(response: 400, description: "Données manquantes ou invalides.")]
    #[OA\Response(response: 401, description: "Utilisateur non authentifié.")]
    #[OA\Response(response: 403, description: "Accès refusé.")]
    #[OA\Response(response: 404, description: "Utilisateur introuvable.")]
    #[Route('/status', name: 'update_status', methods: ['POST'])]
    public function updateStatus(
        Request $request,
        UtilisateurRepository $utilisateurRepository
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        if (!$email) {
            return new JsonResponse(['message' => 'Email manquant'], 400);
        }

        $utilisateur = $utilisateurRepository->findOneBy(['email' => $email]);
        if (!$utilisateur) {
            return new JsonResponse(['message' => 'Utilisateur introuvable'], 404);
        }

        if ($user->getEmail() !== $utilisateur->getEmail() && !in_array('ROLE_ADMIN', $user->getRoles())) {
            return new JsonResponse(['message' => 'Accès refusé'], 403);
        }

        $isChauffeur = isset($data['isChauffeur']) ? (bool) $data['isChauffeur'] : $utilisateur->isChauffeur();
        $isPassager = isset($data['isPassager']) ? (bool) $data['isPassager'] : $utilisateur->isPassager();

        $utilisateur->setIsChauffeur($isChauffeur);
        $utilisateur->setIsPassager($isPassager);
        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Statut mis à jour avec succès',
            'isChauffeur' => $utilisateur->isChauffeur(),
            'isPassager' => $utilisateur->isPassager(),
        ], 200);
    }

    /**
     * Enregistre l'utilisateur comme chauffeur avec son véhicule et ses préférences.
     */
    #[OA\Security(name: "Bearer")]
    #[OA\RequestBody(
        description: "Données complètes pour enregistrer un chauffeur, son véhicule et ses préférences.",
        required: true,
        content: new OA\JsonContent(properties: [
            new OA\Property(property: 'marque', type: 'string', example: 'Peugeot'),
            new OA\Property(property: 'voiture', type: 'object', properties: [
                new OA\Property(property: 'immatriculation', type: 'string', example: 'AB-123-CD'),
                new OA\Property(property: 'modele', type: 'string', example: '208'),
                new OA\Property(property: 'couleur', type: 'string', example: 'Noire'),
                new OA\Property(property: 'energie', type: 'string', example: 'Essence'),
                new OA\Property(property: 'date_premiere_immatriculation', type: 'string', format: 'date', example: '2020-01-15'),
            ]),
            new OA\Property(property: 'preferences', type: 'array', items: new OA\Items(properties: [
                new OA\Property(property: 'propriete', type: 'string', example: 'Musique'),
                new OA\Property(property: 'valeur', type: 'string', example: 'Rock'),
            ])),
        ])
    )]
    #[OA\Response(response: 201, description: "Chauffeur, véhicule et préférences enregistrés avec succès.")]
    #[OA\Response(response: 400, description: "Données incomplètes.")]
    #[OA\Response(response: 401, description: "Utilisateur non authentifié.")]
    #[OA\Response(response: 409, description: "Cette voiture est déjà associée à l'utilisateur.")]
    #[Route('/register-chauffeur', name: 'register_chauffeur', methods: ['POST'])]
    public function registerChauffeur(
        Request $request,
        UtilisateurRepository $utilisateurRepository,
        VoitureRepository $voitureRepository,
        MarqueRepository $marqueRepository,
        GereRepository $gereRepository,
        DetientRepository $detientRepository,
        ParametreRepository $parametreRepository,
        ConfigurationRepository $configurationRepository,
        DisposeRepository $disposeRepository,
        ParametreUtilisateurRepository $parametreUtilisateurRepository
    ): JsonResponse {
        error_log("🚀 [registerChauffeur] Début du traitement");

        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Utilisateur non authentifié'], 401);
        }

        $data = json_decode($request->getContent(), true);
        if (!$data || !isset($data['voiture'], $data['marque'], $data['preferences'])) {
            return new JsonResponse(['message' => 'Données incomplètes'], 400);
        }

        // 🚗 Vérifier et enregistrer la marque du véhicule
        $marque = $marqueRepository->findOneBy(['libelle' => $data['marque']]);
        if (!$marque) {
            $marque = new Marque();
            $marque->setLibelle($data['marque']);
            $this->entityManager->persist($marque);
        }

        // ✅ Vérifier si la voiture existe déjà
        $existingVoiture = $voitureRepository->findOneBy(['immatriculation' => $data['voiture']['immatriculation']]);
        if ($existingVoiture) {
            $voiture = $existingVoiture;
            $existingGere = $gereRepository->findOneBy(['utilisateur' => $user, 'voiture' => $voiture]);
            if ($existingGere) {
                return new JsonResponse(['message' => 'Cette voiture est déjà associée à l\'utilisateur.'], 409);
            }
        } else {
            $voiture = new Voiture();
            $voiture->setImmatriculation($data['voiture']['immatriculation']);
            $voiture->setModele($data['voiture']['modele']);
            $voiture->setCouleur($data['voiture']['couleur']);
            $voiture->setEnergie($data['voiture']['energie']);
            $voiture->setDatePremiereImmatriculation(new \DateTimeImmutable($data['voiture']['date_premiere_immatriculation']));
            $this->entityManager->persist($voiture);
        }

        // 🔗 Vérifier si l'association utilisateur-voiture existe déjà
        $existingGere = $gereRepository->findOneBy(['utilisateur' => $user, 'voiture' => $voiture]);
        if (!$existingGere) {
            $gere = new Gere();
            $gere->setUtilisateur($user);
            $gere->setVoiture($voiture);
            $this->entityManager->persist($gere);
        }

        // ✅ Enregistrer les préférences
        foreach ($data['preferences'] as $prefData) {
            $existingParam = $parametreRepository->findOneBy([
                'propriete' => $prefData['propriete'],
                'valeur' => $prefData['valeur']
            ]);

            if (!$existingParam) {
                $parametre = new Parametre();
                $parametre->setPropriete($prefData['propriete']);
                $parametre->setValeur($prefData['valeur']);
                $this->entityManager->persist($parametre);
            } else {
                $parametre = $existingParam;
            }
        }

        $this->entityManager->flush();

        return new JsonResponse([
            'message' => 'Chauffeur, véhicule et préférences enregistrés avec succès',
            'voiture' => ['id' => $voiture->getId(), 'modele' => $voiture->getModele(), 'immatriculation' => $voiture->getImmatriculation()],
            'preferences' => $data['preferences']
        ], 201);
    }
}
