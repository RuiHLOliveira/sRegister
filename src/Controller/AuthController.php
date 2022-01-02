<?php

namespace App\Controller;

use DateTime;
use Exception;
use App\Entity\User;
use Firebase\JWT\JWT;
use App\Entity\UserAccess;
use App\Entity\InvitationToken;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\VarDumper\VarDumper;

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
        try {
            $requestData = json_decode($request->getContent());
            $password = $requestData->password;
            $email = $requestData->email;
            $invitationToken = $requestData->invitation_token;
            
            if($invitationToken == ''){
                throw new BadRequestHttpException("Invitation Token was not sent.");
            }
            
            $invitationTokenArray = explode('_',$invitationToken);
            if(!isset($invitationTokenArray[0]) || !isset($invitationTokenArray[1])){
                throw new BadRequestHttpException("Invitation Token has an invalid format.");
            }
            $tokenId = $invitationTokenArray[0];
            $tokenNumber = $invitationTokenArray[1];
            
            $invitationToken = $this->getDoctrine()->getRepository(InvitationToken::class)
            ->findOneBy([
                'id' => $tokenId,
                'invitation_token' => $tokenNumber,
                'active' => true
            ]);

            if($invitationToken == null){
                throw new NotFoundHttpException("Invitation Token not found or already used.");
            }
            
            $invitationTokenEmail = $invitationToken->getEmail();
            if($invitationTokenEmail !== null && $invitationTokenEmail !== $email){
                throw new NotFoundHttpException("This email can't use this Invitation Token.");
            }

            $em = $this->getDoctrine()->getManager();

            $user = new User();
            $user->setPassword($encoder->encodePassword($user, $password));
            $user->setEmail($email);

            $em->persist($user);
            $em->flush();

            $invitationToken->setActive(false);
            $em->persist($invitationToken);
            $em->flush();

            return new JsonResponse(['message' => 'User registered']);

        } catch ( BadRequestHttpException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 400);
        } catch ( NotFoundHttpException $e) {
            return new JsonResponse(['message' => $e->getMessage()], 404);
        } catch ( Exception $e) {
            return new JsonResponse(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * @Route("/auth/logout", name="logout", methods={"GET","POST", "OPTIONS"})
     */
    public function logout(Request $request, UserRepository $userRepository, UserPasswordEncoderInterface $encoder)
    {
        $requestData = json_decode($request->getContent());
        if(!property_exists($requestData, 'token')){
            return new JsonResponse(['message' => 'Token not sent'], 500);
        }
        if(!property_exists($requestData, 'refresh_token')){
            return new JsonResponse(['message' => 'Refresh Token not sent'], 500);
        }

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
        if(!property_exists($requestData, 'token')){
            return new JsonResponse(['message' => 'Token not sent'], 400);
        }
        if(!property_exists($requestData, 'refresh_token')){
            return new JsonResponse(['message' => 'Refresh Token not sent'], 400);
        }

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
