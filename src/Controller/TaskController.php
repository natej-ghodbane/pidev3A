<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/task')]
class TaskController extends AbstractController
{
    #[Route('/', name: 'app_task_index', methods: ['GET'])]
    public function index(TaskRepository $taskRepository): Response
    {
        $tasks = $taskRepository->findBy(['isArchived' => false], ['createdAt' => 'DESC']);
        $archivedTasks = $taskRepository->findBy(['isArchived' => true], ['createdAt' => 'DESC']);
        
        $statistics = [
            'total' => count($tasks) + count($archivedTasks),
            'pending' => count(array_filter($tasks, fn($t) => $t->getStatus() === 'pending')),
            'doing' => count(array_filter($tasks, fn($t) => $t->getStatus() === 'doing')),
            'done' => count(array_filter($tasks, fn($t) => $t->getStatus() === 'done')),
            'archived' => count($archivedTasks)
        ];

        return $this->render('task.html.twig', [
            'tasks' => $tasks,
            'archivedTasks' => $archivedTasks,
            'statistics' => $statistics
        ]);
    }

    #[Route('/new', name: 'app_task_new', methods: ['POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!isset($data['title']) || trim($data['title']) === '') {
                throw new \InvalidArgumentException('Task title is required');
            }

            $task = new Task();
            $task->setTitle($data['title']);
            $task->setDescription($data['description'] ?? null);
            $task->setStatus('pending');
            $task->setPriority($data['priority'] ?? 'medium');
            $task->setCategory($data['category'] ?? null);

            $entityManager->persist($task);
            $entityManager->flush();

            return $this->json([
                'status' => 'success',
                'id' => $task->getId(),
                'title' => $task->getTitle(),
                'description' => $task->getDescription(),
                'status' => $task->getStatus(),
                'priority' => $task->getPriority(),
                'category' => $task->getCategory()
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/status', name: 'app_task_status', methods: ['PUT'])]
    public function updateStatus(Request $request, Task $task, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);
            if (!isset($data['status'])) {
                throw new \InvalidArgumentException('Status is required');
            }

            $task->setStatus($data['status']);
            $entityManager->flush();

            return $this->json(['status' => 'success']);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/archive', name: 'app_task_archive', methods: ['PUT'])]
    public function archive(Task $task, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $task->setIsArchived(true);
            $entityManager->flush();

            return $this->json(['status' => 'success']);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/restore', name: 'app_task_restore', methods: ['PUT'])]
    public function restore(Task $task, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $task->setIsArchived(false);
            $entityManager->flush();

            return $this->json(['status' => 'success']);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}/edit', name: 'app_task_edit', methods: ['PUT'])]
    public function edit(Request $request, Task $task, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $data = json_decode($request->getContent(), true);

            if (isset($data['title'])) {
                $task->setTitle($data['title']);
            }
            if (isset($data['description'])) {
                $task->setDescription($data['description']);
            }
            if (isset($data['priority'])) {
                $task->setPriority($data['priority']);
            }
            if (isset($data['category'])) {
                $task->setCategory($data['category']);
            }

            $entityManager->flush();

            return $this->json([
                'status' => 'success',
                'task' => [
                    'id' => $task->getId(),
                    'title' => $task->getTitle(),
                    'description' => $task->getDescription(),
                    'status' => $task->getStatus(),
                    'priority' => $task->getPriority(),
                    'category' => $task->getCategory()
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }

    #[Route('/{id}', name: 'app_task_delete', methods: ['DELETE'])]
    public function delete(Task $task, EntityManagerInterface $entityManager): JsonResponse
    {
        try {
            $entityManager->remove($task);
            $entityManager->flush();

            return $this->json(['status' => 'success']);
        } catch (\Exception $e) {
            return $this->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 400);
        }
    }
} 