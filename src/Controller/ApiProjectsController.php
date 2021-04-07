<?php

namespace App\Controller;

use App\Entity\Task;
use App\Entity\Project;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiProjectsController extends AbstractController
{
    /**
     * @Route("/api/projects/", methods={"GET","HEAD"})
     */
    public function index()
    {
        try {
            $user = $this->getUser();

            $projects = $this->getDoctrine()->getRepository(Project::class)
                ->findBy(['user' => $user]);

            return new JsonResponse(compact('projects'));

        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            // throw $e;
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 500);
        }
    }

    /**
     * @Route("/api/projects/{id}", methods={"PUT"})
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $this->getUser();
            $postData = $request->request;

            $project = $this->getDoctrine()->getRepository(Project::class)
                ->findOneBy([
                    'id' => $id,
                    'user' => $user
            ]);
            
            if($project == null) {
                $this->createNotFoundException("Project Not Found");
            }
            dump($request);
            dump($postData->get('name'));
            $project->setName($postData->get('name'));
            $project->setDescription($postData->get('description'));
            if($postData->get('duedate') !== '' && $postData->get('duedate') !== null) { 
                dump($postData->get('duedate'));
                $duedate = \DateTime::createFromFormat('Y-m-d', $postData->get('duedate'));
                $project->setDuedate($duedate);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($project);
            $em->flush();

            return new JsonResponse(compact('project'));
            // $this->addFlash('success','Project Updated');
            // return $this->redirectToRoute('app_projects_index');
        } catch (NotFoundHttpException $e) {
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 404);
            $this->addFlash('error',$e->getMessage());
            return $this->redirectToRoute('app_projects_edit',['id'=>$id]);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $message = "There was an error while updating your project.";
            return new JsonResponse(compact('message'), 500);
            // $this->addFlash('error', 'There was an error while updating your project.');
            // return $this->redirectToRoute("app_projects_edit");
        }
    }

    /**
     * @Route("/api/projects/{id}", methods={"DELETE"})
     */
    public function destroy($id)
    {
        try {
            $user = $this->getUser();

            $project = $this->getDoctrine()->getRepository(Project::class)
                ->findOneBy([
                'user' => $user,
                'id' => $id
            ]);
            if($project == null) {
                $this->createNotFoundException("Project Not Found");
            }

            $tasks = $this->getDoctrine()->getRepository(Task::class)
                ->findBy([
                    'user' => $user,
                    'project' => $project
                ]);
            
            $em = $this->getDoctrine()->getManager();
            
            //deleta o vinculo das tarefas filhas
            foreach ($tasks as $key => $task) {
                $tasks[$key]->setProject(null);
                $em->persist($tasks[$key]);
            }
            $em->remove($project);
            $em->flush();
            
            return new JsonResponse();
            // $this->addFlash('success',"Project removed");
            // return $this->redirectToRoute('app_projects_index');
        } catch (NotFoundHttpException $e) {
            // throw $e;
            $message = $e->getMessage();
            return new JsonResponse(compact('message'),404);
            // $this->addFlash('error', $e->getMessage());
            // $this->redirectToRoute('app_projects_index');
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $message = 'There was an error while deleting your project.';
            return new JsonResponse(compact('message'),500);
            // throw $e;
            // $this->addFlash('error',);
            // return $this->redirectToRoute('app_projects_index');
        }
    }

    /**
     * @Route("/api/projects/{id}/completeProject/", methods={"POST"})
     */
    public function completeProject($id)
    {
        try {
            $user = $this->getUser();
            $project = $this->getDoctrine()->getRepository(Project::class)
                ->findOneBy([
                'id' => $id,
                'user' => $user
            ]);
            if($project == null) {
                $this->createNotFoundException("Project Not Found");
            }
            $project->setCompleted(true);
            $em = $this->getDoctrine()->getManager();
            $em->persist($project);
            $em->flush();

            return new JsonResponse(compact('project'));
            // $this->addFlash('success', 'Project Completed');
            // return $this->redirectToRoute('app_projects_index');
        } catch (NotFoundHttpException $e) {
            $message = $e->getMessage();
            return new JsonResponse(compact('message'),404);
            // $this->addFlash('error', $e->getMessage());
            // return $this->redirectToRoute('app_projects_edit', ['id'=>$id]);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $message = 'There was an error while setting your project as completed.';
            return new JsonResponse(compact('message'),500);
            // $this->addFlash('error',);
            // return $this->redirectToRoute('app_projects_edit', ['id'=>$id]);
        }
    }
}
