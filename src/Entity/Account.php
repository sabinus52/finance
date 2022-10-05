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
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité de la classe Account (compte bancaire ou contrat d'assurance vie).
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @ORM\Entity(repositoryClass=AccountRepository::class)
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
     * @var int
     *
     * @ORM\Column(type="smallint")
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
     * @ORM\Column(type="string", length=30)
     * @Assert\NotBlank
     * @Assert\Length(max=30)
     */
    private $name;

    /**
     * Solde initial du compte.
     *
     * @var float
     *
     * @ORM\Column(type="float")
     * @Assert\NotBlank
     */
    private $balance;

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
     * @var DateTimeInterface
     *
     * @ORM\Column(type="date")
     * @Assert\NotBlank
     */
    private $openedAt;

    /**
     * Date de fermeture ou null si en cours.
     *
     * @var DateTimeInterface
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
     * @var Institution
     *
     * @ORM\ManyToOne(targetEntity=Institution::class, inversedBy="accounts")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotBlank
     */
    private $institution;

    public function __construct()
    {
        $this->balance = 0;
        $this->currency = 'EUR';
        $this->overdraft = 0;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function setType(int $type): self
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

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function setBalance(?float $balance): self
    {
        $this->balance = $balance;

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

    public function getOpenedAt(): ?DateTimeInterface
    {
        return $this->openedAt;
    }

    public function setOpenedAt(?DateTimeInterface $openedAt): self
    {
        $this->openedAt = $openedAt;

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

    public function getOverdraft(): ?float
    {
        return $this->overdraft;
    }

    public function setOverdraft(?float $overdraft): self
    {
        $this->overdraft = $overdraft;

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

    /**
     * Indique si le compte est fermé ou pas.
     *
     * @return bool
     */
    public function isClosed(): bool
    {
        return null === $this->closedAt;
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
