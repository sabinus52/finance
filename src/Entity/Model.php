<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\ModelRepository;
use App\Values\Payment;
use App\Values\TransactionType;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité de la classe Model.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
#[ORM\Entity(repositoryClass: ModelRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Model implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Montant de la transaction.
     */
    #[ORM\Column(type: Types::FLOAT)]
    #[Assert\NotBlank]
    private ?float $amount = null;

    /**
     * Compte bancaire associé.
     */
    #[ORM\ManyToOne(targetEntity: Account::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private ?Account $account = null;

    /**
     * Type de la transaction.
     */
    #[ORM\Column(type: 'transaction_type')]
    #[Assert\NotBlank]
    private TransactionType $type;

    /**
     * Moyen de paiement.
     */
    #[ORM\Column(type: 'payment')]
    #[Assert\NotBlank]
    private ?Payment $payment = null;

    /**
     * Béneficiaire.
     */
    #[ORM\ManyToOne(targetEntity: Recipient::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private ?Recipient $recipient = null;

    /**
     * Catégorie de la transaction.
     */
    #[ORM\ManyToOne(targetEntity: Category::class)]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private ?Category $category = null;

    /**
     * Information sur la transaction.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $memo = null;

    /**
     * Véhicule associé.
     */
    #[ORM\ManyToOne(targetEntity: Vehicle::class)]
    private ?Vehicle $vehicle = null;

    /**
     * Compte cible de virement.
     */
    #[ORM\ManyToOne(targetEntity: Account::class)]
    private ?Account $transfer = null;

    /**
     * Planification associé.
     */
    #[ORM\OneToOne(targetEntity: Schedule::class, inversedBy: 'model', cascade: ['persist', 'remove'])]
    private ?Schedule $schedule = null;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->type = new TransactionType(TransactionType::STANDARD);
    }

    public function __toString(): string
    {
        if (null === $this->getId()) {
            return '';
        }

        return sprintf('%s € pour %s', $this->getAmount(), $this->getRecipient()->getName());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): self
    {
        $this->amount = $amount;

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

    public function getType(): TransactionType
    {
        return $this->type;
    }

    public function setType(TransactionType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTypeValue(): int
    {
        return $this->type->getValue();
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(Payment $payment): self
    {
        $this->payment = $payment;

        return $this;
    }

    public function getRecipient(): ?Recipient
    {
        return $this->recipient;
    }

    public function setRecipient(?Recipient $recipient): self
    {
        $this->recipient = $recipient;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getMemo(): ?string
    {
        return $this->memo;
    }

    public function setMemo(?string $memo): self
    {
        $this->memo = $memo;

        return $this;
    }

    public function getVehicle(): ?Vehicle
    {
        return $this->vehicle;
    }

    public function setVehicle(?Vehicle $vehicle): self
    {
        $this->vehicle = $vehicle;

        return $this;
    }

    public function getTransfer(): ?Account
    {
        return $this->transfer;
    }

    public function setTransfer(?Account $transfer): self
    {
        $this->transfer = $transfer;

        return $this;
    }

    /**
     * Corrige le signe du montant (+/-) en fonction de la catégorie.
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function correctAmount(): void
    {
        if (Category::INCOME === $this->getCategory()->getType()) {
            $this->setAmount(abs($this->getAmount()));
        } elseif (Category::EXPENSE === $this->getCategory()->getType()) {
            $this->setAmount(abs($this->getAmount()) * -1);
        }
    }

    public function getSchedule(): ?Schedule
    {
        return $this->schedule;
    }

    public function setSchedule(?Schedule $schedule): self
    {
        $this->schedule = $schedule;

        return $this;
    }
}
