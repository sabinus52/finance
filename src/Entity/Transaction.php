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
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TransactionRepository::class)
 */
class Transaction
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id; /** @phpstan-ignore-line */

    /**
     * Date de la Transaction.
     *
     * @var DateTime
     *
     * @ORM\Column(type="date")
     */
    private $date;

    /**
     * Montant de la transaction.
     *
     * @var float
     *
     * @ORM\Column(type="float")
     */
    private $amount;

    /**
     * Compte bancaire associé.
     *
     * @var Account
     *
     * @ORM\ManyToOne(targetEntity=Account::class, inversedBy="transactions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $account;

    /**
     * Moyen de paiement.
     *
     * @var Payment
     *
     * @ORM\Column(type="payment")
     */
    private $payment;

    /**
     * Béneficiaire.
     *
     * @var Recipient
     *
     * @ORM\ManyToOne(targetEntity=Recipient::class, inversedBy="transactions")
     */
    private $recipient;

    /**
     * Catégorie de la transaction.
     *
     * @var Category
     *
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="transactions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $category;

    /**
     * Statut (rapproché, etc).
     *
     * @var int
     *
     * @ORM\Column(type="smallint")
     */
    private $state;

    /**
     * Information sur la transaction.
     *
     * @var string
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $memo;

    /**
     * Transaction associé pour les virements.
     *
     * @var Transaction
     *
     * @ORM\OneToOne(targetEntity=Transaction::class, cascade={"persist", "remove"})
     */
    private $transfer;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): ?DateTime
    {
        return $this->date;
    }

    public function setDate(DateTime $date): self
    {
        $this->date = $date;

        return $this;
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

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): self
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
}
