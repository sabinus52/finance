<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\StockPortfolioRepository;
use App\Values\StockPosition;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité de la classe StockPortfolio (Portefeuille boursier).
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @ORM\Entity(repositoryClass=StockPortfolioRepository::class)
 */
class StockPortfolio
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id; /** @phpstan-ignore-line */

    /**
     * Date de l'opération.
     *
     * @var DateTime
     *
     * @ORM\Column(type="date")
     */
    private $date;

    /**
     * Position d'achat/vente ou autre.
     *
     * @var StockPosition
     *
     * @ORM\Column(type="position")
     */
    private $position;

    /**
     * Nombre d'actions achétés ou vendus.
     *
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $volume;

    /**
     * Cours au moment de l'opération.
     *
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $price;

    /**
     * Commmissions ou frais.
     *
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $fee;

    /**
     * Coût total.
     *
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $total;

    /**
     * Opération sur l'action.
     *
     * @var Stock
     *
     * @ORM\ManyToOne(targetEntity=Stock::class, inversedBy="stockPortfolios")
     * @ORM\JoinColumn(nullable=false)
     */
    private $stock;

    /**
     * Compte bancaire associé.
     *
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="stockPortfolios")
     * @ORM\JoinColumn(nullable=false)
     */
    private $account;

    /**
     * Transaction associé.
     *
     * @var Transaction
     *
     * @ORM\OneToOne(targetEntity=Transaction::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=true)
     */
    private $transaction;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getPosition(): ?StockPosition
    {
        return $this->position;
    }

    public function setPosition(StockPosition $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getVolume(): ?float
    {
        return $this->volume;
    }

    public function setVolume(?float $volume): self
    {
        $this->volume = $volume;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getFee(): ?float
    {
        return $this->fee;
    }

    public function setFee(?float $fee): self
    {
        $this->fee = $fee;

        return $this;
    }

    public function getTotal(): ?float
    {
        return $this->total;
    }

    public function setTotal(float $total): self
    {
        $this->total = $total;

        return $this;
    }

    public function getStock(): ?Stock
    {
        return $this->stock;
    }

    public function setStock(?Stock $stock): self
    {
        $this->stock = $stock;

        return $this;
    }

    public function getAccount(): ?Account
    {
        return $this->account;
    }

    public function setAccount(?Account $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(Transaction $transaction): self
    {
        $this->transaction = $transaction;

        return $this;
    }
}
