<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité de la classe Category (Catégories des opérations).
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @ORM\Entity(repositoryClass=CategoryRepository::class)
 * @ORM\HasLifecycleCallbacks
 */
class Category
{
    public const RECETTES = true;
    public const DEPENSES = false;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id; /** @phpstan-ignore-line */

    /**
     * Nom de la catégorie.
     *
     * @var string
     *
     * @ORM\Column(type="string", length=100)
     * @Assert\NotBlank
     * @Assert\Length(max=100)
     */
    private $name;

    /**
     * Type (recettes=1 ou dépenses=0).
     *
     * @var bool
     *
     * @ORM\Column(type="smallint")
     */
    private $type;

    /**
     * Niveau de la hiérarchie.
     *
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    private $level;

    /**
     * Catégorie parente.
     *
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="children")
     * @ORM\JoinColumn(referencedColumnName="id", onDelete="CASCADE")
     */
    private $parent;

    /**
     * Catégories enfants.
     *
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity=Category::class, mappedBy="parent")
     * @ORM\OrderBy({"name": "ASC"})
     */
    private $children;

    public function __construct()
    {
        $this->type = self::DEPENSES;
        $this->children = new ArrayCollection();
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

    public function getType(): bool
    {
        return (bool) $this->type;
    }

    public function setType(bool $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): self
    {
        $this->level = $level;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): self
    {
        if (!$this->children->contains($child)) {
            $this->children[] = $child;
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): self
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    public function getTypeBadge(): string
    {
        return ($this->type) ? '<span class="badge bg-success text-uppercase">revenus</span>' : '<span class="badge bg-danger text-uppercase">dépenses</span>';
    }

    /**
     * @ORM\PrePersist
     * @ORM\PreUpdate
     */
    public function setTree(): void
    {
        $this->level = 1;
        if (null !== $this->parent) {
            $this->level = 2;
        }
    }
}
