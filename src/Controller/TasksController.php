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
            throw $e;
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
                ->findOneBy(['id' => $id, 'user' => $user]);
            if($task == null){
                $this->createNotFoundException("Task not found");
            }
            
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
                ->findOneBy([
                    'id' => $id,
                    'user' => $user
                ]);
            if($task == null){
                throw $this->createNotFoundException ('Task not found');
            }

            $projects = $this->getDoctrine()->getRepository(Project::class)
                ->findBy([
                'user' => $user
                ],['created_at' => 'DESC'] //orderBy
            );
            
            return $this->render('task/edit.html.twig', [
                'title' => 'Edit Task',
                'subtitle' => 'edit your task',
                'task' => $task,
                // 'situations' => $situations,
                'projects' => $projects
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
                    ->findOneBy(['id' => $postData->get('targetSituation')]);
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
            
            return $this->redirectToRoute('app_tasks_index');
        } catch (ValidationException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_tasks_edit',['id'=>$id]);
        } catch (NotFoundHttpException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_tasks_edit',['id'=>$id]);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            throw $e;
            $this->addFlash('error','There was an error while updating your task.');
            return $this->redirectToRoute('app_tasks_edit',['id'=>$id]);
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

            $tasks = $this->getDoctrine()
                ->getRepository(Task::class)
                ->findBy([
                    'user' => $user,
                    'situation' => null,
                ],['created_at' => 'DESC'] //orderBy
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

    /**
     * @Route("/tickler", methods={"GET", "HEAD"})
     */
    public function tickler()
    {
        try {
            $user = $this->getUser();

            $tasks = $this->getDoctrine()
                ->getRepository(Task::class)
                ->findBy([
                    'user' => $user,
                    'situation' => '1',
                ],['created_at' => 'DESC'] //orderBy
            );

            $title='Tickler';
            $subtitle='stuff you need to remember today';
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

    /**
     * @Route("/waitingfor", methods={"GET", "HEAD"})
     */
    public function waitingfor()
    {
        try {
            $user = $this->getUser();

            $tasks = $this->getDoctrine()
                ->getRepository(Task::class)
                ->findBy([
                    'user' => $user,
                    'situation' => '2',
                ],['created_at' => 'DESC'] //orderBy
            );

            $title='Waiting For';
            $subtitle="waiting someone's callback";
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

    /**
     * @Route("/recurring", methods={"GET", "HEAD"})
     */
    public function recurring()
    {
        try {
            $user = $this->getUser();

            $tasks = $this->getDoctrine()
                ->getRepository(Task::class)
                ->findBy([
                    'user' => $user,
                    'situation' => '3',
                ],['created_at' => 'DESC'] //orderBy
            );

            $title='Recurring';
            $subtitle="tasks you do everyday";
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

    /**
     * @Route("/next", methods={"GET", "HEAD"})
     */
    public function next()
    {
        try {
            $user = $this->getUser();

            $tasks = $this->getDoctrine()
                ->getRepository(Task::class)
                ->findBy([
                    'user' => $user,
                    'situation' => '4',
                ],['created_at' => 'DESC'] //orderBy
            );

            $title='Next';
            $subtitle="next actions you need to do";
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

    /**
     * @Route("/readlist", methods={"GET", "HEAD"})
     */
    public function readlist()
    {
        try {
            $user = $this->getUser();

            $tasks = $this->getDoctrine()
                ->getRepository(Task::class)
                ->findBy([
                    'user' => $user,
                    'situation' => '5',
                ],['created_at' => 'DESC'] //orderBy
            );

            $title='Reading List';
            $subtitle="articles, videos and stuff you want to read/watch";
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

    /**
     * @Route("/somedaymaybe", methods={"GET", "HEAD"})
     */
    public function somedaymaybe()
    {
        try {
            $user = $this->getUser();

            $tasks = $this->getDoctrine()
                ->getRepository(Task::class)
                ->findBy([
                    'user' => $user,
                    'situation' => '6',
                ],['created_at' => 'DESC'] //orderBy
            );

            $title='Someday/Maybe';
            $subtitle="things you want to do someday, but not week... or this month... or this year";
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

    /**
     * @Route("/tasks/{id}/completeTask", methods={"POST"})
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
            $this->addFlash('success','Tasks marked as completed');
            return $this->redirectToRoute('app_tasks_index');
        } catch (NotFoundHttpException $e) {
            $this->addFlash('error',$e->getMessage());
            return $this->redirectToRoute('app_tasks_edit',['id' => $id]);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $this->addFlash('error','There was an error while trying to complete this task.');
            return $this->redirectToRoute('app_tasks_edit',['id' => $id]);
        }
    }

    /**
     * @Route("/tasks/{id}/taskToProject", methods={"POST"})
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
            $this->addFlash('success',"Task transformed in project successfully");
            return $this->redirectToRoute('app_tasks_index');
        } catch (NotFoundHttpException $e) {
            $this->addFlash('error',$e->getMessage());
            return $this->redirectToRoute('app_tasks_edit',['id' => $id]);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            throw $e;
            $this->addFlash('error','There was an error while converting this task to project.');
            return $this->redirectToRoute('app_tasks_edit',['id' => $id]);
        }
    }
}
