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
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
#[OA\Tag(name: 'Administration')]
class AdminFormationController extends AbstractController
{
    // ── Formations ──────────────────────────────────────────────────────────

    #[Route('/admin/formations', name: 'admin_formation_index')]
    #[OA\Get(
        summary: 'Lister les formations (admin)',
        description: 'Liste toutes les formations avec leur nombre de modules, catégorie et difficulté.',
        responses: [new OA\Response(response: 200, description: 'Liste des formations (HTML)')]
    )]
    public function index(FormationRepository $repo): Response
    {
        return $this->render('admin/formation/index.html.twig', [
            'formations' => $repo->findBy([], ['createdAt' => 'DESC']),
        ]);
    }

    #[Route('/admin/formations/new', name: 'admin_formation_new', methods: ['GET', 'POST'])]
    #[OA\Get(
        summary: 'Formulaire de création d\'une formation',
        responses: [new OA\Response(response: 200, description: 'Formulaire vide (HTML)')]
    )]
    #[OA\Post(
        summary: 'Créer une formation',
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'title', type: 'string', example: 'Introduction au Python'),
            new OA\Property(property: 'description', type: 'string'),
            new OA\Property(property: 'category', type: 'string', example: 'Programmation'),
            new OA\Property(property: 'difficulty', type: 'string', enum: ['beginner', 'intermediate', 'advanced']),
        ])),
        responses: [
            new OA\Response(response: 302, description: 'Redirection vers la liste'),
            new OA\Response(response: 422, description: 'Données invalides'),
        ]
    )]
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
    #[OA\Get(
        summary: 'Formulaire d\'édition d\'une formation',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Formulaire pré-rempli (HTML)'),
            new OA\Response(response: 404, description: 'Formation introuvable'),
        ]
    )]
    #[OA\Post(
        summary: 'Mettre à jour une formation',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 302, description: 'Redirection vers la liste'),
            new OA\Response(response: 422, description: 'Données invalides'),
        ]
    )]
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
    #[OA\Post(
        summary: 'Supprimer une formation',
        description: 'Supprime la formation ainsi que tous ses modules et questions (cascade). Protégé par token CSRF.',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 302, description: 'Redirection vers la liste')]
    )]
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
    #[OA\Get(
        summary: 'Lister les modules d\'une formation',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [
            new OA\Response(response: 200, description: 'Liste des modules avec position et nombre de questions (HTML)'),
            new OA\Response(response: 404, description: 'Formation introuvable'),
        ]
    )]
    public function moduleIndex(Formation $formation): Response
    {
        return $this->render('admin/module/index.html.twig', ['formation' => $formation]);
    }

    #[Route('/admin/formations/{id}/modules/new', name: 'admin_module_new', methods: ['GET', 'POST'])]
    #[OA\Get(
        summary: 'Formulaire de création d\'un module',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        responses: [new OA\Response(response: 200, description: 'Formulaire vide (HTML)')]
    )]
    #[OA\Post(
        summary: 'Créer un module',
        description: 'La position est initialisée à la suite des modules existants.',
        parameters: [new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'))],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'title', type: 'string', example: 'Les variables'),
            new OA\Property(property: 'content', type: 'string', description: 'Contenu pédagogique du module'),
            new OA\Property(property: 'position', type: 'integer', example: 0),
        ])),
        responses: [
            new OA\Response(response: 302, description: 'Redirection vers la liste des modules'),
            new OA\Response(response: 422, description: 'Données invalides'),
        ]
    )]
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
    #[OA\Get(
        summary: 'Formulaire d\'édition d\'un module',
        parameters: [
            new OA\Parameter(name: 'formationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Formulaire pré-rempli (HTML)'),
            new OA\Response(response: 404, description: 'Module ou formation introuvable'),
        ]
    )]
    #[OA\Post(
        summary: 'Mettre à jour un module',
        parameters: [
            new OA\Parameter(name: 'formationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 302, description: 'Redirection vers la liste des modules'),
            new OA\Response(response: 422, description: 'Données invalides'),
        ]
    )]
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
    #[OA\Post(
        summary: 'Supprimer un module',
        description: 'Supprime le module et toutes ses questions (cascade). Protégé par token CSRF.',
        parameters: [
            new OA\Parameter(name: 'formationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 302, description: 'Redirection vers la liste des modules')]
    )]
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
    #[OA\Get(
        summary: 'Lister les questions d\'un module',
        parameters: [
            new OA\Parameter(name: 'formationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer'), description: 'ID du module'),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Liste des questions avec type, difficulté et bonne réponse (HTML)'),
            new OA\Response(response: 404, description: 'Module ou formation introuvable'),
        ]
    )]
    public function questionIndex(int $formationId, Module $module, FormationRepository $formationRepo): Response
    {
        return $this->render('admin/module/questions.html.twig', [
            'formation' => $formationRepo->find($formationId) ?? throw $this->createNotFoundException(),
            'module' => $module,
        ]);
    }

    #[Route('/admin/formations/{formationId}/modules/{moduleId}/questions/new', name: 'admin_question_new', methods: ['GET', 'POST'])]
    #[OA\Get(
        summary: 'Formulaire de création d\'une question',
        parameters: [
            new OA\Parameter(name: 'formationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'moduleId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 200, description: 'Formulaire vide (HTML)')]
    )]
    #[OA\Post(
        summary: 'Créer une question',
        description: 'Pour les QCM, le champ `options` contient une option par ligne. `correctAnswer` doit correspondre exactement à l\'une des options.',
        parameters: [
            new OA\Parameter(name: 'formationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'moduleId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(content: new OA\JsonContent(properties: [
            new OA\Property(property: 'content', type: 'string', example: 'Quel mot-clé déclare une variable en Python ?'),
            new OA\Property(property: 'type', type: 'string', enum: ['mcq', 'open']),
            new OA\Property(property: 'difficulty', type: 'integer', enum: [1, 2, 3]),
            new OA\Property(property: 'options', type: 'string', example: "def\nvar\nlet"),
            new OA\Property(property: 'correctAnswer', type: 'string', example: 'def'),
        ])),
        responses: [
            new OA\Response(response: 302, description: 'Redirection vers la liste des questions'),
            new OA\Response(response: 422, description: 'Données invalides'),
        ]
    )]
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
    #[OA\Get(
        summary: 'Formulaire d\'édition d\'une question',
        parameters: [
            new OA\Parameter(name: 'formationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'moduleId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Formulaire pré-rempli (HTML)'),
            new OA\Response(response: 404, description: 'Question introuvable'),
        ]
    )]
    #[OA\Post(
        summary: 'Mettre à jour une question',
        parameters: [
            new OA\Parameter(name: 'formationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'moduleId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 302, description: 'Redirection vers la liste des questions'),
            new OA\Response(response: 422, description: 'Données invalides'),
        ]
    )]
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
    #[OA\Post(
        summary: 'Supprimer une question',
        description: 'Protégé par token CSRF.',
        parameters: [
            new OA\Parameter(name: 'formationId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'moduleId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [new OA\Response(response: 302, description: 'Redirection vers la liste des questions')]
    )]
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
