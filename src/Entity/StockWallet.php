<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\StockWalletRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité de la classe StockWallet (Portefeuille boursier).
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @ORM\Entity(repositoryClass=StockWalletRepository::class)
 */
class StockWallet
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id; /** @phpstan-ignore-line */

    /**
     * Nombre d'actions contenus dans le portefeuille.
     *
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $volume;

    /**
     * Action boursière du portefeuille.
     *
     * @var Stock
     *
     * @ORM\ManyToOne(targetEntity=Stock::class, inversedBy="stockWallets")
     * @ORM\JoinColumn(nullable=false)
     */
    private $stock;

    /**
     * Compte titres ou PEA associé.
     *
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="stockWallets")
     * @ORM\JoinColumn(nullable=false)
     */
    private $account;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVolume(): ?float
    {
        return $this->volume;
    }

    public function setVolume(float $volume): self
    {
        $this->volume = $volume;

        return $this;
    }

    public function addVolume(float $volume): self
    {
        $this->volume += $volume;

        return $this;
    }

    public function subVolume(float $volume): self
    {
        $this->volume -= $volume;

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
}
