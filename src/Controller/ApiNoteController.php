<?php

namespace App\Controller;

use \Exception;
use App\Entity\Note;
use DateTimeImmutable;
use App\Entity\Notebook;
use App\Entity\Situation;
use App\Exception\InternalServerErrorHttpException;
use App\Exception\ValidationException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ApiNoteController extends AbstractController
{
    /**
     * @Route("/api/{notebook}/notes", methods={"POST"})
     */
    public function store(Request $request, $notebook)
    {
        try {
            $postData = json_decode($request->getContent(),true);

            if(!isset($postData['content']) || $postData['content'] === null || $postData['content'] === ''){
                throw new BadRequestHttpException('Content is needed');
            }

            $note = new Note();
            $note->setName(isset($postData['name']) && $postData['name'] != null ? $postData['name'] : '');
            $note->setContent($postData['content']);
            $note->setUser($this->getUser());
            $note->setCreatedAt(new DateTimeImmutable());
            $note->setUpdatedAt(new DateTimeImmutable());

            $notebook = $this->getDoctrine()->getRepository(Notebook::class)
                ->findOneBy([
                'user' => $this->getUser(),
                'id' => $notebook
            ]);

            if($notebook == null){
                throw new NotFoundHttpException("Notebook not found");
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
        } catch (Exception $e) {
            throw new InternalServerErrorHttpException($e->getMessage());
        }
    }

    /**
     * @Route("/api/{notebook}/notes/{id}", methods={"PUT","PATCH"})
     */
    public function update(Request $request, $notebook, $id)
    {
        try {
            $postData = json_decode($request->getContent(),true);

            //validating
            if($postData['content'] === null){
                throw new BadRequestHttpException('Content is needed');
            }

            $notebook = $this->getDoctrine()->getRepository(Notebook::class)
                ->findOneBy([
                'user' => $this->getUser(),
                'id' => $notebook
            ]);

            if($notebook == null){
                throw new NotFoundHttpException("Notebook not found");
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
                throw new NotFoundHttpException('Note not found');
            }

            $note->setName(isset($postData['name']) && $postData['name'] != null ? $postData['name'] : '');
            $note->setContent($postData['content']);
            $note->setUpdatedAt(new DateTimeImmutable());
            
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($note);
            $entityManager->flush();

            $message = "Note edited successfully";
            return new JsonResponse(compact('message'));
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            throw new InternalServerErrorHttpException($e->getMessage());
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
                throw new NotFoundHttpException("Notebook not found");
            }

            $note = $this->getDoctrine()->getRepository(Note::class)
                ->findOneBy([
                    'id' => $id,
                    'notebook' => $notebook,
                    'user' => $this->getUser()
                ]);

            if (!$note) {
                throw new NotFoundHttpException('Note not found');
            }

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($note);
            $entityManager->flush();

            $message = "Note deleted successfully";
            return new JsonResponse(compact('message'));
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            throw new InternalServerErrorHttpException($e->getMessage());
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
                throw new NotFoundHttpException("Notebook not found");
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

            return new JsonResponse(compact('notes'));
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            throw new InternalServerErrorHttpException($e->getMessage());
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
                throw new NotFoundHttpException("Notebook not found");
            }

            $note = $this->getDoctrine()->getRepository(Note::class)
                ->findOneBy(['id' => $id, '' => $notebook, 'user' => $user]);

            if($note == null){
                throw new NotFoundHttpException("Note not found");
            }
            
            return new JsonResponse(compact('note'));
        } catch (\Exception $e) {
            // Log::error($e->getMessage());
            throw new InternalServerErrorHttpException($e->getMessage());
        }
    }
}
