<?php 
namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Repository\MessageRepository;
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
}