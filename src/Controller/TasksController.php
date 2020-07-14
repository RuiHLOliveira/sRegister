<?php

namespace App\Controller;

use App\Entity\Task;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use \Exception;
use App\Exception\ValidationException;

class TasksController extends AbstractController
{
    /**
     * @Route("/tasks/create", methods={"GET","HEAD"})
     */
    public function create ()
    {
        $title = 'New Task';
        $subtitle = '';
        return $this->render('task/create.html.twig',compact(
            'title','subtitle'
        ));
    }

    /**
     * @Route("/tasks", methods={"POST"})
     */
    public function store(Request $request)
    {
        try {
            $postData = $request->request;

            //validating
            if($postData->get('name') === null || $postData->get('name') === ''){
                throw new ValidationException('Name is needed');
            }

            $task = new Task();
            $task->setName($postData->get('name'));
            $task->setUserId($this->getUser());

            // if (isset($data['project']) ) {
            //     $project = Project::where([
            //         'user_id' => $task->user_id,
            //         'id' => $data['project']
            //     ])->first();
            //     if($project == null){
            //         throw new NoResultException("Project not found", 1);//ver esse
            //     }
            //     $task->project_id = $project->id;
            // }
            
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($task);
            $entityManager->flush();

            $this->addFlash('success', 'Task was created');
            return $this->redirectToRoute('app_tasks_index');

        } catch (ValidationException $e) {
            // Log::error($e->getMessage());
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_tasks_create');
        } catch (Exception $e) {
            // Log::error($e->getMessage());
            $this->addFlash('error', 'There was an error while storing your task.');
            return $this->redirectToRoute('app_tasks_create');
        }
    }

    /**
     * @Route("/tasks/{id}", methods={"GET","HEAD"})
     */
    public function show($id)
    {
        try {
            $user = $this->getUser();
            $task = $this->getDoctrine()->getRepository(Task::class)
                ->findBy(['id' => $id, 'user_id' => $user->getId()]);
            if($task == null){
                $this->createNotFoundException("Task not found");
            }
            $task = $task[0];
            return $this->render('task/show.html.twig', [
                'title' => 'Details',
                'subtitle' => "about your item",
                'task' => $task
            ]);
        } catch (NotFoundHttpException $e) {
            $this->addFlash('error', 'Task not found.');
            return $this->redirectToRoute('app_tasks_index');
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $this->addFlash('error', 'There was an error in your task.');
            return $this->redirectToRoute('app_tasks_index');
        }
    }

    /**
     * @Route("/tasks/{id}/edit", methods={"GET"})
     */
    public function edit($id)
    {
        try {
            $user = $this->getUser();
            $task = $this->getDoctrine()
                ->getRepository(Task::class)
                ->findBy([
                    'id' => $id,
                    'user_id' => $user->getId()
                ]);
            if($task == null){
                throw $this->createNotFoundException ('Task not found');
            }
            $task = $task[0];

            // $situations = Situation::where('user_id', $user_id)->get();
            
            // $projects = Project::where([
            //     'user_id' => $user_id
            // ])->orderBy('created_at','desc')->get();
            
            return $this->render('task/edit.html.twig', [
                'title' => 'Edit Task',
                'subtitle' => 'edit your task',
                'task' => $task,
                // 'situations' => $situations,
                // 'projects' => $projects
            ]);
        } catch (NotFoundHttpException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_tasks_index');
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            throw $e;
            
            $this->addFlash('error', 'There was an error in your task.');
            return $this->redirectToRoute('app_tasks_index');
        }
    }

    /**
     * @Route("/tasks/{id}", methods={"PUT","PATCH"})
     */
    public function update(Request $request, $id)
    {
        try {
            
            $postData = $request->request;

            //validating
            if($postData->get('name') === null || $postData->get('name') === ''){
                throw new ValidationException('Name is needed');
            }

            $user = $this->getUser();
            $task = $this->getDoctrine()
                ->getRepository(Task::class)
                ->findBy([
                    'id' => $id,
                    'user_id' => $user->getId()
                ]);
            if($task == null){
                $this->createNotFoundException('Task not found');
            }
            $task = $task[0];

            // if(isset($data['considerProjectForm']) 
            //     && $data['considerProjectForm'] == 1
            //     && isset($data['project'])
            //     && $data['project'] != ''
            // ) {
            //     $project = Project::where([
            //         'user_id' => $user_id,
            //         'id' => $data['project']
            //     ])->first();
            //     if($project == null){
            //         throw new NoResultException("Project not found", 1);
            //     }
            //     $task->project_id = $project->id;
            // }

            // if(isset($data['targetSituation'])) {
            //     $task->situation_id = $data['targetSituation'];
            // }
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
            
            // BackupManager::dumpDatabase('myregister');
            return $this->redirectToRoute('app_tasks_index');
        } catch (ValidationException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_tasks_edit',['id'=>$id]);
        } catch (NotFoundHttpException $e) {
            $this->addFlash('error', $e->getMessage());
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $this->addFlash('error','There was an error while updating your task.');
            $this->redirectToRoute('app_index_edit',['id'=>$id]);
        }
    }

    /**
     * @Route("/tasks/{id}", methods={"DELETE"})
     */
    public function delete($id)
    {
        try {
            $user = $this->getUser();
            $task = $this->getDoctrine()->getRepository(Task::class)
                ->findBy([
                    'id' => $id,
                    'user_id' => $user->getId()
                ]);

            if (!$task) {
                throw $this->createNotFoundException('Task not found');
            }
            $task = $task[0];

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($task);
            $entityManager->flush();

            return $this->redirectToRoute('app_tasks_index');
        } catch (NotFoundHttpException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_tasks_index');
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $this->addFlash('error', 'There was an error while deleting your task.');
            return $this->redirectToRoute('app_tasks_index');
        }
    }

    /**
     * @Route("/tasks", methods={"GET", "HEAD"})
     */
    public function index()
    {
        try {
            $user = $this->getUser();

            /**
             * Fazer filtros abaixo
             * ->whereNull('situation_id')
             * ->orderBy('created_at','desc')
             */
            $tasks = $this->getDoctrine()
                ->getRepository(Task::class)
                ->findBy([
                    'user_id' => $user->getId(),
                    // 'situation_id' => null,
                ],
                    // ['created_at' => 'ASC']
                );

            $title='Inbox';
            $subtitle='declutter your mind here';
            return $this->render('task/index.html.twig',compact(
                'tasks', 'title', 'subtitle'
            ));

        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            throw $e;
            $this->addFlash('error', 'There was an error while getting your tasks.');
            return $this->redirectToRoute('app_tasks_create');
        }
    }

}
