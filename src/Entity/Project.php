<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\ProjectRepository;
use App\Values\ProjectCategory;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité de la classe Project (Compta d'un projet).
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
class Project implements \Stringable
{
    final public const CLOSED = false;
    final public const OPENED = true;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom du projet.
     */
    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private ?string $name = null;

    /**
     * Description du projet.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    private ?string $description = null;

    /**
     * Catégorie du projet.
     */
    #[ORM\Column(type: 'projectcat', options: ['default' => 0])]
    private ProjectCategory $category;

    /**
     * Date de début du projet.
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTime $startedAt = null;

    /**
     * Date de fin du projet.
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTime $finishAt = null;

    /**
     * SI le projet est ouvert pour traitement.
     */
    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => 0])]
    private bool $state = self::OPENED;

    /**
     * Transcations associées.
     *
     * @var Collection|Transaction[]
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'project')]
    private Collection $transactions;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
        $now = new \DateTime();
        $this->startedAt = $now;
        $this->finishAt = $now->modify('+ 10 days');
        $this->category = new ProjectCategory(ProjectCategory::OTHER);
    }

    public function __toString(): string
    {
        return $this->name ?: '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getCategory(): ?ProjectCategory
    {
        return $this->category;
    }

    public function setCategory(ProjectCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getStartedAt(): ?\DateTime
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTime $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getFinishAt(): ?\DateTime
    {
        return $this->finishAt;
    }

    public function setFinishAt(?\DateTime $finishAt): self
    {
        $this->finishAt = $finishAt;

        return $this;
    }

    public function isState(): bool
    {
        return $this->state;
    }

    public function setState(bool $state): self
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return Collection|Transaction[]
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setProject($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        // set the owning side to null (unless already changed)
        if ($this->transactions->removeElement($transaction) && $transaction->getProject() === $this) {
            $transaction->setProject(null);
        }

        return $this;
    }

    /**
     * Retourne le coût total.
     */
    public function getTotalCost(): float
    {
        $result = 0.0;

        foreach ($this->transactions as $transaction) {
            $result += $transaction->getAmount();
        }

        return abs($result);
    }

    /**
     * Affiche le badge du statut.
     */
    public function getStateBadge(): string
    {
        if (!$this->isState()) {
            return '<span class="badge bg-danger text-uppercase">clos</span>';
        }

        return '<span class="badge bg-success text-uppercase">ouvert</span>';
    }
}
