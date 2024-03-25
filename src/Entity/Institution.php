<?php

declare(strict_types=1);

/**
 *  This file is part of My Finance Application.
 *  (c) Sabinus52 <sabinus52@gmail.com>
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace App\Entity;

use App\Repository\InstitutionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité de la classe Institution.
 *
 * @author Sabinus52 <sabinus52@gmail.com>
 */
#[ORM\Entity(repositoryClass: InstitutionRepository::class)]
class Institution implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * Nom de l'organisme.
     */
    #[ORM\Column(type: Types::STRING, length: 50)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private ?string $name = null;

    /**
     * Nom court.
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    private ?string $shortName = null;

    /**
     * Lien du site web.
     */
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    #[Assert\Url]
    #[Assert\Length(max: 255)]
    private ?string $link = null;

    /**
     * Code SWIFT de la banque.
     */
    #[ORM\Column(type: Types::STRING, length: 12, nullable: true)]
    #[Assert\Length(max: 12)]
    private ?string $codeSwift = null;

    /**
     * Image de l'organisme.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $logo = null;

    /**
     * @var Collection|Account[]
     */
    #[ORM\OneToMany(targetEntity: Account::class, mappedBy: 'institution')]
    private Collection $accounts;

    public function __construct()
    {
        $this->accounts = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name ?: '';
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getShortName(): ?string
    {
        return $this->shortName;
    }

    public function setShortName(string $shortName): self
    {
        $this->shortName = $shortName;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(?string $link): self
    {
        $this->link = $link;

        return $this;
    }

    public function getCodeSwift(): ?string
    {
        return $this->codeSwift;
    }

    public function setCodeSwift(?string $codeSwift): self
    {
        $this->codeSwift = $codeSwift;

        return $this;
    }

    public function getLogo(): ?string
    {
        return $this->logo;
    }

    public function setLogo(string $logo): self
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * @return Collection|Account[]
     */
    public function getAccounts(): Collection
    {
        return $this->accounts;
    }

    public function addAccount(Account $account): self
    {
        if (!$this->accounts->contains($account)) {
            $this->accounts[] = $account;
            $account->setInstitution($this);
        }

        return $this;
    }

    public function removeAccount(Account $account): self
    {
        // set the owning side to null (unless already changed)
        if ($this->accounts->removeElement($account) && $account->getInstitution() === $this) {
            $account->setInstitution(null);
        }

        return $this;
    }
}
