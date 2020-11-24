<?php

namespace App\Controller;

use App\Entity\InvitationToken;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

class InvitationTokenController extends AbstractController
{
    /**
     * @Route("/invitations/", methods={"GET"})
     */
    public function index()
    {
        $user = $this->getUser();
        $invitations = $this->getDoctrine()->getRepository(InvitationToken::class)
            ->findAll([
            'user' => $user
        ]);
        return $this->render('invitations/index.html.twig',[
            'title' => 'Invitations',
            'subtitle' => 'Invitations',
            'invitations' => $invitations
        ]);
    }

    /**
     * @Route("/invitations/create", methods={"GET"})
     */
    public function create()
    {
        return $this->render('invitations/create.html.twig',[
            'title' => 'New Invitation',
            'subtitle' => 'something'
        ]);
    }

    /**
     * @Route("/invitations/",methods={"POST"})
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
            $this->addFlash('success','Invitation Token generated successfully!');
            return $this->redirectToRoute('app_invitationtoken_index');
        } catch (\Exception $e) {
            throw $e;
            $this->addFlash('error', 'There was an error while storing your invitation token.');
            return $this->redirectToRoute('app_invitationtoken_create');
        }
    }
}
