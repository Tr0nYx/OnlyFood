<?php

namespace App\Controller;

use App\Entity\Ingredients;
use App\Entity\Tag;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class RecipeOverviewController extends AbstractController
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param RecipeRepository $recipeRepository
     * @param SerializerInterface $serializer
     */
    public function __construct(
        readonly private EntityManagerInterface $entityManager,
        readonly private RecipeRepository $recipeRepository,
        readonly private SerializerInterface $serializer
    ) {
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/api/recipes/overview', name: 'app_recipe_overview', methods: 'GET')]
    public function show(Request $request)
    {
        $offset = $request->query->get('offset', 0);
        $user = $this->getUser();
        $tagIds = $request->query->get('tags', null) ?? [];

        $recipes = $this->recipeRepository->getRecipes($offset, $user, $tagIds);

        if ($recipes) {
            $jsonContent = $this->serializer->serialize($recipes, 'json', ['groups' => 'recipe_listing']);
        } else {
            $jsonContent = null;
        }

        return new Response($jsonContent, Response::HTTP_OK);

    }

    /**
     * @return Response
     */
    #[Route(path: '/api/recipes/showTags', name: 'app_tags', methods: 'GET')]
    public function showTags(): Response
    {
        $tags = $this->entityManager->getRepository(Tag::class)->findAll();

        $jsonContent = $this->serializer->serialize($tags, 'json', ['groups' => 'recipe_listing']);

        return new Response($jsonContent, Response::HTTP_OK);
    }

    /**
     * @return Response
     */
    #[Route(path: '/api/recipes/showIngredients', name: 'app_ingredients', methods: 'GET')]
    public function showIngredients(): Response
    {
        $ingredients = $this->entityManager->getRepository(Ingredients::class)->getFilterIngredients();

        if (!$ingredients) {
            return new Response('No ingredients found', Response::HTTP_NOT_FOUND);
        }

        $jsonContent = $this->serializer->serialize($ingredients, 'json', ['groups' => 'recipe_listing']);

        return new Response($jsonContent, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/api/recipes/searchResult', name: 'app_recipes_search_result', methods: 'GET')]
    public function getSearchResult(Request $request): Response
    {
        $search = $request->query->get('search');

        foreach ($search as $searchItem) {
            $recipes = $this->recipeRepository->getSearchResult($searchItem);
        }

        if (!$recipes) {
            return new Response('No recipes found', Response::HTTP_NOT_FOUND);
        }

        $jsonContent = $this->serializer->serialize($recipes, 'json', ['groups' => 'recipe_listing']);

        return new Response($jsonContent, Response::HTTP_OK);
    }

    /**
     * @return Response
     */
    #[Route(path: '/api/recipes/random', name: 'app_recipes_random', methods: 'GET')]
    public function getRandomRecipes(): Response
    {
        $user = $this->getUser();
        $recipes = $this->recipeRepository->getRandomRecipes($user);

        if (!$recipes) {
            return new Response('No recipes found', Response::HTTP_NOT_FOUND);
        }

        $jsonContent = $this->serializer->serialize($recipes, 'json', ['groups' => 'recipe_listing']);

        return new Response($jsonContent, Response::HTTP_OK);
    }
}
