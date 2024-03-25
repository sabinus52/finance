<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\RecipientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité de la classe Recipient (Tiers et bénéficiares).
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
#[ORM\Entity(repositoryClass: RecipientRepository::class)]
class Recipient implements \Stringable
{
    /**
     * Nom du bénéficiaire pour les virements internes.
     */
    final public const VIRT_NAME = 'Moi-même';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom du bénéficiare.
     */
    #[ORM\Column(type: Types::STRING, length: 100)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private ?string $name = null;

    /**
     * Catégorie la plus utilisé par le bénéficiare.
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    private ?Category $category = null;

    /**
     * @var Collection|Transaction[]
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'recipient')]
    private Collection $transactions;

    public function __construct()
    {
        $this->transactions = new ArrayCollection();
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

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

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
            $transaction->setRecipient($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        // set the owning side to null (unless already changed)
        if ($this->transactions->removeElement($transaction) && $transaction->getRecipient() === $this) {
            $transaction->setRecipient(null);
        }

        return $this;
    }
}
