<?php

namespace App\Entity;

use App\Repository\WeeklyPlanRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;


#[ORM\Entity(repositoryClass: WeeklyPlanRepository::class)]
#[ORM\Table]
class WeeklyPlan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(["weekly_plan", "recipe_overview"])]
    private int $id;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(["weekly_plan", "recipe_overview"])]
    private string $weekday;

    #[ORM\Column(type: Types::STRING, length: 255)]
    #[Groups(["weekly_plan", "recipe_overview"])]
    private string $meal;

    #[ORM\ManyToOne(targetEntity: Recipe::class, inversedBy: 'weeklyPlans')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["weekly_plan"])]
    private Collection $recipe;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'weeklyPlans')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(["weekly_plan"])]
    private Collection $user;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(["weekly_plan"])]
    private int $weekDaySort;

    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(["weekly_plan"])]
    private int $mealSort;

    public function __construct()
    {
        $this->recipe = new ArrayCollection();
        $this->user = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getWeekday(): ?string
    {
        return $this->weekday;
    }

    public function setWeekday(string $weekday): self
    {
        $this->weekday = $weekday;

        return $this;
    }

    public function getMeal(): ?string
    {
        return $this->meal;
    }

    public function setMeal(string $meal): self
    {
        $this->meal = $meal;

        return $this;
    }

    public function getRecipe(): ?Recipe
    {
        return $this->recipe;
    }

    public function setRecipe(?Recipe $recipe): self
    {
        $this->recipe = $recipe;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getWeekDaySort(): ?int
    {
        return $this->weekDaySort;
    }

    public function setWeekDaySort(int $weekDaySort): self
    {
        $this->weekDaySort = $weekDaySort;

        return $this;
    }

    public function getMealSort(): ?int
    {
        return $this->mealSort;
    }

    public function setMealSort(int $mealSort): self
    {
        $this->mealSort = $mealSort;

        return $this;
    }
}
