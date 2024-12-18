<?php

namespace App\Entity;

use App\Repository\GardeningTipRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: GardeningTipRepository::class)]
class GardeningTip
{
    public function __construct()
    {
        $this->creationDate = new DateTimeImmutable();
    }

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['gardening_tip:read'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Groups(['gardening_tip:read'])]
    private ?string $content = null;

    #[ORM\Column]
    private ?DateTimeImmutable $creationDate;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeImmutable
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeImmutable $creationDate): static
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    #[Groups(['gardening_tip:read'])]
    public function getMonth(): ?string
    {
        return $this->creationDate->format('F');
    }
}
