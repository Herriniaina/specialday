<?php

namespace App\Infrastructure\Prestation\Entity;

use App\Infrastructure\Company\Entity\Company;
use App\Infrastructure\PrestationCategory\Entity\PrestationCategory;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'company_prestation')]
class Prestation
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 36)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(name: 'company_id', referencedColumnName: 'id', nullable: false)]
    private ?Company $company = null;

    #[ORM\ManyToOne(targetEntity: PrestationCategory::class)]
    #[ORM\JoinColumn(name: 'prestation_category_id', referencedColumnName: 'id', nullable: false)]
    private ?PrestationCategory $prestationCategory = null;

    #[ORM\Column(type: 'integer')]
    private ?int $priceAmount = null;

    #[ORM\Column(length: 10)]
    private ?string $priceCurrency = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $priceTaxIncluded = null;

    #[ORM\Column(type: 'integer')]
    private ?int $quantity = null;

    #[ORM\Column(length: 50)]
    private ?string $unit = null;

    #[ORM\Column(type: 'text', nullable: true, columnDefinition: 'TEXT DEFAULT NULL')]
    private ?string $notes = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(Company $company): static
    {
        $this->company = $company;

        return $this;
    }

    public function getPrestationCategory(): ?PrestationCategory
    {
        return $this->prestationCategory;
    }

    public function setPrestationCategory(PrestationCategory $prestationCategory): static
    {
        $this->prestationCategory = $prestationCategory;

        return $this;
    }

    public function getPriceAmount(): ?int
    {
        return $this->priceAmount;
    }

    public function setPriceAmount(int $priceAmount): static
    {
        $this->priceAmount = $priceAmount;

        return $this;
    }

    public function getPriceCurrency(): ?string
    {
        return $this->priceCurrency;
    }

    public function setPriceCurrency(string $priceCurrency): static
    {
        $this->priceCurrency = $priceCurrency;

        return $this;
    }

    public function isPriceTaxIncluded(): ?bool
    {
        return $this->priceTaxIncluded;
    }

    public function setPriceTaxIncluded(bool $priceTaxIncluded): static
    {
        $this->priceTaxIncluded = $priceTaxIncluded;

        return $this;
    }

    public function getQuantity(): ?int
    {
        return $this->quantity;
    }

    public function setQuantity(int $quantity): static
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(string $unit): static
    {
        $this->unit = $unit;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
