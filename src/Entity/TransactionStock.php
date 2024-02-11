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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité de la classe TransactionStock (Opération boursière).
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
#[ORM\Entity(repositoryClass: TransactionStockRepository::class)]
class TransactionStock
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Titre associé.
     */
    #[ORM\ManyToOne(targetEntity: Stock::class, inversedBy: 'transactionStocks')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stock $stock = null;

    /**
     * Compte titre associé.
     */
    #[ORM\ManyToOne(targetEntity: Account::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    /**
     * Position d'achat/vente ou autre.
     */
    #[ORM\Column(type: 'position')]
    private ?StockPosition $position = null;

    /**
     * Nombre d'actions achétés ou vendus.
     */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $volume = null;

    /**
     * Cours au moment de l'opération.
     */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $price = null;

    /**
     * Commmissions ou frais.
     */
    #[ORM\Column(type: Types::FLOAT)]
    #[Assert\NotBlank]
    private ?float $fee = 0;

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
