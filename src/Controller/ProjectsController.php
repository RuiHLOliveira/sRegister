<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;

class ProjectsController extends AbstractController
{
    /**
     * @Route("/projects/", methods={"GET","HEAD"})
     */
    public function index()
    {
        $user = $this->getUser();
        $projects = $this->getDoctrine()->getRepository(Project::class)
            ->findBy(['user' => $user]);
        $title = "Projects";
        $subtitle = "your project list";
        return $this->render('project/index.html.twig',compact(
            'title',
            'subtitle',
            'projects'
        ));
    }

    /**
     * @Route("/projects/{id}",methods={"GET","HEAD"})
     */
    public function edit($id)
    {
        try {
            $user = $this->getUser();

            $project = $this->getDoctrine()->getRepository(Project::class)
                ->findOneBy(['id' => $id, 'user' => $user]);

            if($project == null) {
                $this->createNotFoundException("Project Not Found");
            }
            return $this->render('project/edit.html.twig', [
                'title' => 'Edit Project',
                'subtitle' => 'change info about your project',
                'project' => $project,
            ]);
            
        } catch (NotFoundHttpException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_projects_index');
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $this->addFlash('error','There was an error while getting your project.');
            return $this->redirectToRoute('app_projects_index');
        }
    }

    /**
     * @Route("/projects/{id}", methods={"PUT"})
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
            $project->setName($postData->get('name'));
            $project->setDescription($postData->get('description'));
            if($postData->get('duedate') !== '') { 
                $duedate = \DateTime::createFromFormat('Y-m-d', $postData->get('duedate'));
                $project->setDuedate($duedate);
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($project);
            $em->flush();

            $this->addFlash('success','Project Updated');
            return $this->redirectToRoute('app_projects_index');
        } catch (NotFoundHttpException $e) {
            $this->addFlash('error',$e->getMessage());
            return $this->redirectToRoute('app_projects_edit',['id'=>$id]);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $this->addFlash('error', 'There was an error while updating your project.');
            return $this->redirectToRoute("app_projects_edit");
        }
    }

    /**
     * @Route("/projects/{id}", methods={"DELETE"})
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
            
            $this->addFlash('success',"Project removed");
            return $this->redirectToRoute('app_projects_index');
        } catch (NotFoundHttpException $e) {
            
            throw $e;
            $this->addFlash('error', $e->getMessage());
            $this->redirectToRoute('app_projects_index');
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            throw $e;
            $this->addFlash('error','There was an error while deleting your project.');
            return $this->redirectToRoute('app_projects_index');
        }
    }

    /**
     * @Route("/projects/{id}/completeProject/", methods={"POST"})
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

            $this->addFlash('success', 'Project Completed');
            return $this->redirectToRoute('app_projects_index');
        } catch (NotFoundHttpException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_projects_edit', ['id'=>$id]);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $this->addFlash('error','There was an error while setting your project as completed.');
            return $this->redirectToRoute('app_projects_edit', ['id'=>$id]);
        }
    }
}
