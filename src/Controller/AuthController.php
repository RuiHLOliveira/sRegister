<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Repository\UserRepository;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthController extends AbstractController
{
    private $authExpirationTime =  1 * 60 . ' seconds';
    private $refreshExpirationTime = 24 * 60 * 60 . ' seconds';

    /**
     * @Route("/auth/register", name="register", methods={"GET","POST", "OPTIONS"})
     */
    public function register(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $password = $request->get('password');
        $email = $request->get('email');
        $user = new User();
        $user->setPassword($encoder->encodePassword($user, $password));
        $user->setEmail($email);
        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();
        return $this->json([
            'user' => $user->getEmail()
        ]);
    }

    /**
     * @Route("/auth/login", name="login", methods={"GET","POST", "OPTIONS"})
     */
    public function login(Request $request, UserRepository $userRepository, UserPasswordEncoderInterface $encoder)
    {
        $user = $userRepository->findOneBy([
            'email' => $request->get('email'),
        ]);
        
        if (!$user || !$encoder->isPasswordValid($user, $request->get('password'))) {
            return new JsonResponse(['message' => 'Email or password is wrong.'], 400);
        }

        $payload = [
            "user" => $user->getUsername(),
            "exp"  => (new \DateTime())->modify($this->authExpirationTime)->getTimestamp(),
        ];

        $payloadRefresh = [
            "user" => $user->getUsername(),
            "exp"  => (new \DateTime())->modify($this->refreshExpirationTime)->getTimestamp(),
        ];

        $jwt = JWT::encode($payload, $this->getParameter('jwt_secret'), 'HS256');
        $jwtRefresh = JWT::encode($payloadRefresh, $this->getParameter('jwt_secret'), 'HS256');
        
        //salvar o token no banco para poder ver se é valido e invalidar ao gerar um novo
        // ou para o usuario manualmente invalidar se necessário

        return new JsonResponse([
            'message' => 'success!',
            'token' => sprintf('Bearer %s', $jwt),
            'refresh_token' => sprintf('Bearer %s', $jwtRefresh),
        ]);
    }

    /**
     * @Route("/auth/refreshToken", name="refreshToken", methods={"GET","POST", "OPTIONS"})
     */
    public function refreshToken(Request $request, UserRepository $userRepository, UserPasswordEncoderInterface $encoder)
    {
        $credentials = $request->get('refresh_token');
        $credentials = str_replace('Bearer ', '', $credentials);

        try {
            $jwt = (array) JWT::decode(
                $credentials,
                $this->getParameter('jwt_secret'),
                ['HS256']
            );
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ],JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }

        $user = $userRepository->findOneBy([
            'email' => $jwt['user'],
        ]);

        if (!$user ) {
            return new JsonResponse(['message' => 'Email or password is wrong.'], 400);
        }

        // checar no banco na table de usuarios acessos
        // se esse token existe para esse usuario
        // e se ele está válido

        // cria um novo refreshtoken com a mesma data de expiração
        $payload = [
            "user" => $user->getUsername(),
            "exp"  => (new \DateTime())->modify($this->authExpirationTime)->getTimestamp(),
        ];

        $payloadRefresh = [
            "user" => $user->getUsername(),
            "exp"  => $jwt['exp'],
        ];

        $jwt = JWT::encode($payload, $this->getParameter('jwt_secret'), 'HS256');
        $jwtRefresh = JWT::encode($payloadRefresh, $this->getParameter('jwt_secret'), 'HS256');
        
        //atualizar a table de usuarios acessos, mudando o token.

        return new JsonResponse([
            'message' => 'success!',
            'token' => sprintf('Bearer %s', $jwt),
            'refresh_token' => sprintf('Bearer %s', $jwtRefresh),
        ],JsonResponse::HTTP_OK);
    }
}
