<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\Question2;
use App\Repository\MessageRepository;
use App\Repository\ScoreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\AI\Platform\Message\UserMessage;
use Symfony\AI\Platform\Message\AssistantMessage;
use Symfony\AI\Platform\Message\Content\Text;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request; // Utilise HttpFoundation, pas BrowserKit
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\Attribute\Target;

final class ChatController extends AbstractController
{

    #[Route('/chat/{id}', name: 'app_chat_view', methods: ['GET'])]
    public function view(Conversation $conversation): Response
    {
        return $this->render('chat/index.html.twig', [
            'conversation' => $conversation
        ]);
    }

    #[Route('/créer/chat', name: 'app_chat_new')]
    public function create(EntityManagerInterface $em): Response
    {
        $conversation = new Conversation();
        $conversation->setTheme('Entraînement Général');
        $conversation->setCreatedAt(new \DateTimeImmutable());
        $conversation->setDifficulte(1); // Niveau débutant par défaut

        $em->persist($conversation);
        $em->flush();

        // On redirige vers la vue de cette nouvelle conversation
        return $this->redirectToRoute('app_chat_view', ['id' => $conversation->getId()]);
    }

    #[Route('/chat/{id}', name: 'app_chat_ask', methods: ['POST'])]
    public function ask(
        Conversation $conversation,
        Request $request,
        MessageRepository $repo,
        EntityManagerInterface $em,
        #[Target('chatbot_assistant')] AgentInterface $chatbotAgent
    ): Response {
        $userText = $request->request->get('message');

        if (!$userText) {
            return $this->json(['error' => 'Message vide'], 400);
        }

        // 1. Sauvegarder le message de l'utilisateur en BDD
        $userMessage = new Message();
        $userMessage->setContenu($userText);
        $userMessage->setRole('user');
        $userMessage->setConversation($conversation);
        $userMessage->setCreatedAt(new \DateTimeImmutable());
        $em->persist($userMessage);

        // 2. Récupérer l'historique pour l'IA
        $messages = $repo->findBy(['conversation' => $conversation], ['created_at' => 'ASC']);

        // 3. Construire le MessageBag pour l'IA
        $messageBag = new MessageBag();
        foreach ($messages as $msg) {
            if ($msg->getRole() === 'user') {
                $messageBag->add(new UserMessage(new Text($msg->getContenu())));
            } elseif ($msg->getRole() === 'assistant') {
                $messageBag->add(new AssistantMessage($msg->getContenu()));
            }
        }
        $messageBag->add(new UserMessage(new Text($userText)));

        $result = $chatbotAgent->call($messageBag);

        $aiContent = $result->getContent();

        // 4. Enregistrer la réponse de l'IA
        $aiMessage = new Message();
        $aiMessage->setContenu($aiContent);
        $aiMessage->setRole('assistant');
        $aiMessage->setConversation($conversation);
        $aiMessage->setCreatedAt(new \DateTimeImmutable());
        $em->persist($aiMessage);

        $em->flush();

        return $this->json([
            'response' => $aiContent
        ]);
    }

    #[Route('/quiz/generate', name: 'app_quiz_generate', methods: ['POST', 'GET'])]
    public function generate(
        Request $request,
        EntityManagerInterface $em,
        ScoreRepository $scoreRepo,
        #[Target('quiz_generator')] AgentInterface $quizAgent
    ): Response {
        // 1. On cherche le score de l'utilisateur
        $scoreEntity = $scoreRepo->findOneBy(['utilisateur' => $this->getUser()]);

        // 2. Détermination du contexte (Premier test ou adaptation)
        if (!$scoreEntity) {
            // CAS 1 : Premier arrivant
            $theme = "Culture Générale et Logique";
            $instructionDifficulté = "C'est un test de positionnement. Génère des questions de niveaux variés (facile à moyen) pour évaluer l'utilisateur.";
        } else {
            // CAS 2 : Utilisateur connu
            $theme = $request->request->get('theme', 'Informatique'); // Thème dynamique ou par défaut
            $currentScore = $scoreEntity->getScore();
            $instructionDifficulté = sprintf(
                "L'utilisateur a un score de %d. Adapte la difficulté : si le score est > 100, propose des questions complexes. Si < 50, reste sur des bases.",
                $currentScore
            );
        }

        $prompt = sprintf(
            "Thème : %s. %s. Génère 5 questions au format JSON : [{\"question\": \"...\", \"reponses\": [\"...\"], \"correct\": 0}]. Ne parle pas, réponds juste le JSON.",
            $theme,
            $instructionDifficulté
        );

        // 3. Appel de l'IA avec le MessageBag (Correction pour ton erreur de type)
        $messageBag = new MessageBag();
        $messageBag->add(new UserMessage(new Text($prompt)));
        $result = $quizAgent->call($messageBag);

        $content = (string) $result->getContent();
        $data = json_decode(preg_replace('/```json|
```/', '', $content), true);

        if (!$data) return $this->json(['error' => 'IA Error'], 500);

        // 4. Enregistrement des questions pour cette session
        foreach ($data as $item) {
            $q = new Question2();
            $q->setIntitule($item['question']);
            $q->setChoix($item['reponses']);
            $q->setIndexCorrect($item['correct']);
            // On lie éventuellement à l'utilisateur ici
            $em->persist($q);
        }
        $em->flush();

        return $this->json([
            'isFirstTime' => !$scoreEntity,
            'questions' => $data
        ]);
    }
}
