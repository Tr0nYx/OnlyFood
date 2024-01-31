<?php

namespace App\Controller;

use App\Entity\IngredientQuantity;
use App\Entity\Ingredients;
use App\Entity\Recipe;
use App\Entity\Tag;
use App\Entity\User;
use App\Entity\WeeklyPlan;
use App\Repository\RecipeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

class RecipeController extends AbstractController
{
    /**
     * @param EntityManagerInterface $entityManager
     * @param SerializerInterface $serializer
     */
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SerializerInterface $serializer,
        private readonly RecipeRepository $recipeRepository
    ) {
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Exception
     */
    #[Route(path: '/api/createRecipe', name: 'app_recipe_create')]
    public function createRecipe(Request $request)
    {
        $user = $this->getUser();
        $content = json_decode($request->get('recipe'), true);
        $userName = $this->getUser()->getUserIdentifier();
        $path = $request->files->get('file');

        if ($content['id']) {
            $recipe = $this->recipeRepository->findOneBy(['id' => $content['id'], 'userId' => $user->getId()]);
            $recipe->setPortion($content['portion']);
            $recipe->setPrepTime($content['prepTime']);

            foreach ($recipe->getTags() as $tag) {
                $recipe->removeTag($tag);
            }
        } else {
            $recipe = new Recipe();
            $recipe->setPortion($content['portion']);
            $recipe->setPrepTime($content['prepTime']);
            $recipe->setUserId($user);
        }

        if ($path) {
            $fileName = $userName.'.'.$path->guessExtension();

            $recipe->setImageFile($path);
            $recipe->setImageName($fileName);
        }

        if (!$content['name']) {
            throw new \Exception('Recipe name is required');
        } else {
            $recipe->setName($content['name']);
        }

        if (!$content['method']) {
            throw new \Exception('Method is required');
        } else {
            $recipe->setMethod($content['method']);
        }

        if (!$content['difficulty']) {
            throw new \Exception('Difficulty is required');
        } else {
            $inUseDifficulty = ['easy', 'medium', 'hard'];
            if (in_array($content['difficulty'], $inUseDifficulty)) {
                $recipe->setDifficulty($content['difficulty']);
            }
        }

        $this->updateDatabase($recipe);

        if (!$content['tags']) {
            return new Response('Tags are required', Response::HTTP_NOT_FOUND);
        }

        $inUseTags = [
            'breakfast',
            'lunch',
            'dinner',
            'cold food',
            'warm food',
            'no animal products',
            'no fish & meat',
            'no seafood',
            'sweet',
            'savoury',
            'fast',
            'cheap',
            'high protein',
        ];

        foreach ($content['tags'] as $tag) {
            $tagRepo = $this->entityManager->getRepository(Tag::class)->findOneBy(['name' => $tag]);

            if (in_array($tag['name'], $inUseTags)) {
                if (!$tagRepo) {
                    $tagRepo = new Tag();
                    $tagRepo->setName($tag['name']);
                    $tagRepo->addRecipe($recipe);
                } else {
                    $recipe->addTag($tagRepo);
                }
                $this->updateDatabase($tagRepo);
            }
        }


        if (!$content['ingredients']) {
            throw new \Exception('Ingredients are required');
        } else {
            foreach ($content['ingredients'] as $ingredient) {
                $ingredientRepo = $this->entityManager->getRepository(Ingredients::class);
                $ingredientEntity = $ingredientRepo->findOneBy(['id' => $ingredient['id']]);
                if (!$ingredientEntity) {
                    $ingredientEntity = new Ingredients();
                    $this->setIngredients($ingredientEntity, $ingredient);
                    $ingredientEntity->addRecipe($recipe);
                } else {
                    $this->setIngredients($ingredientEntity, $ingredient);
                }

                $this->updateDatabase($ingredientEntity);
            }
        }

        $jsonContent = $this->serializer->serialize($recipe, 'json', ['groups' => 'recipe_overview']);

        return new Response($jsonContent, Response::HTTP_OK);
    }

    /**
     * @return JsonResponse|Response
     */
    #[Route(path: '/api/recipe/{id}', name: 'app_recipe_show', methods: 'GET')]
    public function show(#[MapEntity] Recipe $recipe)
    {
        $likedRecipe = [];
        $user = $this->getUser();

        if ($user) {
            $userId = $user->getId();
            $weeklyPlan = $this->entityManager->getRepository(WeeklyPlan::class)->findWeeklyPlanOfUser($user);
            $weeklyPlanJson = $this->serializer->serialize($weeklyPlan, 'json', ['groups' => 'weekly_plan']);
            $likedRecipe = $this->recipeRepository->checkLikedRecipe($user, $recipe);

        } else {
            $weeklyPlanJson = null;
            $userId = null;
        }

        $isUserRecipe = false;

        if ($recipe) {
            $recipeUser = $recipe->getUserId();

            if ($recipeUser == $user) {
                $isUserRecipe = true;
            }
        } else {
            return new Response('Recipe not found', Response::HTTP_NOT_FOUND);
        }

        $recipeJson = $this->serializer->serialize($recipe, 'json', ['groups' => 'recipe_overview']);

        $newResponse = array(
            'recipe' => $recipeJson,
            'likedRecipe' => $likedRecipe,
            'weeklyPlans' => $weeklyPlanJson,
            'isUserRecipe' => $isUserRecipe,
            'isUserLoggedIn' => $userId,
        );

        return new JsonResponse($newResponse, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/api/recipe/{id}/like', name: 'app_recipe_like', methods: 'POST')]
    public function likeRecipe(#[MapEntity] Recipe $recipe)
    {
        $user = $this->getUser();
        $userRepo = $this->entityManager->getRepository(User::class)->findOneBy(['id' => $user->getId()]);
        $recipeRepo = $this->recipeRepository->findOneBy(['id' => $recipe->getId()]);
        $likedRecipe = $this->recipeRepository->checkLikedRecipe($user, $recipeRepo);

        if ($likedRecipe) {
            $userRepo->removeLikedRecipe($recipeRepo);
            $this->updateDatabase($recipeRepo);
            $likedRecipe = false;
        } else {
            $likedRecipe = true;
            $userRepo->addLikedRecipe($recipeRepo);
            $this->updateDatabase($recipeRepo);
        }

        return new Response($likedRecipe, Response::HTTP_OK);
    }


    /**
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/api/recipe/{id}/cancelRecipe', name: 'api_delete_recipe', methods: 'DELETE')]
    public function cancelRecipe(#[MapEntity] Recipe $recipe): Response
    {
        $user = $this->getUser();
        $recipeId = $this->recipeRepository->findOneBy(['id' => $recipe->getId(), 'userId' => $user->getId()]);
        $ingredients = $this->entityManager->getRepository(Ingredients::class)->getRecipeId($recipeId);
        $weeklyPlans = $this->entityManager->getRepository(WeeklyPlan::class)->findBy(['recipe' => $recipeId]);

        if ($recipeId) {
            if ($ingredients) {
                foreach ($ingredients as $ingredient) {
                    $this->entityManager->remove($ingredient);
                }
            }

            if ($weeklyPlans) {
                foreach ($weeklyPlans as $weeklyPlan) {
                    $this->entityManager->remove($weeklyPlan);
                }
            }

            $this->entityManager->remove($recipeId);
            $this->entityManager->flush();
        }

        $jsonContent = $this->serializer->serialize($recipeId, 'json', ['groups' => 'recipe_overview']);

        return new Response($jsonContent, Response::HTTP_OK);
    }


    private function setIngredients($ingredientEntity, $ingredient)
    {
        if ($ingredient['name']) {
            $ingredientEntity->setName($ingredient['name']);
        } else {
            throw new \Exception('Ingredient Name is required');
        }
        if ($ingredient['quantity']) {
            if (is_numeric($ingredient['quantity'])) {
                $ingredientEntity->setQuantity($ingredient['quantity']);
            } else {
                throw new \Exception('Quantity has to be an integer');
            }
            $ingredientEntity->setQuantity($ingredient['quantity']);
        } else {
            throw new \Exception('Quantity is required');
        }

        $ingredientEntity->setUnit($ingredient['unit']);
    }

    public function updateDatabase($object)
    {
        $this->entityManager->persist($object);
        $this->entityManager->flush();
    }
}
