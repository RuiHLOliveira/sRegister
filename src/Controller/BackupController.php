<?php

namespace App\Controller;

use App\Entity\Note;
use App\Entity\Notebook;
use DateTime;
use DateTimeZone;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class BackupController extends AbstractController
{
    /**
     * @Route("/api/backup/export", name="backupExport")
     */
    public function index(Request $request): Response
    {
        $user = $this->getUser();

        $notebooks = $this->getDoctrine()
            ->getRepository(Notebook::class)
            ->findBy(['user' => $user]);

        foreach ($notebooks as $key => $notebook) {
            $notebook->fullSerialize = true;
        }

        return new JsonResponse(compact('notebooks'), 200);
    }

    /**
     * @Route("/api/backup/import", name="backupImport")
     */
    public function import(Request $request): Response
    {
        $user = $this->getUser();

        $file = $request->files->get('file');
        $mimetype = $file->getClientMimeType();
        $path = $file->getPathname();

        $data = file_get_contents($path);
        $data = json_decode($data,true);

        $entityManager = $this->getDoctrine()->getManager();

        foreach ($data['notebooks'] as $key => $notebook) {

            $notebook['name'] .= ' bkp'; //padrão backup

            $notebookObj = new Notebook();
            $notebookObj->setName($notebook['name']);
            
            $timezone = new DateTimeZone($notebook['created_at']['timezone']);
            $created_at = new DateTime($notebook['created_at']['date'], $timezone);
            $notebookObj->setCreatedAt($created_at);

            $timezone = new DateTimeZone($notebook['updated_at']['timezone']);
            $updated_at = new DateTime($notebook['updated_at']['date'], $timezone);
            $notebookObj->setUpdatedAt($updated_at);

            $notebookObj->setUser($user);

            $entityManager->persist($notebookObj);
            $entityManager->flush();

            $notebookObj->getId();

            foreach ($notebook['notes'] as $key => $note) {
                
                $note['name'] .= ' bkp'; //padrão backup

                $noteObj = new Note();
                $noteObj->setName($note['name']);
                $noteObj->setContent($note['content']);

                $timezone = new DateTimeZone($note['created_at']['timezone']);
                $created_at = new DateTime($note['created_at']['date'], $timezone);
                $noteObj->setCreatedAt($created_at);
    
                $timezone = new DateTimeZone($note['updated_at']['timezone']);
                $updated_at = new DateTime($note['updated_at']['date'], $timezone);
                $noteObj->setUpdatedAt($updated_at);
                
                $noteObj->setNotebook($notebookObj);
                $noteObj->setUser($user);

                $entityManager->persist($noteObj);
                $entityManager->flush();
            }

        }

        $mensagem = "Backup successfully restored";

        return new JsonResponse(compact('mensagem'), 200);
    }
}
