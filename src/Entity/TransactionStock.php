<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\TransactionStockRepository;
use App\Values\StockPosition;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité de la classe TransactionStock (Opération boursière).
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @ORM\Entity(repositoryClass=TransactionStockRepository::class)
 */
class TransactionStock
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id; /** @phpstan-ignore-line */

    /**
     * Titre associé.
     *
     * @var Stock
     *
     * @ORM\ManyToOne(targetEntity=Stock::class, inversedBy="transactionStocks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $stock;

    /**
     * Compte titre associé.
     *
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity=Account::class, cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $account;

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
     * @ORM\Column(type="float")
     * @Assert\NotBlank
     */
    private $fee;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->fee = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getPosition(): ?StockPosition
    {
        return $this->position;
    }

    public function setPosition(StockPosition $position): self
    {
        $this->position = $position;

        return $this;
    }

    public function getPositionValue(): int
    {
        return $this->position->getValue();
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
}
