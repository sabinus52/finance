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
use App\Values\AccountBalance;
use App\Values\AccountType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité de la classe Account (compte bancaire ou contrat d'assurance vie).
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
#[ORM\Entity(repositoryClass: AccountRepository::class)]
class Account implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Type du compte.
     */
    #[ORM\Column(type: 'account_type')]
    private ?AccountType $type = null;

    /**
     * Numéro du compte bancaire.
     */
    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    #[Assert\Length(max: 20)]
    private ?string $number = null;

    /**
     * Nom du compte.
     */
    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private ?string $name = null;

    /**
     * Nom court.
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private ?string $shortName = null;

    /**
     * Groupe d'appartenance.
     */
    #[ORM\Column(type: Types::SMALLINT, options: ['default' => 0])]
    private ?int $unit = 0;

    /**
     * Solde initial du compte.
     */
    #[ORM\Column(type: Types::FLOAT, options: ['default' => 0])]
    #[Assert\NotBlank]
    private ?float $initial = 0;

    /**
     * Devise du compte.
     */
    #[ORM\Column(type: Types::STRING, length: 3)]
    #[Assert\NotBlank]
    private string $currency = 'EUR';

    /**
     * Date d'ouverture du compte.
     */
    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTime $openedAt = null;

    /**
     * Date de fermeture ou null si en cours.
     */
    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $closedAt = null;

    /**
     * Montant du découvert autorisé.
     */
    #[ORM\Column(type: Types::FLOAT)]
    #[Assert\NotBlank]
    private ?float $overdraft = 0;

    /**
     * Metadata des différents soldes calculés.
     */
    #[ORM\Column(type: Types::OBJECT, nullable: true)]
    private AccountBalance $balance;

    #[ORM\ManyToOne(targetEntity: Institution::class, inversedBy: 'accounts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private ?Institution $institution = null;

    /**
     * Compte associé pour les tes transactions (Ex : PEA -> PEA Caisse.
     */
    #[ORM\OneToOne(targetEntity: self::class, cascade: ['persist', 'remove'])]
    private ?Account $accAssoc = null;

    /**
     * @var Collection|Transaction[]
     */
    #[ORM\OneToMany(targetEntity: Transaction::class, mappedBy: 'account')]
    private Collection $transactions;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->balance = new AccountBalance();
        $this->transactions = new ArrayCollection();
    }

    public function __toString(): string
    {
        if ('' === $this->name || '0' === $this->name) {
            return '';
        }

        return $this->getFullName();
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

    public function getTypeCode(): int
    {
        return $this->type->getTypeCode();
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

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function getOpenedAt(): ?\DateTime
    {
        return $this->openedAt;
    }

    public function setOpenedAt(?\DateTime $openedAt): self
    {
        $this->openedAt = $openedAt;

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

    public function getOverdraft(): ?float
    {
        return $this->overdraft;
    }

    public function setOverdraft(?float $overdraft): self
    {
        $this->overdraft = $overdraft;

        return $this;
    }

    public function getBalance(): AccountBalance
    {
        return $this->balance;
    }

    public function setBalance(AccountBalance $balance): self
    {
        $this->balance = $balance;

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
     * @return Collection|Transaction[]
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
        // set the owning side to null (unless already changed)
        if ($this->transactions->removeElement($transaction) && $transaction->getAccount() === $this) {
            $transaction->setAccount(null);
        }

        return $this;
    }

    /**
     * Retourne le nom complet organisme + nom du compte.
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
     */
    public function isClosed(): bool
    {
        return $this->closedAt instanceof \DateTime;
    }

    /**
     * Affiche le badge du statut du compte ou contrat.
     */
    public function getStatusBadge(): string
    {
        if ($this->isClosed()) {
            return '<span class="badge bg-danger text-uppercase">cloturé</span>';
        }

        return '<span class="badge bg-success text-uppercase">ouvert</span>';
    }

    /**
     * Retourne la performance du placement.
     */
    public function getInvestPerformance(): ?float
    {
        if (0.0 === $this->balance->getInvestment()) {
            return null;
        }

        return round($this->getInvestGain() / $this->balance->getInvestment(), 5);
    }

    public function getInvestValuation(): float
    {
        return $this->balance->getBalance() + $this->balance->getRepurchase();
    }

    public function getInvestGain(): float
    {
        return $this->balance->getBalance() + $this->balance->getRepurchase() - $this->balance->getInvestment();
    }
}
