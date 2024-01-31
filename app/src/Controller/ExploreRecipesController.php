<?php

namespace App\Controller;

use App\Repository\RecipeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class ExploreRecipesController extends AbstractController
{

    public function __construct(readonly private RecipeRepository $recipeRepository)
    {

    }

    /**
     * @param Request $request
     * @param SerializerInterface $serializer
     * @return Response
     */
    #[Route(path: '/api/recipes/explore', name: 'app_recipes_explore', methods: 'GET')]
    public function show(Request $request, SerializerInterface $serializer)
    {
        $offset = $request->query->get('offset', 0);
        $user = $this->getUser();
        $tagIds = $request->query->get('tags', null) ?? [];

        $recipes = $this->recipeRepository->getExploreRecipes($offset, $user, $tagIds);

        if ($recipes) {
            $jsonContent = $serializer->serialize($recipes, 'json', ['groups' => 'recipe_listing']);
        } else {
            $jsonContent = null;
        }

        return new Response($jsonContent, Response::HTTP_OK);

    }
}
