<?php

namespace App\Entity;

use App\Repository\ShoppingListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=ShoppingListRepository::class)
 */
#[ORM\Entity(repositoryClass: ShoppingListRepository::class)]
#[ORM\Table]
class ShoppingList
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    #[Groups(["weekly_plan", "shopping_list"])]
    private int $id;

    #[ORM\Column(type: Types::STRING, nullable: true)]
    #[Groups(["weekly_plan", "shopping_list"])]
    private string $name;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'shoppingList')]
    #[Groups(["shopping_list"])]
    private Collection $user;

    #[ORM\Column(type: Types::JSON)]
    #[Groups(["shopping_list"])]
    private array $ingredients = [];

    public function __construct()
    {
        $this->user = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

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

    public function getIngredients(): ?array
    {
        return $this->ingredients;
    }

    public function setIngredients(?array $ingredients): self
    {
        $this->ingredients = $ingredients;

        return $this;
    }
}
