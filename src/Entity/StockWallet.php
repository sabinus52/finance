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
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité de la classe StockWallet (Portefeuille boursier) = 1 ligne du portefeuille.
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
     * @ORM\ManyToOne(targetEntity=Account::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private $account;

    /**
     * Nombre d'actions contenus dans le portefeuille.
     *
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $volume;

    /**
     * Dernier cours en date de l'action.
     *
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $price;

    /**
     * Date du prix en cours.
     *
     * @var DateTime
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $priceDate;

    /**
     * Montant investi.
     *
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $invest;

    /**
     * Somme des dividendes reçues.
     *
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $dividend;

    /**
     * Commissions.
     *
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $fee;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->volume = 0.0;
        $this->price = 0.0;
        $this->invest = 0.0;
        $this->dividend = 0.0;
        $this->fee = 0.0;
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

    public function getPriceDate(): ?DateTime
    {
        return $this->priceDate;
    }

    public function setPriceDate(DateTime $priceDate): self
    {
        $this->priceDate = clone $priceDate;
        $this->priceDate->modify('last day of this month');

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
     *
     * @return float
     */
    public function getValuation(): float
    {
        return $this->volume * $this->price;
    }

    /**
     * Retourne le gain sur cours.
     *
     * @return float
     */
    public function getGainOnCost(): float
    {
        return $this->getValuation() - $this->invest;
    }

    /**
     * Retourne le gain total.
     *
     * @return float
     */
    public function getGainTotal(): float
    {
        return $this->getGainOnCost() + $this->dividend;
    }

    /**
     * Retourne le rendement total.
     *
     * @return float
     */
    public function getPerformance(): float
    {
        return $this->getGainTotal() / $this->invest;
    }

    /**
     * Traitement de l'achat d'un titre boursier.
     *
     * @param float $volume
     * @param float $price
     * @param float $fee
     *
     * @return self
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
     *
     * @param float $volume
     * @param float $price
     * @param float $fee
     *
     * @return self
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
     *
     * @param float $volume
     * @param float $price
     * @param float $fee
     *
     * @return self
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
     * @param float       $volume
     * @param float       $price
     * @param float       $fee
     * @param StockWallet $stockWallet Aancien titre pour récuperer l'investissement
     *
     * @return self
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
     *
     * @param float $dividend
     *
     * @return self
     */
    public function doDividend(float $dividend): self
    {
        $this->dividend += $dividend;

        return $this;
    }
}
