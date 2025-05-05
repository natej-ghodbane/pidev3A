<?php

namespace App\Controller;

use App\Entity\ChatMessage;
use App\Service\RagChatbotService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RequestStack;  // Change this line
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Exception;

class RagChatbotController extends AbstractController
{
    private $chatbotService;
    private $requestStack;  // Change this line


    public function __construct(RagChatbotService $chatbotService, RequestStack $requestStack)
    {
        $this->chatbotService = $chatbotService;
        $this->requestStack = $requestStack;  // Change this line
    }
    #[Route('/chatbot', name: 'app_chatbot')]
    public function index(): Response
    {
        // Get chat history from session or initialize empty array
        $chatHistory = $this->getSession()->get('chat_history', []);  
        
        // Convert to ChatMessage objects for the view
        $messages = [];
        foreach ($chatHistory as $message) {
            $chatMessage = new ChatMessage(
                $message['role'],
                $message['content'],
                new \DateTime($message['timestamp'])
            );
            $messages[] = $chatMessage;
        }
        
        // Get API status
        try {
            $apiStatus = $this->chatbotService->getStatus();
            $documentsLoaded = $apiStatus['documents_loaded'] ?? false;
            $documentCount = $apiStatus['document_count'] ?? 0;
        } catch (Exception $e) {
            $documentsLoaded = false;
            $documentCount = 0;
            $this->addFlash('error', 'Cannot connect to RAG API: ' . $e->getMessage());
        }
        
        return $this->render('rag_chatbot/index.html.twig', [
            'messages' => $messages,
            'documents_loaded' => $documentsLoaded,
            'document_count' => $documentCount,
        ]);
    }
    private function getSession()
    {
        return $this->requestStack->getSession();  // Add this method
    }

    #[Route('/chatbot/send', name: 'app_chatbot_send' , methods: ['POST'])]
    public function sendMessage(Request $request): JsonResponse
    {
        $content = $request->request->get('message');
        
        if (empty($content)) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Message cannot be empty'
            ]);
        }
        
        // Get chat history from session
        $chatHistory = $this->getSession()->get('chat_history', []);
        
        // Create new user message
        $userMessage = [
            'role' => 'user',
            'content' => $content,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
        ];
        
        // Add to history
        $chatHistory[] = $userMessage;
        
        // Prepare messages for API
        $apiMessages = array_map(function($msg) {
            return [
                'role' => $msg['role'],
                'content' => $msg['content']
            ];
        }, $chatHistory);
        
        try {
            // Get API status first to ensure documents are loaded
            $apiStatus = $this->chatbotService->getStatus();
            
            if (!$apiStatus['documents_loaded']) {
                $response = 'Please upload and process some PDF documents first.';
            } else {
                // Send to API
                $response = $this->chatbotService->chat($apiMessages);
            }
            
            // Create assistant message
            $assistantMessage = [
                'role' => 'assistant',
                'content' => $response,
                'timestamp' => (new \DateTime())->format('Y-m-d H:i:s')
            ];
            
            // Add to history
            $chatHistory[] = $assistantMessage;
            
            // Save back to session
            $this->getSession()->set('chat_history', $chatHistory);
            
            return new JsonResponse([
                'success' => true,
                'message' => $assistantMessage
            ]);
            
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }

    #[Route('/chatbot/upload', name: 'app_chatbot_upload', methods: ['POST'])]
    public function uploadPdf(Request $request): JsonResponse
    {
        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('pdf_file');
        
        if (!$uploadedFile) {
            return new JsonResponse([
                'success' => false,
                'error' => 'No file uploaded'
            ]);
        }
        
        if ($uploadedFile->getMimeType() !== 'application/pdf') {
            return new JsonResponse([
                'success' => false,
                'error' => 'Only PDF files are allowed'
            ]);
        }
        
        try {
            // Move to temporary location
            $fileName = $uploadedFile->getClientOriginalName();
            $filePath = $uploadedFile->getPathname();
            
            // Upload to API
            $result = $this->chatbotService->uploadPdf($filePath, $fileName);
            
            return new JsonResponse([
                'success' => true,
                'message' => sprintf(
                    'Successfully processed %s - %s chunks created',
                    $fileName,
                    $result['document_count'] ?? 0
                )
            ]);
            
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Error uploading PDF: ' . $e->getMessage()
            ]);
        }
    }

    #[Route('/chatbot/reset', name: 'app_chatbot_reset', methods: ['POST'])]
    public function resetChat(): JsonResponse
    {
        // Clear chat history from session
        $this->getSession()->set('chat_history', []);
        
        return new JsonResponse([
            'success' => true,
            'message' => 'Chat history cleared'
        ]);
    }

    #[Route('/chatbot/clear-database', name: 'app_chatbot_clear_database', methods: ['POST'])]
    public function clearDatabase(): JsonResponse
    {
        try {
            $result = $this->chatbotService->clearDatabase();
            
            return new JsonResponse([
                'success' => $result,
                'message' => 'Document database has been cleared'
            ]);
            
        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Error clearing database: ' . $e->getMessage()
            ]);
        }
    }
}