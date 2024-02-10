<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Import;

use App\Entity\Account;
use App\Entity\Category;
use App\Entity\Recipient;
use App\Values\Payment;
use App\Values\TransactionType;

/**
 * Element trouvé à importer.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 *
 * @SuppressWarnings(PHPMD.StaticAccess)
 */
class QifItem implements \Stringable
{
    /**
     * @var \DateTime
     */
    private $date;

    /**
     * @var string
     */
    private $account;

    /**
     * @var TransactionType
     */
    private $type;

    /**
     * @var float
     */
    private $amount;

    /**
     * @var string
     */
    private $recipient;

    /**
     * @var string
     */
    private $category;

    /**
     * @var Payment
     */
    private $payment;

    /**
     * @var int
     */
    private $state;

    /**
     * @var string
     */
    private $memo;

    /**
     * @param AssocDatas $assocDatas liste des données associées (Account, Recepient, Category)
     */
    public function __construct(private readonly AssocDatas $assocDatas)
    {
        $this->setType(TransactionType::STANDARD);
    }

    public function __toString(): string
    {
        return sprintf('%s | %s | %s | %s | %s', $this->date->format('d/m/Y'), $this->getAccount()->getFullName(), $this->amount, $this->getRecipient()->getName(), $this->getCategory()->getFullName());
    }

    public function setDate(string $date, string $format = QifParser::DATE_FORMAT): self
    {
        $this->date = \DateTime::createFromFormat($format, $date); /** @phpstan-ignore-line */
        if (false === $this->date) {
            $this->date = new \DateTime('1970-01-01');
        }

        return $this;
    }

    public function setAccount(string $account): self
    {
        $this->account = $account;

        return $this;
    }

    public function setType(int $type): self
    {
        $this->type = new TransactionType($type);

        return $this;
    }

    /**
     * @param string|float $amount
     *
     * @return self
     */
    public function setAmount($amount): self
    {
        if (!is_float($amount)) {
            $this->amount = (float) str_replace(',', '.', $amount);
        } else {
            $this->amount = $amount;
        }

        return $this;
    }

    public function setRecipient(string $recipient): self
    {
        $this->recipient = ('' === $recipient) ? Recipient::VIRT_NAME : $recipient;

        return $this;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function setPayment(int $payment): self
    {
        $this->payment = new Payment($payment);

        return $this;
    }

    public function setState(string $state): self
    {
        $this->state = ('R' === $state) ? 1 : 0;

        return $this;
    }

    public function setMemo(?string $memo): self
    {
        $this->memo = $memo;

        return $this;
    }

    public function getDate(): \DateTime
    {
        return $this->date;
    }

    public function getAccount(): Account
    {
        return $this->assocDatas->getAccount($this->account);
    }

    public function getType(): TransactionType
    {
        return $this->type;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function getRecipient(): Recipient
    {
        return $this->assocDatas->getRecipient($this->recipient);
    }

    public function getCategory(): Category
    {
        return $this->assocDatas->getCategory($this->category, $this->getAmount());
    }

    public function getPayment(): Payment
    {
        if (null === $this->payment) {
            return ($this->amount > 0) ? new Payment(Payment::DEPOT) : new Payment(Payment::PRELEVEMENT);
        }

        return $this->payment;
    }

    public function getMemo(): ?string
    {
        if ('' === $this->memo || '(null)' === $this->memo) {
            return null;
        }

        return $this->memo;
    }

    public function getState(): int
    {
        return $this->state;
    }
}
