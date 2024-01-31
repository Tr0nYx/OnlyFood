<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;

class AccountOverviewController extends AbstractController
{
    /**
     * @param TokenStorageInterface $tokenStorage
     * @param UserRepository $userRepository
     * @param SerializerInterface $serializer
     */
    public function __construct(
        readonly private TokenStorageInterface $tokenStorage,
        readonly private UserRepository $userRepository,
        readonly private SerializerInterface $serializer
    ) {

    }

    /**
     * @return Response
     */
    #[Route(path: '/api/account/details', name: 'api_account_details', methods: 'GET')]
    public function getAccountDetails(): Response
    {
        $user = $this->getUser();
        $jsonContent = $this->serializer->serialize($user, 'json', ['groups' => 'account_overview']);

        return new Response($jsonContent, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/api/account/uploadProfilePicture', name: 'api_account_upload_profile_picture', methods: 'POST')]
    public function uploadProfilePicture(Request $request): Response
    {
        $user = $this->getUser();
        $userName = $this->getUser()->getUserIdentifier();
        $path = $request->files->get('file');
        $fileName = $userName.'.'.$path->guessExtension();

        if ($path) {
            $user->setProfilePictureFile($path);
            $user->setProfilePictureName($fileName);
            $this->updateDatabase($user);
        }

        $jsonContent = $this->serializer->serialize($user, 'json', ['groups' => 'account_overview']);

        return new Response($jsonContent, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    #[Route(path: '/api/account/changeUserInfo', name: 'api_account_change_user_info')]
    public function getChangeUserinfo(Request $request)
    {
        $user = $this->getUser();
        $content = json_decode($request->getContent(), true);

        if ($content['username'] != $user->getUserIdentifier()) {
            $checkUsername = $this->userRepository->findOneBy(['username' => $content['username']]);

            if ($checkUsername) {
                throw new \Exception('Username already exist');
            } else {
                $user->setUsername($content['username']);
            }
        }

        $user->setPublicMode($content['publicMode']);
        $user->setLightMode($content['lightMode']);

        $this->updateDatabase($user);

        $jsonContent = $this->serializer->serialize($user, 'json', ['groups' => 'account_overview']);

        return new Response($jsonContent, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/api/account/deleteAccount', name: 'api_account_delete_account', methods: 'DELETE')]
    public function deleteAccount(Request $request)
    {
        $user = $this->getUser();
        $this->userRepository->remove($user);

        $request->getSession()->invalidate();
        $this->tokenStorage->setToken();

        return new Response(Response::HTTP_OK);
    }

    public function updateDatabase(User $user)
    {
        $this->userRepository->add($user);
    }
}
