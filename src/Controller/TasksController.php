<?php

namespace App\Controller;

use App\Entity\Task;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

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
                $this->addFlash('error', 'Name is needed');
                return $this->redirectToRoute('app_tasks_create');
            }

            $task = new Task();
            $task->setName($postData->get('name'));
            // $task->user_id = $request->user()->id;

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

            dd($task);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $this->addFlash('error', 'There was an error while storing your task.');
            return $this->redirectToRoute('app_tasks_create');
        }
    }

    /**
     * @Route("/tasks", methods={"GET", "HEAD"})
     */
    public function index()
    {
        try {
            // $user_id = request()->user()->id;

            // $tasks = Task::where('user_id',$user_id)
            //     ->whereNull('situation_id')
            //     ->orderBy('created_at','desc')
            //     ->get();

            $tasks = $this->getDoctrine()
                ->getRepository(Task::class)
                ->findAll();

            if (!$tasks) {
                throw $this->createNotFoundException(
                    'No tasks found.'
                );
            }

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
