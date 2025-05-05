<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Exception;

class RagChatbotService
{
    private $httpClient;
    private $apiBaseUrl;

    public function __construct(HttpClientInterface $httpClient, string $apiBaseUrl = 'http://localhost:3300')
    {
        $this->httpClient = $httpClient;
        $this->apiBaseUrl = $apiBaseUrl;
    }

    /**
     * Get API status to check if documents are loaded
     * 
     * @return array Status information including documents_loaded flag
     */
    public function getStatus(): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->apiBaseUrl . '/status');
            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                return $response->toArray();
            } else {
                throw new Exception('Error checking API status: ' . $statusCode);
            }
        } catch (Exception $e) {
            throw new Exception('Error connecting to API: ' . $e->getMessage());
        }
    }

    /**
     * Send chat messages to the API for processing
     * 
     * @param array $messages Array of chat messages with role and content
     * @return string The assistant's response content
     */
    public function chat(array $messages): string
    {
        try {
            $payload = ['messages' => $messages];
            
            $response = $this->httpClient->request(
                'POST',
                $this->apiBaseUrl . '/chat',
                [
                    'json' => $payload,
                    'timeout' => 60, // Increased timeout for RAG processing
                ]
            );
            
            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                $result = $response->toArray();
                return $result['choices'][0]['message']['content'];
            } else {
                $errorData = json_decode($response->getContent(false), true);
                $errorDetail = $errorData['detail'] ?? 'Unknown error';
                throw new Exception('Error from API: ' . $statusCode . ' - ' . $errorDetail);
            }
        } catch (Exception $e) {
            throw new Exception('Chat error: ' . $e->getMessage());
        }
    }

    /**
     * Upload and process a PDF file
     * 
     * @param string $filePath Path to the PDF file
     * @param string $fileName Original file name
     * @return array Processing result
     */
    public function uploadPdf(string $filePath, string $fileName): array
    {
        try {
            $response = $this->httpClient->request(
                'POST',
                $this->apiBaseUrl . '/upload',
                [
                    'multipart' => [
                        [
                            'name' => 'file',
                            'filename' => $fileName,
                            'content' => fopen($filePath, 'r'),
                        ],
                    ],
                ]
            );
            
            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                return $response->toArray();
            } else {
                throw new Exception('Error uploading PDF: ' . $statusCode);
            }
        } catch (Exception $e) {
            throw new Exception('Upload error: ' . $e->getMessage());
        }
    }

    /**
     * Get all documents from the database
     * 
     * @return array List of documents
     */
    public function getDocuments(): array
    {
        try {
            $response = $this->httpClient->request('GET', $this->apiBaseUrl . '/documents');
            $statusCode = $response->getStatusCode();
            
            if ($statusCode === 200) {
                return $response->toArray();
            } else {
                throw new Exception('Error retrieving documents: ' . $statusCode);
            }
        } catch (Exception $e) {
            throw new Exception('Error getting documents: ' . $e->getMessage());
        }
    }

    /**
     * Clear the document database
     * 
     * @return bool Success status
     */
    public function clearDatabase(): bool
    {
        try {
            $response = $this->httpClient->request('POST', $this->apiBaseUrl . '/clear-database');
            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            throw new Exception('Error clearing database: ' . $e->getMessage());
        }
    }
}