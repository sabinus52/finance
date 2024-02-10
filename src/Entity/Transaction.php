<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\TransactionRepository;
use App\Values\Payment;
use App\Values\TransactionType;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction implements \Stringable
{
    final public const STATE_NONE = 0;
    final public const STATE_RECONCILIED = 1;
    final public const STATE_RECONTEMP = 9;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id; /** @phpstan-ignore-line */

    /**
     * Date de la Transaction.
     *
     * @var \DateTime
     */
    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank]
    private $date;

    /**
     * Montant de la transaction.
     *
     * @var float
     */
    #[ORM\Column(type: 'float')]
    #[Assert\NotBlank]
    private $amount;

    /**
     * Solde du compte.
     *
     * @var float
     */
    #[ORM\Column(type: 'float', options: ['default' => 0])]
    private $balance;

    /**
     * Compte bancaire associé.
     *
     * @var Account
     */
    #[ORM\ManyToOne(targetEntity: Account::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private $account;

    /**
     * Type de la transaction.
     *
     * @var TransactionType
     */
    #[ORM\Column(type: 'transaction_type', options: ['default' => 0])]
    private $type;

    /**
     * Moyen de paiement.
     *
     * @var Payment
     */
    #[ORM\Column(type: 'payment')]
    #[Assert\NotBlank]
    private $payment;

    /**
     * Béneficiaire.
     *
     * @var Recipient
     */
    #[ORM\ManyToOne(targetEntity: Recipient::class, inversedBy: 'transactions')]
    #[Assert\NotBlank]
    private $recipient;

    /**
     * Catégorie de la transaction.
     *
     * @var Category
     */
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private $category;

    /**
     * Statut (rapproché, etc).
     *
     * @var int
     */
    #[ORM\Column(type: 'smallint')]
    private $state;

    /**
     * Information sur la transaction.
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $memo;

    /**
     * Transaction associé pour les virements.
     *
     * @var Transaction
     */
    #[ORM\OneToOne(targetEntity: self::class, cascade: ['persist', 'remove'])]
    private $transfer;

    /**
     * Projet associé.
     *
     * @var Project
     */
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'transactions')]
    private $project;

    /**
     * Transaction du véhicule associé (kilométrage).
     *
     * @var TransactionVehicle
     */
    #[ORM\ManyToOne(targetEntity: TransactionVehicle::class, cascade: ['persist', 'remove'])]
    #[Assert\Valid]
    private $transactionVehicle;

    /**
     * Transaction de l'opération boursière.
     *
     * @var TransactionStock
     */
    #[ORM\ManyToOne(targetEntity: TransactionStock::class, cascade: ['persist', 'remove'])]
    private $transactionStock;

    /**
     * Constructeur.
     */
    public function __construct()
    {
        $this->balance = 0;
        $this->state = 0;
        $this->date = new \DateTime();
        $this->type = new TransactionType(TransactionType::STANDARD);
    }

    public function __toString(): string
    {
        if (!$this->getId()) {
            return '';
        }

        return sprintf('%s € du %s pour %s', $this->getAmount(), $this->getDate()->format('d/m/Y'), $this->getRecipient()->getName());
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(?\DateTime $date): self
    {
        $this->date = $date;

        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->amount;
    }

    public function setAmount(?float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getBalance(): ?float
    {
        return $this->balance;
    }

    public function setBalance(float $balance): self
    {
        $this->balance = $balance;

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

    public function getType(): ?TransactionType
    {
        return $this->type;
    }

    public function setType(TransactionType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setTypeValue(int $type): self
    {
        $this->type = new TransactionType($type);

        return $this;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): self
    {
        $this->payment = $payment;

        return $this;
    }

    public function setPaymentValue(int $payment): self
    {
        $this->payment = new Payment($payment);

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

    public function getState(): ?int
    {
        return $this->state;
    }

    public function setState(int $state): self
    {
        $this->state = $state;

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

    public function getTransfer(): ?self
    {
        return $this->transfer;
    }

    public function setTransfer(?self $transfer): self
    {
        $this->transfer = $transfer;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getTransactionVehicle(): ?TransactionVehicle
    {
        return $this->transactionVehicle;
    }

    public function setTransactionVehicle(?TransactionVehicle $transactionVehicle): self
    {
        $this->transactionVehicle = $transactionVehicle;

        return $this;
    }

    public function getTransactionStock(): ?TransactionStock
    {
        return $this->transactionStock;
    }

    public function setTransactionStock(?TransactionStock $transactionStock): self
    {
        $this->transactionStock = $transactionStock;

        return $this;
    }
}
