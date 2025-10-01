<?php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Covoiturage;
use App\Entity\Depose;
use App\Form\AvisType;
use App\Repository\AvisRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use OpenApi\Attributes as OA;


#[Route('/api/avis')]
#[OA\Tag(name: 'Avis')]
#[IsGranted('ROLE_USER')]
class AvisController extends AbstractController
{
    /**
     * Affiche le formulaire et gère la soumission d'un avis pour un covoiturage spécifique.
     */
    #[Route('/avis/creer/{id}', name: 'app_avis_creer_pour_covoiturage')]
    public function laisserAvis(Covoiturage $covoiturage, Request $request, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\Utilisateur $user */
        $user = $this->getUser();

        $avis = new Avis();
        $form = $this->createForm(AvisType::class, $avis);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $avis->setStatut('en_attente'); // L'avis doit être validé par un employé

            // On ajoute une information contextuelle à l'avis.
            // Le plus simple est d'utiliser un champ non mappé ou un champ JSON dans l'entité Avis.
            // Ici, nous allons stocker l'ID dans le commentaire pour la démo, mais un champ dédié serait mieux.
            $commentaireOriginal = $avis->getCommentaire();
            $context = json_encode(['covoiturage_id' => $covoiturage->getId()]);
            $avis->setCommentaire($commentaireOriginal . "");


            // On crée la liaison Depose comme avant
            $depose = new Depose();
            $depose->setUtilisateur($user);
            $depose->addAvi($avis);
            $avis->setDepose($depose);

            $em->persist($depose);
            $em->persist($avis);
            $em->flush();

            $this->addFlash('success', 'Votre avis a été soumis pour validation. Merci !');
            return $this->redirectToRoute('app_home'); // ou vers l'historique des trajets
        }

        return $this->render('avis/laisser_avis.html.twig', [
            'form' => $form->createView(),
            'covoiturage' => $covoiturage,
        ]);
    }

}
