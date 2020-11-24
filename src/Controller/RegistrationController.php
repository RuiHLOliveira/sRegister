<?php

namespace App\Controller;

use App\Entity\InvitationToken;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", methods={"POST"})
     */
    public function store(
        Request $request, 
        UserPasswordEncoderInterface $passwordEnconder)
    {
        try {
            $postData = $request->request;

            if($postData->get('invitation_token') == ''){
                throw new \Exception("You don't have a valid invitation token.", 1);
            }

            $invitationTokenString = $postData->get('invitation_token');
            $invitationTokenArray = explode('_',$invitationTokenString);
            
            $invitationToken = $this->getDoctrine()->getRepository(InvitationToken::class)
                ->findOneBy([
                    'id' => $invitationTokenArray[0],
                    'invitation_token' => $invitationTokenArray[1],
                    'active' => true
                ]);
            
            if($invitationToken == null){
                throw $this->createNotFoundException("You don't have a valid invitation token.");
            }
            
            $invitationTokenEmail = $invitationToken->getEmail();
            if($invitationTokenEmail !== null && $invitationTokenEmail !== $postData->get('email')){
                throw new \Exception("You don't have a valid invitation token.", 1);
            }

            $invitationToken->setActive(false);
            $user = new User();
            $user->setEmail($postData->get('email'));
            $user->setPassword(
                $passwordEnconder->encodePassword($user, $postData->get('password'))
            );

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->persist($invitationToken);
            $em->flush();

            $this->addFlash('success','Account created! Please, log in!');
            return $this->redirectToRoute('app_login');
        
        } catch (NotFoundHttpException $e) {
            $this->addFlash('error',$e->getMessage());
            return $this->redirectToRoute('app_registration_create');
        } catch (\Exception $e) {
            $this->addFlash('error',$e->getMessage());
            return $this->redirectToRoute('app_registration_create');
        }
    }

    /**
     * @Route("/register", methods={"GET","HEAD"})
     */
    public function create (Request $request)
    {
        $invitation_token = $request->get('invitation_token');
        return $this->render('registration/index.html.twig', compact('invitation_token'));
    }
}
