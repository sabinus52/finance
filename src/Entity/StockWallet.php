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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité de la classe StockWallet (Portefeuille boursier) = 1 ligne du portefeuille.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
#[ORM\Entity(repositoryClass: StockWalletRepository::class)]
class StockWallet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Action boursière du portefeuille.
     */
    #[ORM\ManyToOne(targetEntity: Stock::class, inversedBy: 'stockWallets')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Stock $stock = null;

    /**
     * Compte titres ou PEA associé.
     */
    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Account $account = null;

    /**
     * Nombre d'actions contenus dans le portefeuille.
     */
    #[ORM\Column(type: Types::FLOAT)]
    private float $volume = 0.0;

    /**
     * Dernier cours en date de l'action.
     */
    #[ORM\Column(type: Types::FLOAT)]
    private float $price = 0.0;

    /**
     * Date du prix en cours.
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $priceDate = null;

    /**
     * Montant investi.
     */
    #[ORM\Column(type: Types::FLOAT)]
    private float $invest = 0.0;

    /**
     * Somme des dividendes reçues.
     */
    #[ORM\Column(type: Types::FLOAT)]
    private float $dividend = 0.0;

    /**
     * Commissions.
     */
    #[ORM\Column(type: Types::FLOAT)]
    private float $fee = 0.0;

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

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getPriceDate(): ?\DateTimeImmutable
    {
        return $this->priceDate;
    }

    public function setPriceDate(\DateTimeImmutable $priceDate): self
    {
        $this->priceDate = $priceDate->modify('last day of this month');

        return $this;
    }

    public function getInvest(): ?float
    {
        return $this->invest;
    }

    public function setInvest(float $invest): self
    {
        $this->invest = $invest;

        return $this;
    }

    public function getDividend(): ?float
    {
        return $this->dividend;
    }

    public function setDividend(float $dividend): self
    {
        $this->dividend = $dividend;

        return $this;
    }

    public function getFee(): ?float
    {
        return $this->fee;
    }

    public function setFee(float $fee): self
    {
        $this->fee = $fee;

        return $this;
    }

    /**
     * Retourne la valorisation en cours.
     */
    public function getValuation(): float
    {
        return $this->volume * $this->price;
    }

    /**
     * Retourne le gain sur cours.
     */
    public function getGainOnCost(): float
    {
        return $this->getValuation() - $this->invest;
    }

    /**
     * Retourne le gain total.
     */
    public function getGainTotal(): float
    {
        return $this->getGainOnCost() + $this->dividend;
    }

    /**
     * Retourne le rendement total.
     */
    public function getPerformance(): float
    {
        return $this->getGainTotal() / $this->invest;
    }

    /**
     * Traitement de l'achat d'un titre boursier.
     */
    public function doBuying(float $volume, float $price, float $fee): self
    {
        $this->addVolume($volume)->setPrice($price);
        $this->invest = $this->invest + ($volume * $price) + $fee;
        $this->fee += $fee;

        return $this;
    }

    /**
     * Traitement de la vente d'un titre boursier.
     */
    public function doSelling(float $volume, float $price, float $fee): self
    {
        $this->subVolume($volume)->setPrice($price);
        $this->invest = $this->invest + $fee - ($volume * $price);
        $this->fee += $fee;

        return $this;
    }

    /**
     * Traitement d'une fusion d'un titre boursier (vente de l'ancien).
     */
    public function doFusionSelling(float $volume, float $price, float $fee): self
    {
        $this->subVolume($volume)->setPrice($price);
        $this->invest += $fee;
        $this->fee += $fee;

        return $this;
    }

    /**
     * Traitement d'une fusion d'un titre boursier (achat du nouveau).
     *
     * @param StockWallet $stockWallet Aancien titre pour récuperer l'investissement
     */
    public function doFusionBuying(float $volume, float $price, float $fee, self $stockWallet): self
    {
        $this->addVolume($volume)->setPrice($price);
        $this->invest = $stockWallet->getInvest() + $fee;
        $this->fee += $fee;
        $stockWallet->setInvest(0.0);

        return $this;
    }

    /**
     * Traitement d'une reception de dividendes.
     */
    public function doDividend(float $dividend): self
    {
        $this->dividend += $dividend;

        return $this;
    }
}
