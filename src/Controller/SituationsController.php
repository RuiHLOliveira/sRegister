<?php

namespace App\Controller;

use App\Entity\Situation;
use App\Entity\Task;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Request;
use App\Exception\ValidationException;
use LogicException;

class SituationsController extends AbstractController
{
    /**
     * @Route("/situations/", methods={"GET","HEAD"})
     */
    public function index()
    {
        $user = $this->getUser();
        $situations = $this->getDoctrine()->getRepository(Situation::class)
            ->findBy(['user' => [$user, null]],['user' => 'desc']
        );
        $title = "Situations";
        $subtitle = "your situation list";
        return $this->render('situation/index.html.twig',compact(
            'title',
            'subtitle',
            'situations'
        ));
    }

    /**
     * @Route("/situations/create", methods={"GET","HEAD"})
     */
    public function create ()
    {
        $title = 'New Situation';
        $subtitle = '';
        return $this->render('situation/create.html.twig',compact(
            'title','subtitle'
        ));
    }

    /**
     * @Route("/situations", methods={"POST"})
     */
    public function store(Request $request)
    {
        try {
            $postData = $request->request;

            if($postData->get('situationName') === null || $postData->get('situationName') === ''){
                throw new ValidationException('Name of the new situation is needed');
            }

            $situation = new Situation();
            $situation->setSituation($postData->get('situationName'));
            $situation->setUser($this->getUser());
            
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($situation);
            $entityManager->flush();

            $this->addFlash('success', 'Situation was created');
            return $this->redirectToRoute('app_situations_index');

        } catch (ValidationException $e) {
            // Log::error($e->getMessage());
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_situations_create');
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            throw $e;
            $this->addFlash('error', 'There was an error while storing your situation.');
            return $this->redirectToRoute('app_situations_create');
        }
    }

    /**
     * @Route("/situations/{id}",methods={"GET","HEAD"})
     */
    public function edit($id)
    {
        try {
            $user = $this->getUser();

            $situation = $this->getDoctrine()->getRepository(Situation::class)
                ->findOneBy(['id' => $id, 'user' => $user]);

            if($situation == null) {
                $this->createNotFoundException("Situation Not Found");
            }
            return $this->render('situation/edit.html.twig', [
                'title' => 'Edit Situation',
                'subtitle' => 'change info about your situation',
                'situation' => $situation,
            ]);
            
        } catch (NotFoundHttpException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_situations_index');
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            throw $e;
            $this->addFlash('error','There was an error while getting your situation.');
            return $this->redirectToRoute('app_situations_index');
        }
    }

    /**
     * @Route("/situations/{id}", methods={"PUT"})
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $this->getUser();
            $postData = $request->request;

            if($postData->get('situationName') === null || $postData->get('situationName') === ''){
                throw new ValidationException('Name of the new situation is needed');
            }

            $situation = $this->getDoctrine()->getRepository(Situation::class)
                ->findOneBy([
                    'id' => $id,
                    'user' => $user
            ]);
            
            if($situation == null) {
                $this->createNotFoundException("Situation Not Found");
            }
            $situation->setSituation($postData->get('situationName'));

            $em = $this->getDoctrine()->getManager();
            $em->persist($situation);
            $em->flush();

            $this->addFlash('success','Situation Updated');
            return $this->redirectToRoute('app_situations_index');

        } catch (NotFoundHttpException $e) {
            $this->addFlash('error',$e->getMessage());
            return $this->redirectToRoute('app_situations_edit',['id'=>$id]);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $this->addFlash('error', 'There was an error while updating your situation.');
            return $this->redirectToRoute("app_situations_edit");
        }
    }

    /**
     * @Route("/situations/{id}", methods={"DELETE"})
     */
    public function destroy($id)
    {
        try {
            $user = $this->getUser();

            $situation = $this->getDoctrine()->getRepository(Situation::class)
                ->findOneBy([
                'user' => $user,
                'id' => $id
            ]);
            if($situation == null) {
                $this->createNotFoundException("Situation Not Found");
            }

            $tasks = $this->getDoctrine()->getRepository(Task::class)
                ->findBy([
                    'user' => $user,
                    'situation' => $situation->getId()
                ]);
            if(!empty($tasks)) {
                throw new LogicException("Can't delete a situation used by a task.");
            }

            $em = $this->getDoctrine()->getManager();
            
            $em->remove($situation);
            $em->flush();
            
            $this->addFlash('success',"Situation removed");
            return $this->redirectToRoute('app_situations_index');

        } catch (NotFoundHttpException $e) {
            // throw $e;
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_situations_index');
        } catch (LogicException $e) {
            // throw $e;
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('app_situations_index');
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            throw $e;
            $this->addFlash('error','There was an error while deleting your situation.');
            return $this->redirectToRoute('app_situations_index');
        }
    }
}
