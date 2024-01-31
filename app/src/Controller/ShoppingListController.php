<?php

namespace App\Controller;

use App\Entity\ShoppingList;
use App\Repository\ShoppingListRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ShoppingListController extends AbstractController
{
    public function __construct(
        readonly private ShoppingListRepository $repository,
        readonly private SerializerInterface $serializer
    ) {
    }

    /**
     * @return Response
     */
    #[Route(path: '/api/shopping/list', name: 'app_shopping_list')]
    public function show(): Response
    {
        $user = $this->getUser();
        $shoppingList = $this->repository->findBy(['user' => $user->getId()]);

        $jsonContent = $this->serializer->serialize($shoppingList, 'json', ['groups' => 'shopping_list']);

        return new Response($jsonContent, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/api/shopping-list/upsert/', name: 'app_shopping_upsert_list')]
    public function upsertList(Request $request): Response
    {
        $user = $this->getUser();
        $content = json_decode($request->getContent(), true);
        $shoppingList = $this->repository->findOneBy(['user' => $user->getId()]);

        if ($shoppingList) {
            if ($content['ingredients']) {
                $shoppingList->setIngredients($content['ingredients']);
            } else {
                throw new \Exception('No ingredients found');
            }
        } else {
            $shoppingList = new ShoppingList();
            $shoppingList->setUser($user);

            if ($content['ingredients']) {
                $shoppingList->setIngredients($content['ingredients']);
            } else {
                throw new \Exception('No ingredients found');
            }
        }

        $this->repository->add($shoppingList);

        $jsonContent = $this->serializer->serialize($shoppingList, 'json', ['groups' => 'shopping_list']);

        return new Response($jsonContent, Response::HTTP_OK);

    }

    /**
     * @return Response
     */
    #[Route(path: '/api/shopping-list/delete/', name: 'app_shopping_delete_list')]
    public function deleteList(): Response
    {
        $user = $this->getUser();
        $shoppingList = $this->repository->findOneBy(['user' => $user->getId()]);

        if ($shoppingList) {
            $this->repository->remove($shoppingList);
        }

        $jsonContent = $this->serializer->serialize($shoppingList, 'json', ['groups' => 'shopping_list']);

        return new Response($jsonContent, Response::HTTP_OK);

    }
}
