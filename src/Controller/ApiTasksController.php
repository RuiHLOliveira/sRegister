<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Situation;
use App\Entity\Task;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Exception;
use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\JsonResponse;

class ApiTasksController extends AbstractController
{
    /**
     * @Route("/api/tasks", methods={"POST"})
     */
    public function store(Request $request)
    {
        try {
            $postData = $request->request;

            if($postData->get('name') === null || $postData->get('name') === ''){
                throw new ValidationException('Name is needed');
            }

            $task = new Task();
            $task->setName($postData->get('name'));
            $task->setUser($this->getUser());

            $projectFromRequest = $postData->get('project');
            if(isset($projectFromRequest)
                && $projectFromRequest != ''
            ) {
                $project = $this->getDoctrine()->getRepository(Project::class)
                    ->findOneBy([
                    'user' => $this->getUser(),
                    'id' => $projectFromRequest
                ]);
                if($project == null){
                    $this->createNotFoundException("Project not found");
                }
                $task->setProject($project);
            }

            $situation = $this->getDoctrine()->getRepository(Situation::class)
                ->findOneBy(['id' => 1]);
            if($situation == null){
                $this->createNotFoundException('Desired situation not found');
            }
            $task->setSituation($situation);
            
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($task);
            $entityManager->flush();

            $task = $this->getDoctrine()->getRepository(Task::class)
                ->findOneBy([
                'user' => $this->getUser(),
                'id' => $task->getId()
            ]);

            return new JsonResponse(compact('task'), 201);
        } catch (ValidationException $e) {
            // Log::error($e->getMessage());
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 400);
        } catch (Exception $e) {
            // Log::error($e->getMessage());
            // throw $e;
            $message = $e->getMessage(); // or There was an error while storing your task.
            return new JsonResponse(compact('message'), 500);
        }
    }

    /**
     * @Route("/api/tasks/{id}", methods={"GET","HEAD"})
     */
    public function show($id)
    {
        try {
            $user = $this->getUser();
            $task = $this->getDoctrine()->getRepository(Task::class)
                ->findOneBy(['id' => $id, 'user' => $user]);
            if($task == null){
                $this->createNotFoundException("Task not found");
            }
            
            return new JsonResponse(compact('task'));
        } catch (NotFoundHttpException $e) {
            $message = "Task not found";
            return new JsonResponse(compact('message'), 404);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $message = $e->getMessage(); // or There was an error in your task.
            return new JsonResponse(compact('message'), 500);
        }
    }

    /**
     * @Route("/api/tasks/{id}", methods={"PUT","PATCH"})
     */
    public function update(Request $request, $id)
    {
        try {
            $postData = $request->request;
            $duedate = $postData->get('duedate');
            if(is_array($duedate)){
                $duedate = substr($duedate['date'],0,10);
                $postData->set('duedate', $duedate);
            }
            //validating
            if($postData->get('name') === null || $postData->get('name') === ''){
                throw new ValidationException('Name is needed');
            }

            $user = $this->getUser();
            $task = $this->getDoctrine()
                ->getRepository(Task::class)
                ->findOneBy([
                    'id' => $id,
                    'user' => $user
                ]);
            if($task == null){
                $this->createNotFoundException('Task not found');
            }

            $considerProjectForm = $postData->get('considerProjectForm');
            $projectFromRequest = $postData->get('project');

            if(isset($considerProjectForm)
                && $considerProjectForm == 1
                && isset($projectFromRequest)
                && $projectFromRequest != ''
            ) {
                $project = $this->getDoctrine()->getRepository(Project::class)
                    ->findOneBy([
                    'user' => $user,
                    'id' => $projectFromRequest
                ]);
                if($project == null){
                    $this->createNotFoundException("Project not found");
                }
                $task->setProject($project);
            }
            
            if(!empty($postData->get('targetSituation'))) {
                $situation = $this->getDoctrine()->getRepository(Situation::class)
                    ->findOneBy([
                        'id' => $postData->get('targetSituation'),
                        'user' => [$user, null]
                    ]);
                if($situation == null){
                    $this->createNotFoundException('Desired situation not found');
                }
                $task->setSituation($situation);
            }

            if(!empty($postData->get('duedate'))) {
                $date = $postData->get('duedate');
                $date = \DateTime::createFromFormat('Y-m-d', $date);
                $task->setDuedate($date);
            }
            $task->setName($postData->get('name'));
            $task->setDescription($postData->get('description'));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($task);
            $entityManager->flush();

            $message = "Task edited successfully";
            return new JsonResponse(compact('message'));
        } catch (ValidationException $e) {
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 500);
        } catch (NotFoundHttpException $e) {
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 500);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $message = $e->getMessage(); // or There was an error while updating your task.
            return new JsonResponse(compact('message'), 500);
        }
    }

    /**
     * @Route("/api/tasks/{id}", methods={"DELETE"})
     */
    public function delete($id)
    {
        try {
            $user = $this->getUser();
            $task = $this->getDoctrine()->getRepository(Task::class)
                ->findOneBy([
                    'id' => $id,
                    'user' => $user
                ]);

            if (!$task) {
                throw $this->createNotFoundException('Task not found');
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($task);
            $entityManager->flush();

            return $this->redirectToRoute('app_tasks_index');
        } catch (NotFoundHttpException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_tasks_index');
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 500);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $this->addFlash('error', 'There was an error while deleting your task.');
            return $this->redirectToRoute('app_tasks_index');
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 500);
        }
    }

    /**
     * @Route("/api/tasks", methods={"GET", "HEAD"})
     */
    public function index()
    {
        try {
            $user = $this->getUser();

            $tasks = $this->getDoctrine()
                ->getRepository(Task::class)
                ->findBy([
                    'user' => $user,
                    // 'situation' => null,
                ],['created_at' => 'DESC'] //orderBy
            );

            $situations = $this->getDoctrine()->getRepository(Situation::class)
                ->findAll();

            $title='Inbox';
            $subtitle='declutter your mind here';

            return new JsonResponse(compact('tasks', 'situations'));
            
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            // throw $e;
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 500);
        }
    }

    /**
     * @Route("/api/tasks/{id}/completeTask", methods={"POST"})
     */
    public function completeTask($id) {
        try {
            $user = $this->getUser();
            $task = $this->getDoctrine()->getRepository(Task::class)
                ->findOneBy(['id' => $id, 'user' => $user]);
            if($task == null){
                $this->createNotFoundException("Task not found");
            }

            $task->setCompleted(true);
            $em = $this->getDoctrine()->getManager();
            $em->persist($task);
            $em->flush();

            $message = "Tasks marked as completed";
            return new JsonResponse(compact('message'));
        } catch (NotFoundHttpException $e) {
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 500);
        } catch (\Exception $e) {
            $message = "There was an error while trying to complete this task.";//$e->getMessage();
            return new JsonResponse(compact('message'), 500);
            // Log::error($e->getMessage());
        }
    }

    /**
     * @Route("/api/tasks/{id}/taskToProject", methods={"POST"})
     */
    public function taskToProject($id){
        try {
            $user = $this->getUser();
            $task = $this->getDoctrine()->getRepository(Task::class)
                ->findOneBy(['id' => $id, 'user' => $user]);
            if($task == null){
                $this->createNotFoundException("Task not found");
            }

            $project = new Project();
            $project->setName($task->getName());
            $project->setDescription($task->getDescription());
            $project->setDuedate($task->getDuedate());
            $project->setUser($user);

            $em = $this->getDoctrine()->getManager();
            $em->persist($project);
            $em->remove($task);
            $em->flush();

            $message = "Task successfully transformed in project";
            return new JsonResponse(compact('message'));
        } catch (NotFoundHttpException $e) {
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 500);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 500);
            // Log::error($e->getMessage());
        }
    }
}
