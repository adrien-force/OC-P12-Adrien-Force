<?php

namespace App\Entity;

use App\Repository\GardeningTipRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use OpenApi\Attributes as OA;

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
    #[Groups(['gardening_tip:read', 'gardening_tip:write'])]
    #[Assert\NotBlank(message: 'Le contenu du conseil ne peut pas être vide')]
    #[OA\Property(description: 'Le contenu du conseil de jardinage')]
    private ?string $content = null;

    #[ORM\Column]
    #[Groups(['exlude'])]
    #[OA\Property(description: 'La date de création du conseil de jardinage')]
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
    #[OA\Property(
        description: 'Le mois de création du conseil de jardinage',
        readOnly: true
    )]    public function getMonth(): ?string
    {
        return $this->creationDate->format('F');
    }
}
