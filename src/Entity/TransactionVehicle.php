<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\TransactionVehicleRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=TransactionVehicleRepository::class)
 */
class TransactionVehicle
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id; /** @phpstan-ignore-line */

    /**
     * Transaction associée.
     *
     * @var Transaction
     *
     * @ORM\OneToOne(targetEntity=Transaction::class, inversedBy="transactionVehicle", cascade={"persist", "remove"})
     * @ORM\JoinColumn(nullable=false)
     */
    private $transaction;

    /**
     * Véhicule associé.
     *
     * @var Vehicle
     *
     * @ORM\ManyToOne(targetEntity=Vehicle::class, inversedBy="transactionVehicles")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\NotBlank
     */
    private $vehicle;

    /**
     * Kilométrage.
     *
     * @var int
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    private $distance;

    /**
     * Volume d'essence.
     *
     * @var float
     *
     * @ORM\Column(type="float", nullable=true)
     */
    private $volume;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTransaction(): ?Transaction
    {
        return $this->transaction;
    }

    public function setTransaction(Transaction $transaction): self
    {
        $this->transaction = $transaction;

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

    public function getDistance(): ?int
    {
        return $this->distance;
    }

    public function setDistance(int $distance): self
    {
        $this->distance = $distance;

        return $this;
    }

    public function getVolume(): ?float
    {
        return $this->volume;
    }

    public function setVolume(?float $volume): self
    {
        $this->volume = $volume;

        return $this;
    }
}
