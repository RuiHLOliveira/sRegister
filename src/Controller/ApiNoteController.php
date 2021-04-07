<?php

namespace App\Controller;

use \Exception;
use App\Entity\Note;
use App\Entity\Notebook;
use App\Entity\Situation;
use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiNoteController extends AbstractController
{
    /**
     * @Route("/api/{notebook}/notes", methods={"POST"})
     */
    public function store(Request $request, $notebook)
    {
        try {
            $postData = $request->request;

            if($postData->get('name') === null || $postData->get('name') === ''){
                throw new ValidationException('Name is needed');
            }

            $note = new Note();
            $note->setName($postData->get('name'));
            $note->setContent($postData->get('content'));
            $note->setUser($this->getUser());

            $notebook = $this->getDoctrine()->getRepository(Notebook::class)
                ->findOneBy([
                'user' => $this->getUser(),
                'id' => $notebook
            ]);

            if($notebook == null){
                $this->createNotFoundException("Notebook not found");
            }

            $note->setNotebook($notebook);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($note);
            $entityManager->flush();

            $note = $this->getDoctrine()->getRepository(Note::class)
                ->findOneBy([
                'user' => $this->getUser(),
                'id' => $note->getId()
            ]);

            return new JsonResponse(compact('note'), 201);
        } catch (ValidationException $e) {
            // Log::error($e->getMessage());
            throw $e;
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 400);
        } catch (Exception $e) {
            // Log::error($e->getMessage());
            throw $e;
            $message = $e->getMessage(); // or There was an error while storing your note.
            return new JsonResponse(compact('message'), 500);
        }
    }

    /**
     * @Route("/api/{notebook}/notes/{id}", methods={"GET","HEAD"})
     */
    public function show($id, $notebook)
    {
        try {
            $user = $this->getUser();

            $notebook = $this->getDoctrine()->getRepository(Notebook::class)
                ->findOneBy([
                'user' => $this->getUser(),
                'id' => $notebook
            ]);

            if($notebook == null){
                $this->createNotFoundException("Notebook not found");
            }

            $note = $this->getDoctrine()->getRepository(Note::class)
                ->findOneBy(['id' => $id, '' => $notebook, 'user' => $user]);

            if($note == null){
                $this->createNotFoundException("Note not found");
            }
            
            return new JsonResponse(compact('note'));
        } catch (NotFoundHttpException $e) {
            throw $e;
            $message = "Note not found";
            return new JsonResponse(compact('message'), 404);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            throw $e;
            $message = $e->getMessage(); // or There was an error in your note.
            return new JsonResponse(compact('message'), 500);
        }
    }

    /**
     * @Route("/api/{notebook}/notes/{id}", methods={"PUT","PATCH"})
     */
    public function update(Request $request, $notebook, $id)
    {
        try {
            $postData = $request->request;

            //validating
            if($postData->get('name') === null || $postData->get('name') === ''){
                throw new ValidationException('Name is needed');
            }
            //validating
            if($postData->get('content') === null){
                throw new ValidationException('Content is needed');
            }

            $notebook = $this->getDoctrine()->getRepository(Notebook::class)
                ->findOneBy([
                'user' => $this->getUser(),
                'id' => $notebook
            ]);

            if($notebook == null){
                $this->createNotFoundException("Notebook not found");
            }

            $user = $this->getUser();
            $note = $this->getDoctrine()
                ->getRepository(Note::class)
                ->findOneBy([
                    'id' => $id,
                    'notebook' => $notebook,
                    'user' => $user
                ]);

            if($note == null){
                $this->createNotFoundException('Note not found');
            }

            $note->setName($postData->get('name'));
            $note->setContent($postData->get('content'));

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($note);
            $entityManager->flush();

            $message = "Note edited successfully";
            return new JsonResponse(compact('message'));
        } catch (ValidationException $e) {
            throw $e;
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 500);
        } catch (NotFoundHttpException $e) {
            throw $e;
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 500);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            throw $e;
            $message = $e->getMessage(); // or There was an error while updating your note.
            return new JsonResponse(compact('message'), 500);
        }
    }

    /**
     * @Route("/api/{notebook}/notes/{id}", methods={"DELETE"})
     */
    public function delete($id, $notebook)
    {
        try {

            $notebook = $this->getDoctrine()->getRepository(Notebook::class)
                ->findOneBy([
                'user' => $this->getUser(),
                'id' => $notebook
            ]);

            if($notebook == null){
                $this->createNotFoundException("Notebook not found");
            }

            $note = $this->getDoctrine()->getRepository(Note::class)
                ->findOneBy([
                    'id' => $id,
                    'notebook' => $notebook,
                    'user' => $this->getUser()
                ]);

            if (!$note) {
                throw $this->createNotFoundException('Note not found');
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($note);
            $entityManager->flush();

            $message = "Note deleted successfully";
            return new JsonResponse(compact('message'));
            // return $this->redirectToRoute('app_notes_index');
        } catch (ValidationException $e) {
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 500);
        } catch (NotFoundHttpException $e) {
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 500);
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            $message = $e->getMessage(); // or There was an error while updating your note.
            return new JsonResponse(compact('message'), 500);
        }
    }

    /**
     * @Route("/api/{notebook}/notes", methods={"GET", "HEAD"})
     */
    public function index($notebook)
    {
        try {
            
            $notebook = $this->getDoctrine()->getRepository(Notebook::class)
                ->findOneBy([
                'user' => $this->getUser(),
                'id' => $notebook
            ]);

            if($notebook == null){
                $this->createNotFoundException("Notebook not found");
            }

            $notes = $this->getDoctrine()
                ->getRepository(Note::class)
                ->findBy([
                    'notebook' => $notebook,
                    'user' => $this->getUser(),
                ],['created_at' => 'DESC'] //orderBy
            );

            $situations = $this->getDoctrine()->getRepository(Situation::class)
                ->findAll();

            $title='Inbox';
            $subtitle='declutter your mind here';

            return new JsonResponse(compact('notes'));
            
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            // throw $e;
            throw $e;
            $message = $e->getMessage();
            return new JsonResponse(compact('message'), 500);
        }
    }
}
