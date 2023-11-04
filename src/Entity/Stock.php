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
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité de la classe Stock (Actions boursières).
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @ORM\Entity(repositoryClass=StockRepository::class)
 */
class Stock
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id; /** @phpstan-ignore-line */

    /**
     * Code ISIN.
     *
     * @var string
     *
     * @ORM\Column(type="string", length=12, unique=true)
     * @Assert\NotBlank
     */
    private $codeISIN;

    /**
     * Nom de l'action.
     *
     * @var string
     *
     * @ORM\Column(type="string", length=100, unique=true)
     * @Assert\NotBlank
     */
    private $name;

    /**
     * Date de la fermeture.
     *
     * @var DateTimeInterface
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $closedAt;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity=StockPrice::class, mappedBy="stock", orphanRemoval=true)
     */
    private $stockPrices;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity=StockWallet::class, mappedBy="stock", orphanRemoval=true)
     */
    private $stockWallets;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity=TransactionStock::class, mappedBy="stock")
     */
    private $transactionStocks;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->stockPrices = new ArrayCollection();
        $this->stockWallets = new ArrayCollection();
        $this->transactionStocks = new ArrayCollection();
    }

    public function __toString()
    {
        if (!$this->name) {
            return '';
        }

        return $this->getName();
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

    public function getClosedAt(): ?DateTimeInterface
    {
        return $this->closedAt;
    }

    public function setClosedAt(?DateTimeInterface $closedAt): self
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    /**
     * @return Collection<int, StockPrice>
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
        if ($this->stockPrices->removeElement($stockPrice)) {
            // set the owning side to null (unless already changed)
            if ($stockPrice->getStock() === $this) {
                $stockPrice->setStock(null);
            }
        }

        return $this;
    }

    /**
     * Affiche le badge du statut de l'action.
     *
     * @return string
     */
    public function getStatusBadge(): string
    {
        if (null !== $this->closedAt) {
            return '<span class="badge bg-danger text-uppercase">fermé</span>';
        }

        return '<span class="badge bg-secondary text-uppercase">ouvert</span>';
    }

    /**
     * @return Collection<int, StockWallet>
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
        if ($this->stockWallets->removeElement($stockWallet)) {
            // set the owning side to null (unless already changed)
            if ($stockWallet->getStock() === $this) {
                $stockWallet->setStock(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TransactionStock>
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
        if ($this->transactionStocks->removeElement($transactionStock)) {
            // set the owning side to null (unless already changed)
            if ($transactionStock->getStock() === $this) {
                $transactionStock->setStock(null);
            }
        }

        return $this;
    }
}
