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
    public const INCOME = true;
    public const EXPENSE = false;

    /**
     * Constantes des catégories de mouvements internes.
     */
    public const MOVEMENT = 'MVT';
    public const BALANCE = 'BLCE';
    public const VIREMENT = 'VIRT';
    public const INVESTMENT = 'INVS';
    public const REPURCHASE = 'RCHA';
    public const REVALUATION = 'EVAL';
    public const STOCKTRANSAC = 'OPST';

    public const DIVIDENDES = 'DIVD';
    public const CARBURANT = 'FUEL';
    public const VEHICULEREPAIR = 'VRPR';
    public const VEHICULEFUNDING = 'VFDG';
    public const RESALE = 'RSAL';
    public const INTERET = 'INTR';
    public const TAXE = 'TAXE';
    public const TAXE_CSG = 'CSSG';

    /**
     * Listes des catégories de niveau 1 de mouvements internes.
     *
     * @var array<mixed>
     */
    public static $movementsLevel1 = [
        self::DEPENSES => 'Mouvements débiteurs',
        self::RECETTES => 'Mouvements créditeurs',
    ];

    /**
     * Listes des catégories obligatoires de mouvements internes à créer.
     *
     * @var array<mixed>
     */
    public static $movements = [
        self::BALANCE => ['Balance négative', 'Balance positive'], // Equilibrage de la balance
        self::VIREMENT => ['Virement émis', 'Virement reçus'], // Virement interne
        self::INVESTMENT => ['Investissement', 'Versement'], // Investissement sur un placement (ass vie, ...)
        self::REPURCHASE => ['Rachat capital', 'Versement capital'], // Rachat du capital d'un placement
        self::REVALUATION => ['Réévaluation déficitaire', 'Réévaluation bénéficiaire'], // Réévaluation mensuelle d'un placement
        self::STOCKTRANSAC => ['Achat titres', 'Vente titres'], // Achat et vente titres boursiers
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id; /** @phpstan-ignore-line */

    /**
     * Code de la catégorie pour reconnaitre les virements internes ou la catégorie essence.
     *
     * @var string
     *
     * @ORM\Column(type="string", length=4, nullable=true)
     */
    private $code;

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

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity=Transaction::class, mappedBy="category")
     */
    private $transactions;

    public function __construct()
    {
        $this->type = self::DEPENSES;
        $this->children = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function __toString()
    {
        if (!$this->name) {
            return '';
        }

        return $this->getFullName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(?string $code): self
    {
        $this->code = $code;

        return $this;
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

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setCategory($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getCategory() === $this) {
                $transaction->setCategory(null);
            }
        }

        return $this;
    }

    /**
     * Retounr le nom complet pour le niveau 2.
     *
     * @return string
     */
    public function getFullName(): string
    {
        switch ($this->getLevel()) {
            case 1:
                return $this->getName();
            case 2:
                return $this->getParent()->getName().':'.$this->getName();
            default:
                return $this->getName();
        }
    }

    public function getTypeBadge(): string
    {
        return ($this->type) ? '<span class="badge bg-success text-uppercase">revenus</span>' : '<span class="badge bg-danger text-uppercase">dépenses</span>';
    }

    public function getTypeSymbol(): string
    {
        return ($this->type) ? '+' : '-';
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
