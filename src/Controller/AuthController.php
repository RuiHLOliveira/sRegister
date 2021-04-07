<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\UserAccess;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Repository\UserRepository;
use DateTime;
use Firebase\JWT\JWT;
use Symfony\Component\HttpFoundation\JsonResponse;

class AuthController extends AbstractController
{
    private $authExpirationTime =  1 * 60 . ' seconds';
    private $refreshExpirationTime = 24 * 60 * 60 . ' seconds';
    // private $refreshExpirationTime = 60 . ' seconds';

    /**
     * @Route("/auth/register", name="register", methods={"GET","POST", "OPTIONS"})
     */
    public function register(Request $request, UserPasswordEncoderInterface $encoder)
    {
        $requestData = json_decode($request->getContent());
        $password = $requestData->password;
        $email = $requestData->email;
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
     * @Route("/auth/logout", name="logout", methods={"GET","POST", "OPTIONS"})
     */
    public function logout(Request $request, UserRepository $userRepository, UserPasswordEncoderInterface $encoder)
    {
        $requestData = json_decode($request->getContent());
        $refreshToken = $requestData->refresh_token;
        $refreshToken = str_replace('Bearer ', '', $refreshToken);

        $authToken = $requestData->token;
        $authToken = str_replace('Bearer ', '', $authToken);

        try {
            $jwt = (array) JWT::decode(
                $refreshToken,
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

        $userAccess = $this->getDoctrine()->getRepository(UserAccess::class)->findOneBy([
            'user' => $user,
            'hash' => $authToken,
            'refreshToken' => $refreshToken
        ]);

        if (!$userAccess ) {
            return new JsonResponse(['message' => 'This user has no active session. Please log in.'], 400);
        }
        if($userAccess->getHash() != $authToken){
            return new JsonResponse(['message' => 'This user has no active session. Please log in.'], 400);
        }
        if($userAccess->getRefreshToken() != $refreshToken){
            return new JsonResponse(['message' => 'This user has no active session. Please log in.'], 400);
        }

        $userAccess->setActive(false);
        $userAccess->setLastUsageDate();
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($userAccess);
        $em->flush();

        return new JsonResponse([
            'message' => 'Logged off successfuly!',
        ],JsonResponse::HTTP_OK);
    }

    /**
     * @Route("/auth/login", name="login", methods={"GET","POST", "OPTIONS"})
     */
    public function login(Request $request, UserRepository $userRepository, UserPasswordEncoderInterface $encoder)
    {
        $requestData = json_decode($request->getContent());

        $user = $userRepository->findOneBy([
            'email' => $requestData->email,
        ]);
        
        if (!$user) {
            return new JsonResponse(['message' => 'Email is wrong.'], 400);
        }

        if (!$encoder->isPasswordValid($user, $requestData->password)) {
            return new JsonResponse(['message' => 'Password is wrong.'], 400);
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
        $userAccess = new UserAccess();
        $userAccess->setActive(true);
        $userAccess->setHash($jwt);
        $userAccess->setRefreshToken($jwtRefresh);
        $userAccess->setUser($user);

        $em = $this->getDoctrine()->getManager();
        $em->persist($userAccess);
        $em->flush();

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
        $requestData = json_decode($request->getContent());
        $refreshToken = $requestData->refresh_token;
        $refreshToken = str_replace('Bearer ', '', $refreshToken);

        $authToken = $requestData->token;
        $authToken = str_replace('Bearer ', '', $authToken);

        try {
            $jwt = (array) JWT::decode(
                $refreshToken,
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

        $userAccess = $this->getDoctrine()->getRepository(UserAccess::class)->findOneBy([
            'user' => $user,
            'hash' => $authToken,
            'refreshToken' => $refreshToken
        ]);

        if (!$userAccess ) {
            return new JsonResponse(['message' => 'This user has no active session. Please log in.'], 400);
        }
        if($userAccess->getHash() != $authToken){
            return new JsonResponse(['message' => 'This user has no active session. Please log in.'], 400);
        }
        if($userAccess->getRefreshToken() != $refreshToken){
            return new JsonResponse(['message' => 'This user has no active session. Please log in.'], 400);
        }

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
        
        $userAccess->setHash($jwt);
        $userAccess->setRefreshToken($jwtRefresh);
        $userAccess->setLastUsageDate();
        
        $em = $this->getDoctrine()->getManager();
        $em->persist($userAccess);
        $em->flush();

        return new JsonResponse([
            'message' => 'success!',
            'token' => sprintf('Bearer %s', $jwt),
            'refresh_token' => sprintf('Bearer %s', $jwtRefresh),
        ],JsonResponse::HTTP_OK);
    }
}
