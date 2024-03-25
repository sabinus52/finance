<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\ScheduleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité de la classe Schedule.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
#[ORM\Entity(repositoryClass: ScheduleRepository::class)]
class Schedule
{
    /**
     * Liste des périodes.
     *
     * @var array<mixed>
     */
    private static array $periods = [
        'D' => ['day', 'Jour'],
        'W' => ['week', 'Semaine'],
        'M' => ['month', 'Mois'],
        'Y' => ['year', 'Année'],
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Statut de la planification actif ou pas.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $state = true;

    /**
     * Prochaine date de la transaction.
     */
    #[ORM\Column(type: Types::DATE_IMMUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeImmutable $doAt = null;

    /**
     * Frequence de la périodicité de la planification.
     */
    #[ORM\Column(type: Types::SMALLINT)]
    #[Assert\NotBlank]
    #[Assert\Type('int')]
    private ?int $frequency = 1;

    /**
     * Periode de la planification (jour, semaine, mois ou année).
     */
    #[ORM\Column(type: Types::STRING, length: 1)]
    #[Assert\NotBlank]
    private ?string $period = 'M';

    /**
     * Nombre de transaction à effectuer.
     */
    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $number = null;

    /**
     * Modèle associé.
     */
    #[ORM\OneToOne(targetEntity: Model::class, mappedBy: 'schedule', cascade: ['persist'])]
    private ?Model $model = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isState(): ?bool
    {
        return $this->state;
    }

    public function setState(bool $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getDoAt(): ?\DateTimeImmutable
    {
        return $this->doAt;
    }

    public function setDoAt(?\DateTimeImmutable $doAt): self
    {
        $this->doAt = $doAt;

        return $this;
    }

    /**
     * Retourne le badge de la prochaine date de la planification ou à défaut le statut.
     */
    public function getLastDateBadge(): string
    {
        if (false === $this->state) {
            return '<span class="badge badge-danger">Désactivé</span>';
        }

        $now = new \DateTimeImmutable();
        $color = 'success';
        if ($this->doAt < $now->modify('+ 10 days')) {
            $color = 'warning';
        }

        return sprintf('<span class="badge badge-%s">%s</span>', $color, $this->doAt->format('d/m/Y'));
    }

    /**
     * Remet la prochaine date de la planification.
     */
    public function setNextDoAt(): self
    {
        $period = new \DateInterval(sprintf('P%s%s', $this->getFrequency(), $this->getPeriod()));
        $this->doAt = $this->doAt->add($period);

        return $this;
    }

    public function getFrequency(): ?int
    {
        return $this->frequency;
    }

    public function setFrequency(?int $frequency): self
    {
        $this->frequency = $frequency;

        return $this;
    }

    public function getPeriod(): ?string
    {
        return $this->period;
    }

    public function setPeriod(?string $period): self
    {
        $this->period = $period;

        return $this;
    }

    public function getPeriodLabel(): string
    {
        return self::$periods[$this->period][1];
    }

    public function getNumber(): ?int
    {
        return $this->number;
    }

    public function setNumber(?int $number): self
    {
        $this->number = $number;

        return $this;
    }

    public function getModel(): ?Model
    {
        return $this->model;
    }

    public function setModel(?Model $model): self
    {
        // unset the owning side of the relation if necessary
        if (!$model instanceof Model && $this->model instanceof Model) {
            $this->model->setSchedule(null);
        }

        // set the owning side of the relation if necessary
        if ($model instanceof Model && $model->getSchedule() !== $this) {
            $model->setSchedule($this);
        }

        $this->model = $model;

        return $this;
    }
}
