<?php

namespace App\Controller\Admin;

use App\Entity\Formation;
use App\Entity\Module;
use App\Entity\Question;
use App\Form\Admin\FormationType;
use App\Form\Admin\ModuleType;
use App\Form\Admin\QuestionType;
use App\Repository\FormationRepository;
use App\Repository\ModuleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminFormationController extends AbstractController
{
    // ── Formations ──────────────────────────────────────────────────────────

    #[Route('/admin/formations', name: 'admin_formation_index')]
    public function index(FormationRepository $repo): Response
    {
        return $this->render('admin/formation/index.html.twig', [
            'formations' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/admin/formations/new', name: 'admin_formation_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $formation = new Formation();
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($formation);
            $em->flush();
            $this->addFlash('success', 'Formation créée.');
            return $this->redirectToRoute('admin_formation_index');
        }

        return $this->render('admin/formation/form.html.twig', ['form' => $form, 'formation' => $formation]);
    }

    #[Route('/admin/formations/{id}/edit', name: 'admin_formation_edit', methods: ['GET', 'POST'])]
    public function edit(Formation $formation, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(FormationType::class, $formation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Formation mise à jour.');
            return $this->redirectToRoute('admin_formation_index');
        }

        return $this->render('admin/formation/form.html.twig', ['form' => $form, 'formation' => $formation]);
    }

    #[Route('/admin/formations/{id}/delete', name: 'admin_formation_delete', methods: ['POST'])]
    public function deleteFormation(Formation $formation, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_formation_' . $formation->getId(), $request->request->get('_token'))) {
            $em->remove($formation);
            $em->flush();
            $this->addFlash('success', 'Formation supprimée.');
        }

        return $this->redirectToRoute('admin_formation_index');
    }

    // ── Modules ─────────────────────────────────────────────────────────────

    #[Route('/admin/formations/{id}/modules', name: 'admin_module_index')]
    public function moduleIndex(Formation $formation): Response
    {
        return $this->render('admin/module/index.html.twig', ['formation' => $formation]);
    }

    #[Route('/admin/formations/{id}/modules/new', name: 'admin_module_new', methods: ['GET', 'POST'])]
    public function moduleNew(Formation $formation, Request $request, EntityManagerInterface $em): Response
    {
        $module = new Module();
        $module->setPosition($formation->getModules()->count());
        $form = $this->createForm(ModuleType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $module->setFormation($formation);
            $em->persist($module);
            $em->flush();
            $this->addFlash('success', 'Module créé.');
            return $this->redirectToRoute('admin_module_index', ['id' => $formation->getId()]);
        }

        return $this->render('admin/module/form.html.twig', [
            'form' => $form,
            'formation' => $formation,
            'module' => $module,
        ]);
    }

    #[Route('/admin/formations/{formationId}/modules/{id}/edit', name: 'admin_module_edit', methods: ['GET', 'POST'])]
    public function moduleEdit(int $formationId, Module $module, Request $request, EntityManagerInterface $em, FormationRepository $formationRepo): Response
    {
        $formation = $formationRepo->find($formationId) ?? throw $this->createNotFoundException();
        $form = $this->createForm(ModuleType::class, $module);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Module mis à jour.');
            return $this->redirectToRoute('admin_module_index', ['id' => $formationId]);
        }

        return $this->render('admin/module/form.html.twig', [
            'form' => $form,
            'formation' => $formation,
            'module' => $module,
        ]);
    }

    #[Route('/admin/formations/{formationId}/modules/{id}/delete', name: 'admin_module_delete', methods: ['POST'])]
    public function moduleDelete(int $formationId, Module $module, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_module_' . $module->getId(), $request->request->get('_token'))) {
            $em->remove($module);
            $em->flush();
            $this->addFlash('success', 'Module supprimé.');
        }

        return $this->redirectToRoute('admin_module_index', ['id' => $formationId]);
    }

    // ── Questions ────────────────────────────────────────────────────────────

    #[Route('/admin/formations/{formationId}/modules/{id}/questions', name: 'admin_question_index')]
    public function questionIndex(int $formationId, Module $module, FormationRepository $formationRepo): Response
    {
        return $this->render('admin/module/questions.html.twig', [
            'formation' => $formationRepo->find($formationId) ?? throw $this->createNotFoundException(),
            'module' => $module,
        ]);
    }

    #[Route('/admin/formations/{formationId}/modules/{moduleId}/questions/new', name: 'admin_question_new', methods: ['GET', 'POST'])]
    public function questionNew(int $formationId, int $moduleId, Request $request, EntityManagerInterface $em, ModuleRepository $moduleRepo, FormationRepository $formationRepo): Response
    {
        $module = $moduleRepo->find($moduleId) ?? throw $this->createNotFoundException();
        $formation = $formationRepo->find($formationId) ?? throw $this->createNotFoundException();
        $question = new Question();
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $question->setModule($module);
            $em->persist($question);
            $em->flush();
            $this->addFlash('success', 'Question créée.');
            return $this->redirectToRoute('admin_question_index', [
                'formationId' => $formationId,
                'id' => $moduleId,
            ]);
        }

        return $this->render('admin/module/question_form.html.twig', [
            'form' => $form,
            'formation' => $formation,
            'module' => $module,
            'question' => $question,
        ]);
    }

    #[Route('/admin/formations/{formationId}/modules/{moduleId}/questions/{id}/edit', name: 'admin_question_edit', methods: ['GET', 'POST'])]
    public function questionEdit(int $formationId, int $moduleId, Question $question, Request $request, EntityManagerInterface $em, ModuleRepository $moduleRepo, FormationRepository $formationRepo): Response
    {
        $module = $moduleRepo->find($moduleId) ?? throw $this->createNotFoundException();
        $formation = $formationRepo->find($formationId) ?? throw $this->createNotFoundException();
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Question mise à jour.');
            return $this->redirectToRoute('admin_question_index', [
                'formationId' => $formationId,
                'id' => $moduleId,
            ]);
        }

        return $this->render('admin/module/question_form.html.twig', [
            'form' => $form,
            'formation' => $formation,
            'module' => $module,
            'question' => $question,
        ]);
    }

    #[Route('/admin/formations/{formationId}/modules/{moduleId}/questions/{id}/delete', name: 'admin_question_delete', methods: ['POST'])]
    public function questionDelete(int $formationId, int $moduleId, Question $question, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_question_' . $question->getId(), $request->request->get('_token'))) {
            $em->remove($question);
            $em->flush();
            $this->addFlash('success', 'Question supprimée.');
        }

        return $this->redirectToRoute('admin_question_index', [
            'formationId' => $formationId,
            'id' => $moduleId,
        ]);
    }
}
