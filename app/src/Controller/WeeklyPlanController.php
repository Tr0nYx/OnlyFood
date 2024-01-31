<?php

namespace App\Controller;

use App\Entity\WeeklyPlan;
use App\Repository\RecipeRepository;
use App\Repository\WeeklyPlanRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

class WeeklyPlanController extends AbstractController
{
    public function __construct(
        readonly private WeeklyPlanRepository $weeklyPlanRepository,
        readonly private RecipeRepository $recipeRepository,
        readonly private SerializerInterface $serializer
    ) {
    }

    /**
     * @return Response
     */
    #[Route(path: '/api/weekly-plan/', name: 'app_weekly_plan')]
    public function show(): Response
    {
        $user = $this->getUser();
        $weeklyPlan = $this->weeklyPlanRepository->findBy(['user' => $user->getId()]);
        $jsonContent = $this->serializer->serialize($weeklyPlan, 'json', ['groups' => 'weekly_plan']);

        return new Response($jsonContent, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    #[Route(path: '/api/weekly-plan/update/', name: 'app_update_weekly_plan')]
    public function createWeeklyPlan(Request $request): Response
    {
        $user = $this->getUser();
        $content = json_decode($request->getContent(), true);
        $recipe = $this->recipeRepository->find($content['recipeId']);

        if ($content['id']) {
            $weeklyPlan = $this->weeklyPlanRepository->findOneBy(['id' => $content['id'], 'user' => $user->getId()]);

            $this->setWeeklyPlanContent($weeklyPlan, $content, $recipe);
        } else {
            $weeklyPlan = new WeeklyPlan();
            $weeklyPlan->setUser($user);

            $this->setWeeklyPlanContent($weeklyPlan, $content, $recipe);
        }

        $this->weeklyPlanRepository->add($weeklyPlan);

        $jsonContent = $this->serializer->serialize($weeklyPlan, 'json', ['groups' => 'weekly_plan']);

        return new Response($jsonContent, Response::HTTP_OK);
    }

    /**
     * @param $weeklyPlan
     * @param $content
     * @param $recipe
     * @return void
     */
    private function setWeeklyPlanContent($weeklyPlan, $content, $recipe): void
    {

        if (!$content['day']) {
            new Response('Day is not set', Response::HTTP_BAD_REQUEST);

            return;
        }

        if (!$content['meal']) {
            new Response('Meal is not set', Response::HTTP_BAD_REQUEST);

            return;
        }

        $inWeekDay = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $inMeal = ['breakfast', 'lunch', 'dinner', 'snack'];

        if (in_array($content['day']['weekday'], $inWeekDay)) {
            $weeklyPlan->setWeekday($content['day']['weekday']);
            $weeklyPlan->setWeekDaySort($content['day']['weekDaySort']);
        }

        if (in_array($content['meal']['meal'], $inMeal)) {
            $weeklyPlan->setMeal($content['meal']['meal']);
            $weeklyPlan->setMealSort($content['meal']['mealSort']);
        }

        $weeklyPlan->setRecipe($recipe);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    #[Route(path: '/api/weekly-plan/remove/', name: 'app_remove_weekly_plan')]
    public function removeWeeklyPlan(Request $request): Response
    {
        $user = $this->getUser();
        $content = json_decode($request->getContent(), true);

        if ($content) {
            $weeklyPlan = $this->weeklyPlanRepository->findOneBy(['id' => $content['id'], 'user' => $user->getId()]);
            $this->weeklyPlanRepository->remove($weeklyPlan);
        }

        $weeklyPlans = $this->weeklyPlanRepository->findBy(['user' => $user]);

        $jsonContent = $this->serializer->serialize($weeklyPlans, 'json', ['groups' => 'weekly_plan']);

        return new Response($jsonContent, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/api/weekly-plan/showRecipes', name: 'app_weekly_plan_show_recipe')]
    public function showRecipesToWeeklyPlan(Request $request): Response
    {
        $user = $this->getUser();
        $content = json_decode($request->getContent(), true);
        $recipes = $this->recipeRepository->getRecipesforWeeklyPlan($user);

        $jsonContent = $this->serializer->serialize($recipes, 'json', ['groups' => 'add_weekly_plan']);

        return new Response($jsonContent, Response::HTTP_OK);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route(path: '/api/weekly-plan/todaysmealplan', name: 'app_weekly_plan_todays_meal_prep')]
    public function todaysMealPlan(Request $request): Response
    {
        $user = $this->getUser();
        $content = json_decode($request->getContent(), true);
        if ($content['date']) {
            $todaysMealPlan = $this->weeklyPlanRepository->getTodaysMealPlan(
                $user,
                $content['date']
            );

            $jsonContent = $this->serializer->serialize($todaysMealPlan, 'json', ['groups' => 'weekly_plan']);
        } else {
            $jsonContent = null;
        }

        return new Response($jsonContent, Response::HTTP_OK);
    }

}
