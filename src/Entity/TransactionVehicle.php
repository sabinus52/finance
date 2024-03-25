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
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TransactionVehicleRepository::class)]
class TransactionVehicle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Véhicule associé.
     */
    #[ORM\ManyToOne(targetEntity: Vehicle::class, inversedBy: 'transactionVehicles')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotBlank]
    private ?Vehicle $vehicle = null;

    /**
     * Kilométrage.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $distance = null;

    /**
     * Volume d'essence.
     */
    #[ORM\Column(type: Types::FLOAT, nullable: true)]
    private ?float $volume = null;

    public function getId(): ?int
    {
        return $this->id;
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
