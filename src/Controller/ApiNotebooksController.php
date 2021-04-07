<?php

namespace App\Controller;

use App\Entity\Notebook;
use App\Exception\ValidationException;
use DateTimeImmutable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiNotebooksController extends AbstractController
{
    /**
     * @Route("/api/notebooks/", methods={"GET","HEAD"})
     */
    public function index()
    {
        try {
            $user = $this->getUser();

            $notebooks = $this->getDoctrine()
            ->getRepository(Notebook::class)
            ->findBy(['user' => $user]);

            return new JsonResponse(compact('notebooks'));

        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            // throw $e;
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 500);
        }
    }

    /**
     * @Route("/api/notebooks", methods={"POST"})
     */
    public function store(Request $request)
    {
        try {
            $requestData = json_decode($request->getContent());

            if($requestData->name === null || $requestData->name === ''){
                throw new ValidationException('Name is needed');
            }

            $notebook = new Notebook();
            $notebook->setName($requestData->name);
            $notebook->setCreatedAt(new DateTimeImmutable());
            $notebook->setUpdatedAt(new DateTimeImmutable());
            $notebook->setUser($this->getUser());

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($notebook);
            $entityManager->flush();

            $notebook = $this->getDoctrine()->getRepository(Notebook::class)
                ->findOneBy([
                'user' => $this->getUser(),
                'id' => $notebook->getId()
            ]);

            return new JsonResponse(compact('notebook'), 201);
        } catch (ValidationException $e) {
            // Log::error($e->getMessage());
            throw $e;
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 400);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            throw $e;
            $message = $e->getMessage(); // or There was an error while storing your notebook.
            return new JsonResponse(compact('message'), 500);
        }
    }

    /**
     * @Route("/api/notebooks/{id}", methods={"PUT"})
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $this->getUser();
            $postData = $request->request;

            $notebook = $this->getDoctrine()->getRepository(Notebook::class)
                ->findOneBy([
                    'id' => $id,
                    'user' => $user
            ]);
            
            if($notebook == null) {
                $this->createNotFoundException("Notebook Not Found");
            }

            $notebook->setName($postData->get('name'));
            $notebook->setUpdatedAt(new DateTimeImmutable());

            $em = $this->getDoctrine()->getManager();
            $em->persist($notebook);
            $em->flush();

            return new JsonResponse(compact('notebook'));
        } catch (NotFoundHttpException $e) {
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 404);
            $this->addFlash('error',$e->getMessage());
            return $this->redirectToRoute('app_notebooks_edit',['id'=>$id]);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $message = "There was an error while updating your notebook.";
            return new JsonResponse(compact('message'), 500);
        }
    }

    /**
     * @Route("/api/notebooks/{id}", methods={"DELETE"})
     */
    public function destroy($id)
    {
        try {
            $user = $this->getUser();

            $notebook = $this->getDoctrine()->getRepository(Notebook::class)
                ->findOneBy([
                'user' => $user,
                'id' => $id
            ]);
            if($notebook == null) {
                $this->createNotFoundException("Notebook Not Found");
            }

            $em = $this->getDoctrine()->getManager();
            
            $em->remove($notebook);
            $em->flush();
            
            return new JsonResponse();
        } catch (NotFoundHttpException $e) {
            // throw $e;
            $message = $e->getMessage();
            return new JsonResponse(compact('message'),404);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $message = 'There was an error while deleting your notebook.';
            return new JsonResponse(compact('message'),500);
            // throw $e;
        }
    }
}
