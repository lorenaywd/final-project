<?php

namespace App\Controller;


use App\Entity\Devis;
use App\Entity\User;
use App\Entity\Operation;
use App\Form\OperationType;
use App\Service\PdfService;
use App\Entity\TypeOperation;
use App\Form\ReclamationType;
use App\Service\SendMailService;
use App\Repository\UserRepository;
use App\Form\OperationNoteType;
use App\Form\TypeOperationType;
use Doctrine\ORM\EntityManager;
use App\Repository\OperationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/operation')]
class OperationController extends AbstractController
{
    #[Route('/', name: 'app_operation_index', methods: ['GET'])]
    public function index(OperationRepository $operationRepository): Response
    {
        return $this->render('admin/operation/index.html.twig', [
            'operations' => $operationRepository->findAll(),
        ]);
    }


    #[Route('/{id}', name: 'app_operation_show', methods: ['GET'])]
    public function show(Operation $operation): Response
    {
        return $this->render('admin/operation/show.html.twig', [
            'operation' => $operation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_operation_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Operation $operation, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(OperationType::class, $operation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_operation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin/operation/edit.html.twig', [
            'operation' => $operation,
            'form' => $form,
        ]);
    }

    #[Route('/assign/{id}', name: 'app_operation_assign', methods: ['GET', 'POST'])]
    public function assignOperation(Request $request, EntityManagerInterface $entityManager, UserRepository $userRepository, Operation $operation): Response
    {
        $roles = ['ROLE_SENIOR', 'ROLE_APPRENTI', 'ROLE_ADMIN'];
        $employees = $userRepository->findByRoles($roles); 

        $form = $this->createFormBuilder()
            ->add('user', ChoiceType::class, [
                'choices' => $employees,
                'choice_label' => function(?User $user) {
                    return $user ? $user->getFirstname() . ' ' . $user->getLastname() . ' (' . implode(', ', array_diff($user->getRoles(), ['ROLE_USER'])) . ')' : '';
                },
            ])
            ->getForm();
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $selectedUser = $form->getData()['user'];

            if (in_array('ROLE_ADMIN', $selectedUser->getRoles(), true) && $selectedUser->getOperationEnCours() >= 5) {
                $this->addFlash('warning', 'L\'administrateur a déjà atteint le nombre maximum d\'opérations en cours.');
                return $this->redirectToRoute('app_operation_index');
            } elseif (in_array('ROLE_SENIOR', $selectedUser->getRoles(), true) && $selectedUser->getOperationEnCours() >= 3) {
                $this->addFlash('warning', 'Le sénior a déjà atteint le nombre maximum d\'opérations en cours.');
                return $this->redirectToRoute('app_operation_index');
            } elseif (in_array('ROLE_APPRENTI', $selectedUser->getRoles(), true) && $selectedUser->getOperationEnCours() >= 1) {
                $this->addFlash('warning', 'L\'apprenti a déjà atteint le nombre maximum d\'opérations en cours.');
                return $this->redirectToRoute('app_operation_index');
            }
    
            // Décrémenter pour l'utilisateur actuel, s'il existe
            if ($operation->getUser() !== null && $operation->getUser()->getId() !== $selectedUser->getId()) {
                $currentUser = $operation->getUser();
                $currentUser->setOperationEnCours(max($currentUser->getOperationEnCours() - 1, 0));
                $entityManager->persist($currentUser);
            }
    
            // Incrémenter pour le nouvel utilisateur
            $selectedUser->setOperationEnCours($selectedUser->getOperationEnCours() + 1);
            $entityManager->persist($selectedUser);
    
            $operation->setUser($selectedUser);
            $entityManager->flush();
    
            return $this->redirectToRoute('app_operation_index');
        }
    
        return $this->render('admin/operation/assign.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/unassign/{id}', name: 'app_operation_unassign', methods: ['POST'])]
public function unassignOperation(Request $request, EntityManagerInterface $entityManager, Operation $operation): Response
{
    if ($this->isCsrfTokenValid('unassign'.$operation->getId(), $request->request->get('_token'))) {
        if ($operation->getUser() !== null) {
            $currentUser = $operation->getUser();
            $currentUser->setOperationEnCours(max($currentUser->getOperationEnCours() - 1, 0));
            $entityManager->persist($currentUser);
        }

        $operation->setUser(null);
        $entityManager->flush();
    }

    return $this->redirectToRoute('app_operation_index');
}

#[Route('/{id}/factures', name: 'app_operation_factures', methods: ['GET', 'POST'])]
    public function VoirFacture(PdfService $pdf, Operation $operation, UserRepository $userRepository, EntityManagerInterface $entityManager, Request $request): Response
    {
        $devis = $operation->getDevis()->first();

        if ($operation->isStatusOperation() && $devis) {

            $typeOperation = $devis->getTypeOperation(); // Récupérer l'entité TypeOperation à partir du devis

            $publicDirectory = $this->getParameter('kernel.project_dir') . '/public';
            $logoPath = $publicDirectory . '/images/logo.png';

            if (!file_exists($logoPath)) {
                throw new \Exception('Le fichier logo n\'existe pas.');
            }

            $logoData = base64_encode(file_get_contents($logoPath));
            $logoBase64 = 'data:image/png;base64,' . $logoData;

            $html = $this->renderView('Pdf/facture.html.twig', [
                'devi' => $devis,
                'type_operation' => $typeOperation,
                'logo_base64' => $logoBase64,
                'operation' => $operation
            ]);

            $pdfContent = $pdf->generateBinaryPDF($html);

            return new Response(
                $pdfContent,
                Response::HTTP_OK,
                [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'attachment; filename="facture.pdf"',
                ]
            );
        } else {
            $this->addFlash('warning', 'La facture n\'a pas pu être téléchargée');
            return $this->redirectToRoute('app_operation_index');
        }
    }
}
