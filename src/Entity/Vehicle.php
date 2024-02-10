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
    #[ORM\Column(type: 'integer')]
    private $id; /** @phpstan-ignore-line */

    /**
     * Marque du véhicule.
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private $brand;

    /**
     * Modèle du véhicule.
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private $model;

    /**
     * Type de véhicule.
     *
     * @var VehicleType
     */
    #[ORM\Column(type: 'vehicle_type')]
    private $type;

    /**
     * Immatriculation.
     *
     * @var string
     */
    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Assert\Length(max: 10)]
    private $matriculation;

    /**
     * Type de carburant.
     *
     * @var Fuel
     */
    #[ORM\Column(type: 'fuel')]
    private $fuel;

    /**
     * Date de 1ère circulation.
     *
     * @var \DateTime
     */
    #[ORM\Column(type: 'date', nullable: true)]
    private $registeredAt;

    /**
     * Kilométrage d'achat.
     *
     * @var int
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\NotBlank]
    private $kilometer;

    /**
     * Date d'achat.
     *
     * @var \DateTime
     */
    #[ORM\Column(type: 'date')]
    #[Assert\NotBlank]
    private $boughtAt;

    /**
     * Date de vente.
     *
     * @var \DateTime
     */
    #[ORM\Column(type: 'date', nullable: true)]
    private $soldAt;

    /**
     * Liste des transactions associés.
     *
     * @var Collection
     */
    #[ORM\OneToMany(targetEntity: TransactionVehicle::class, mappedBy: 'vehicle', orphanRemoval: true)]
    private $transactionVehicles;

    public function __construct()
    {
        $this->kilometer = 0;
        $this->transactionVehicles = new ArrayCollection();
    }

    public function __toString(): string
    {
        if (!$this->brand) {
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

    public function getRegisteredAt(): ?\DateTime
    {
        return $this->registeredAt;
    }

    public function setRegisteredAt(?\DateTime $registeredAt): self
    {
        $this->registeredAt = $registeredAt;

        return $this;
    }

    public function getKilometer(): ?int
    {
        return $this->kilometer;
    }

    public function setKilometer(int $kilometer): self
    {
        $this->kilometer = $kilometer;

        return $this;
    }

    public function getBoughtAt(): ?\DateTime
    {
        return $this->boughtAt;
    }

    public function setBoughtAt(?\DateTime $boughtAt): self
    {
        $this->boughtAt = $boughtAt;

        return $this;
    }

    public function getSoldAt(): ?\DateTime
    {
        return $this->soldAt;
    }

    public function setSoldAt(?\DateTime $soldAt): self
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
     *
     * @return bool
     */
    public function isSold(): bool
    {
        return null !== $this->soldAt;
    }

    /**
     * Affiche le badge du statut du véhicule.
     *
     * @return string
     */
    public function getStatusBadge(): string
    {
        if ($this->isSold()) {
            return '<span class="badge bg-danger text-uppercase">vendu</span>';
        }

        return '<span class="badge bg-success text-uppercase">en cours</span>';
    }

    /**
     * @return Collection<int, TransactionVehicle>
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
        if ($this->transactionVehicles->removeElement($transactionVehicle)) {
            // set the owning side to null (unless already changed)
            if ($transactionVehicle->getVehicle() === $this) {
                $transactionVehicle->setVehicle(null);
            }
        }

        return $this;
    }
}
