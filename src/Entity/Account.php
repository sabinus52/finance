<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\AccountRepository;
use App\Values\AccountType;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité de la classe Account (compte bancaire ou contrat d'assurance vie).
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @ORM\Entity(repositoryClass=AccountRepository::class)
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Account
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id; /** @phpstan-ignore-line */

    /**
     * Type du compte.
     *
     * @var AccountType
     *
     * @ORM\Column(type="account_type")
     */
    private $type;

    /**
     * Numéro du compte bancaire.
     *
     * @var string
     *
     * @ORM\Column(type="string", length=20, nullable=true)
     * @Assert\Length(max=20)
     */
    private $number;

    /**
     * Nom du compte.
     *
     * @var string
     *
     * @ORM\Column(type="string", length=50)
     * @Assert\NotBlank
     * @Assert\Length(max=50)
     */
    private $name;

    /**
     * Nom court.
     *
     * @var string
     *
     * @ORM\Column(type="string", length=20)
     * @Assert\NotBlank
     * @Assert\Length(max=20)
     */
    private $shortName;

    /**
     * Groupe d'appartenance.
     *
     * @var int
     *
     * @ORM\Column(type="smallint", options={"default": 0})
     */
    private $unit;

    /**
     * Solde initial du compte.
     *
     * @var float
     *
     * @ORM\Column(type="float", options={"default": 0})
     * @Assert\NotBlank
     */
    private $initial;

    /**
     * Solde courant du compte.
     *
     * @var float
     *
     * @ORM\Column(type="float", options={"default": 0})
     */
    private $balance;

    /**
     * Solde rapproché.
     *
     * @var float
     *
     * @ORM\Column(type="float", options={"default": 0})
     */
    private $reconBalance;

    /**
     * Rapprochement en cours à solder.
     *
     * @var float
     *
     * @ORM\Column(type="float", options={"default": 0})
     */
    private $reconCurrent;

    /**
     * Devise du compte.
     *
     * @var string
     *
     * @ORM\Column(type="string", length=3)
     * @Assert\NotBlank
     */
    private $currency;

    /**
     * Date d'ouverture du compte.
     *
     * @var DateTime
     *
     * @ORM\Column(type="date")
     * @Assert\NotBlank
     */
    private $openedAt;

    /**
     * Date de fermeture ou null si en cours.
     *
     * @var DateTime
     *
     * @ORM\Column(type="date", nullable=true)
     */
    private $closedAt;

    /**
     * Montant du découvert autorisé.
     *
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotBlank
     */
    private $overdraft;

    /**
     * Montant investi dans les placements.
     *
     * @var float
     *
     * @ORM\Column(type="float", options={"default": 0})
     */
    private $invested;

    /**
     * @var Institution
     *
     * @ORM\ManyToOne(targetEntity=Institution::class, inversedBy="accounts")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotBlank
     */
    private $institution;

    /**
     * Compte associé pour les tes transactions (Ex : PEA -> PEA Caisse.
     *
     * @var Account
     *
     * @ORM\OneToOne(targetEntity=Account::class, cascade={"persist", "remove"})
     */
    private $accAssoc;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity=StockPortfolio::class, mappedBy="account")
     */
    private $stockPortfolios;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity=Transaction::class, mappedBy="account")
     */
    private $transactions;

    public function __construct()
    {
        $this->unit = 0;
        $this->initial = 0;
        $this->balance = 0;
        $this->reconBalance = 0;
        $this->reconCurrent = 0;
        $this->currency = 'EUR';
        $this->overdraft = 0;
        $this->invested = 0;
        $this->stockPortfolios = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?AccountType
    {
        return $this->type;
    }

    public function setType(AccountType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        $this->number = $number;

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

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function setShortName(string $shortName): self
    {
        $this->shortName = $shortName;

        return $this;
    }

    public function getUnit(): ?int
    {
        return $this->unit;
    }

    public function setUnit(?int $unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function getInitial(): ?float
    {
        return $this->initial;
    }

    public function setInitial(float $initial): self
    {
        $this->initial = $initial;

        return $this;
    }

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function setBalance(?float $balance): self
    {
        $this->balance = $balance;

        return $this;
    }

    public function getReconBalance(): ?float
    {
        return $this->reconBalance;
    }

    public function setReconBalance(float $reconBalance): self
    {
        $this->reconBalance = $reconBalance;

        return $this;
    }

    public function getReconCurrent(): ?float
    {
        return $this->reconCurrent;
    }

    public function setReconCurrent(float $reconCurrent): self
    {
        $this->reconCurrent = $reconCurrent;

        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getOpenedAt(): ?DateTime
    {
        return $this->openedAt;
    }

    public function setOpenedAt(?DateTime $openedAt): self
    {
        $this->openedAt = $openedAt;

        return $this;
    }

    public function getClosedAt(): ?DateTime
    {
        return $this->closedAt;
    }

    public function setClosedAt(?DateTime $closedAt): self
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    public function getOverdraft(): ?float
    {
        return $this->overdraft;
    }

    public function setOverdraft(?float $overdraft): self
    {
        $this->overdraft = $overdraft;

        return $this;
    }

    public function getInvested(): ?float
    {
        return $this->invested;
    }

    public function setInvested(?float $invested): self
    {
        $this->invested = $invested;

        return $this;
    }

    public function getInstitution(): ?Institution
    {
        return $this->institution;
    }

    public function setInstitution(?Institution $institution): self
    {
        $this->institution = $institution;

        return $this;
    }

    public function getAccAssoc(): ?self
    {
        return $this->accAssoc;
    }

    public function setAccAssoc(?self $accAssoc): self
    {
        $this->accAssoc = $accAssoc;

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
            $stockPortfolio->setAccount($this);
        }

        return $this;
    }

    public function removeStockPortfolio(StockPortfolio $stockPortfolio): self
    {
        if ($this->stockPortfolios->removeElement($stockPortfolio)) {
            // set the owning side to null (unless already changed)
            if ($stockPortfolio->getAccount() === $this) {
                $stockPortfolio->setAccount(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Transaction>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setAccount($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->removeElement($transaction)) {
            // set the owning side to null (unless already changed)
            if ($transaction->getAccount() === $this) {
                $transaction->setAccount(null);
            }
        }

        return $this;
    }

    /**
     * Retourne le nom complet organisme + nom du compte.
     *
     * @return string
     */
    public function getFullName(): string
    {
        return sprintf('%s %s', $this->getInstitution()->getName(), $this->getName());
    }

    public function getFullShortName(): string
    {
        return sprintf('%s %s', $this->getInstitution()->getShortName(), $this->getShortName());
    }

    public function getName4Import(): string
    {
        return sprintf('%s %s', $this->getInstitution()->getShortName(), $this->getName());
    }

    /**
     * Indique si le compte est fermé ou pas.
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return null !== $this->closedAt;
    }

    /**
     * Affiche le badge du statut du compte ou contrat.
     *
     * @return string
     */
    public function getStatusBadge(): string
    {
        if ($this->isClosed()) {
            return '<span class="badge bg-danger text-uppercase">cloturé</span>';
        }

        return '<span class="badge bg-success text-uppercase">ouvert</span>';
    }
}
