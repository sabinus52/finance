<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\VehicleRepository;
use App\Values\Fuel;
use App\Values\VehicleType;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité de la classe Vehicle (Compta sur les véhicules).
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
#[ORM\Entity(repositoryClass: VehicleRepository::class)]
class Vehicle implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Marque du véhicule.
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private ?string $brand = null;

    /**
     * Modèle du véhicule.
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private ?string $model = null;

    /**
     * Type de véhicule.
     */
    #[ORM\Column(type: 'vehicle_type')]
    private ?VehicleType $type = null;

    /**
     * Immatriculation.
     */
    #[ORM\Column(type: Types::STRING, length: 10, nullable: true)]
    #[Assert\Length(max: 10)]
    private ?string $matriculation = null;

    /**
     * Type de carburant.
     */
    #[ORM\Column(type: 'fuel')]
    private ?Fuel $fuel = null;

    /**
     * Date de 1ère circulation.
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $registeredAt = null;

    /**
     * Kilométrage d'achat.
     */
    #[ORM\Column(type: Types::INTEGER, options: ['default' => 0])]
    #[Assert\NotBlank]
    private ?int $kilometer = 0;

    /**
     * Date d'achat.
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $boughtAt = null;

    /**
     * Date de vente.
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $soldAt = null;

    /**
     * Liste des transactions associés.
     *
     * @var Collection|TransactionVehicle[]
     */
    #[ORM\OneToMany(targetEntity: TransactionVehicle::class, mappedBy: 'vehicle', orphanRemoval: true)]
    private Collection $transactionVehicles;

    public function __construct()
    {
        $this->transactionVehicles = new ArrayCollection();
    }

    public function __toString(): string
    {
        if ('' === $this->brand) {
            return '';
        }

        return $this->getName();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(string $brand): self
    {
        $this->brand = $brand;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function getType(): ?VehicleType
    {
        return $this->type;
    }

    public function setType(VehicleType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getMatriculation(): ?string
    {
        return $this->matriculation;
    }

    public function setMatriculation(?string $matriculation): self
    {
        $this->matriculation = $matriculation;

        return $this;
    }

    public function getFuel(): ?Fuel
    {
        return $this->fuel;
    }

    public function setFuel(Fuel $fuel): self
    {
        $this->fuel = $fuel;

        return $this;
    }

    public function getRegisteredAt(): ?\DateTimeImmutable
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(?\DateTimeImmutable $registeredAt): self
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    public function getKilometer(): ?int
    {
        return $this->kilometer;
    }

    public function setKilometer(?int $kilometer): self
    {
        $this->kilometer = $kilometer;

        return $this;
    }

    public function getBoughtAt(): ?\DateTimeImmutable
    {
        return $this->boughtAt;
    }

    public function setBoughtAt(?\DateTimeImmutable $boughtAt): self
    {
        $this->boughtAt = $boughtAt;

        return $this;
    }

    public function getSoldAt(): ?\DateTimeImmutable
    {
        return $this->soldAt;
    }

    public function setSoldAt(?\DateTimeImmutable $soldAt): self
    {
        $this->soldAt = $soldAt;

        return $this;
    }

    public function getName(): string
    {
        return $this->getBrand().' '.$this->getModel();
    }

    /**
     * Indique si le véhicule a été vendu.
     */
    public function isSold(): bool
    {
        return $this->soldAt instanceof \DateTimeImmutable;
    }

    /**
     * Affiche le badge du statut du véhicule.
     */
    public function getStatusBadge(): string
    {
        if ($this->isSold()) {
            return '<span class="badge bg-danger text-uppercase">vendu</span>';
        }

        return '<span class="badge bg-success text-uppercase">en cours</span>';
    }

    /**
     * @return Collection|TransactionVehicle[]
     */
    public function getTransactionVehicles(): Collection
    {
        return $this->transactionVehicles;
    }

    public function addTransactionVehicle(TransactionVehicle $transactionVehicle): self
    {
        if (!$this->transactionVehicles->contains($transactionVehicle)) {
            $this->transactionVehicles[] = $transactionVehicle;
            $transactionVehicle->setVehicle($this);
        }

        return $this;
    }

    public function removeTransactionVehicle(TransactionVehicle $transactionVehicle): self
    {
        // set the owning side to null (unless already changed)
        if ($this->transactionVehicles->removeElement($transactionVehicle) && $transactionVehicle->getVehicle() === $this) {
            $transactionVehicle->setVehicle(null);
        }

        return $this;
    }
}
