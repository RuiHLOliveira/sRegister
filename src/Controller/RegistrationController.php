<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

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
            $user = new User();
            $user->setEmail($postData->get('email'));
            $user->setPassword(
                $passwordEnconder->encodePassword($user, $postData->get('password'))
            );
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('app_login');
        
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @Route("/register", methods={"GET","HEAD"})
     */
    public function create ()
    {
        return $this->render('registration/index.html.twig');
    }
}
