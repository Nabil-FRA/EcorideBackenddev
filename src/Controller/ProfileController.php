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

#[Route('/api/profile', name: 'api_profile_')]
class ProfileController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $em)
    {
        $this->entityManager = $em;
    }

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