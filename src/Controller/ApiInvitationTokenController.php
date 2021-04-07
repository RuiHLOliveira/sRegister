<?php

namespace App\Controller;

use App\Entity\InvitationToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ApiInvitationTokenController extends AbstractController
{
    /**
     * @Route("/api/invitations/", methods={"GET"})
     */
    public function index()
    {
        $user = $this->getUser();
        $invitations = $this->getDoctrine()->getRepository(InvitationToken::class)
            ->findAll([
            'user' => $user
        ]);

        return new JsonResponse(compact('invitations'));

        // return $this->render('invitations/index.html.twig',[
        //     'title' => 'Invitations',
        //     'subtitle' => 'Invitations',
        //     'invitations' => $invitations
        // ]);
    }

    /**
     * @Route("/api/invitations/", methods={"POST"})
     */
    public function store(Request $request)
    {
        try {
            $postData = $request->request;
            $user = $this->getUser();
            if($postData->get('invitationToken') != ''){
                // strchr()
            }
            if(($postData->get('invitationToken') == '') || $postData->get('invitationToken') == ""){
                $random_bytes = rand(0,1000000);
                $postData->set('invitationToken', $random_bytes);
            }
            $invToken = new InvitationToken();
            $invToken->setUser($user);
            $invToken->setInvitationToken($postData->get('invitationToken'));

            $em = $this->getDoctrine()->getManager();
            $em->persist($invToken);
            $em->flush();
            
            $invToken = $this->getDoctrine()->getRepository(InvitationToken::class)
                ->findOneBy([
                'user' => $this->getUser(),
                'id' => $invToken->getId()
            ]);

            return new JsonResponse(compact('invToken'), 201);
            // $this->addFlash('success','Invitation Token generated successfully!');
            // return $this->redirectToRoute('app_invitationtoken_index');
        } catch (\Exception $e) {
            $message = "There was an error while storing your invitation token.";
            $message = $e->getMessage(); // or There was an error while storing your task.
            return new JsonResponse(compact('message'), 500);
            // throw $e;
            // $this->addFlash('error', 'There was an error while storing your invitation token.');
            // return $this->redirectToRoute('app_invitationtoken_create');
        }
    }
}
