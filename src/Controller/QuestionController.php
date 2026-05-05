<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\Score;
use App\Form\QuestionType;
use App\Repository\QuestionRepository;
use App\Repository\ScoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/question')]
final class QuestionController extends AbstractController
{


    #[Route(name: 'app_question_index', methods: ['GET'])]
    public function index(QuestionRepository $questionRepository): Response
    {
        return $this->render('question/index.html.twig', [
            'questions' => $questionRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_question_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        //$utilisateur = $this->getUser();
        $question = new Question();
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($question);
            $entityManager->flush();

            return $this->redirectToRoute('app_question_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('question/new.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
        // $score=$entityManager->getRepository(Score::class)->findOneBy(['utilisateur' => $utilisateur]);
        // if ($score > 80) {
        //     $niveau = 'difficile';
        // } elseif ($score > 50) {
        //     $niveau = 'moyen';
        // } else {
        //     $niveau = 'facile';
        // }
        //$question = $this->aiQuizService->generateQuestion($niveau);
        
        //return $this->json($question);
    }

    #[Route('/{id}', name: 'app_question_show', methods: ['GET'])]
    public function show(Question $question): Response
    {
        return $this->render('question/show.html.twig', [
            'question' => $question,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_question_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_question_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('question/edit.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_question_delete', methods: ['POST'])]
    public function delete(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$question->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($question);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_question_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/quiz/submit-score', name: 'app_quiz_score_update', methods: ['POST'])]
public function updateScore(
    Request $request, 
    ScoreRepository $scoreRepo, 
    EntityManagerInterface $em
): Response {
    $pointsGagnes = (int) $request->request->get('points'); // Calculé en JS ou ici
    $utilisateur = $this->getUser();

    $scoreEntity = $scoreRepo->findOneBy(['utilisateur' => $utilisateur]);

    if (!$scoreEntity) {
        // Création du premier score
        $scoreEntity = new Score();
        $scoreEntity->setUtilisateur($utilisateur);
        $scoreEntity->setCreatedAt(new \DateTimeImmutable());
        $scoreEntity->setScore($pointsGagnes);
        $em->persist($scoreEntity);
    } else {
        // Mise à jour
        $scoreEntity->setScore($scoreEntity->getScore() + $pointsGagnes);
    }

    $em->flush();
    return $this->json(['status' => 'success', 'newTotal' => $scoreEntity->getScore()]);
}

    #[Route('/test/question', name: 'test')]
    public function test(): Response
    {
    // Cette page ne fait rien d'autre qu'afficher le squelette HTML/JS
    return $this->render('question/test.html.twig');
    }
}
