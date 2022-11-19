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
use Doctrine\ORM\Mapping as ORM;

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
     */
    private $codeISIN;

    /**
     * Nom de l'action.
     *
     * @var string
     *
     * @ORM\Column(type="string", length=100, unique=true)
     */
    private $name;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity=StockPrice::class, mappedBy="stock", orphanRemoval=true)
     */
    private $stockPrices;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity=StockPortfolio::class, mappedBy="stock")
     */
    private $stockPortfolios;

    public function __construct()
    {
        $this->stockPrices = new ArrayCollection();
        $this->stockPortfolios = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCodeISIN(): ?string
    {
        return $this->codeISIN;
    }

    public function setCodeISIN(string $codeISIN): self
    {
        $this->codeISIN = $codeISIN;

        return $this;
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
     * @return Collection<int, StockPortfolio>
     */
    public function getStockPortfolios(): Collection
    {
        return $this->stockPortfolios;
    }

    public function addStockPortfolio(StockPortfolio $stockPortfolio): self
    {
        if (!$this->stockPortfolios->contains($stockPortfolio)) {
            $this->stockPortfolios[] = $stockPortfolio;
            $stockPortfolio->setStock($this);
        }

        return $this;
    }

    public function removeStockPortfolio(StockPortfolio $stockPortfolio): self
    {
        if ($this->stockPortfolios->removeElement($stockPortfolio)) {
            // set the owning side to null (unless already changed)
            if ($stockPortfolio->getStock() === $this) {
                $stockPortfolio->setStock(null);
            }
        }

        return $this;
    }
}
