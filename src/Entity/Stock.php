<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\StockRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité de la classe Stock (Titre boursier).
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
#[ORM\Entity(repositoryClass: StockRepository::class)]
class Stock implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Code ISIN.
     */
    #[ORM\Column(type: Types::STRING, length: 12, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 12)]
    private ?string $codeISIN = null;

    /**
     * Nom de l'action.
     */
    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 5, max: 100)]
    private ?string $name = null;

    /**
     * Date de la fermeture.
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $closedAt = null;

    /**
     * Titre d'avant qui a été fusionné.
     */
    #[ORM\OneToOne(targetEntity: self::class, inversedBy: 'fusionTo', cascade: ['persist', 'remove'])]
    private ?Stock $fusionFrom = null;

    /**
     * Vers le nouveau titre fusionné.
     */
    #[ORM\OneToOne(targetEntity: self::class, mappedBy: 'fusionFrom', cascade: ['persist', 'remove'])]
    private ?Stock $fusionTo = null;

    /**
     * @var Collection|StockPrice[]
     */
    #[ORM\OneToMany(targetEntity: StockPrice::class, mappedBy: 'stock', orphanRemoval: true)]
    private Collection $stockPrices;

    /**
     * @var Collection|StockWallet[]
     */
    #[ORM\OneToMany(targetEntity: StockWallet::class, mappedBy: 'stock', orphanRemoval: true)]
    private Collection $stockWallets;

    /**
     * @var Collection|TransactionStock[]
     */
    #[ORM\OneToMany(targetEntity: TransactionStock::class, mappedBy: 'stock')]
    private Collection $transactionStocks;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->stockPrices = new ArrayCollection();
        $this->stockWallets = new ArrayCollection();
        $this->transactionStocks = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?: '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeISIN(): ?string
    {
        return $this->codeISIN;
    }

    public function setCodeISIN(?string $codeISIN): self
    {
        $this->codeISIN = $codeISIN;

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

    public function getClosedAt(): ?\DateTime
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTime $closedAt): self
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    public function getFusionFrom(): ?self
    {
        return $this->fusionFrom;
    }

    public function setFusionFrom(?self $fusionFrom): self
    {
        $this->fusionFrom = $fusionFrom;

        return $this;
    }

    public function getFusionTo(): ?self
    {
        return $this->fusionTo;
    }

    public function setFusionTo(?self $fusionTo): self
    {
        // unset the owning side of the relation if necessary
        if (!$fusionTo instanceof self && $this->fusionTo instanceof self) {
            $this->fusionTo->setFusionFrom(null);
        }

        // set the owning side of the relation if necessary
        if ($fusionTo instanceof self && $fusionTo->getFusionFrom() !== $this) {
            $fusionTo->setFusionFrom($this);
        }

        $this->fusionTo = $fusionTo;

        return $this;
    }

    /**
     * @return Collection|StockPrice[]
     */
    public function getStockPrices(): Collection
    {
        return $this->stockPrices;
    }

    public function addStockPrice(StockPrice $stockPrice): self
    {
        if (!$this->stockPrices->contains($stockPrice)) {
            $this->stockPrices[] = $stockPrice;
            $stockPrice->setStock($this);
        }

        return $this;
    }

    public function removeStockPrice(StockPrice $stockPrice): self
    {
        // set the owning side to null (unless already changed)
        if ($this->stockPrices->removeElement($stockPrice) && $stockPrice->getStock() === $this) {
            $stockPrice->setStock(null);
        }

        return $this;
    }

    /**
     * @return Collection|StockWallet[]
     */
    public function getStockWallets(): Collection
    {
        return $this->stockWallets;
    }

    public function addStockWallet(StockWallet $stockWallet): self
    {
        if (!$this->stockWallets->contains($stockWallet)) {
            $this->stockWallets[] = $stockWallet;
            $stockWallet->setStock($this);
        }

        return $this;
    }

    public function removeStockWallet(StockWallet $stockWallet): self
    {
        // set the owning side to null (unless already changed)
        if ($this->stockWallets->removeElement($stockWallet) && $stockWallet->getStock() === $this) {
            $stockWallet->setStock(null);
        }

        return $this;
    }

    /**
     * @return Collection|TransactionStock[]
     */
    public function getTransactionStocks(): Collection
    {
        return $this->transactionStocks;
    }

    public function addTransactionStock(TransactionStock $transactionStock): self
    {
        if (!$this->transactionStocks->contains($transactionStock)) {
            $this->transactionStocks[] = $transactionStock;
            $transactionStock->setStock($this);
        }

        return $this;
    }

    public function removeTransactionStock(TransactionStock $transactionStock): self
    {
        // set the owning side to null (unless already changed)
        if ($this->transactionStocks->removeElement($transactionStock) && $transactionStock->getStock() === $this) {
            $transactionStock->setStock(null);
        }

        return $this;
    }

    /**
     * Affiche le badge du statut de l'action.
     */
    public function getStatusBadge(): string
    {
        if ($this->closedAt instanceof \DateTime) {
            return '<span class="badge bg-danger text-uppercase">fermé</span>';
        }

        return '<span class="badge bg-secondary text-uppercase">ouvert</span>';
    }
}
