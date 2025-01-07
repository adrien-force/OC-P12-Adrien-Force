<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use OpenApi\Attributes as OA;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]

    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Groups(['user:read', 'user:write'])]
    #[OA\Property(description: 'Adresse email de l\'utilisateur')]
    #[Assert\Email(message: 'Veuillez saisir une adresse email valide')]
    #[Assert\NotBlank(message: 'L\'adresse email ne peut pas être vide')]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(['user:read'])]
    #[OA\Property(description: 'Les rôles de l\'utilisateur')]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Groups(['user:write'])]
    #[OA\Property(description: 'Mot de passe de l\'utilisateur')]
    #[Assert\NotBlank(message: 'Le mot de passe ne peut pas être vide')]
    private ?string $password = null;

    #[ORM\Column(length: 5)]
    #[Groups(['user:read', 'user:write'])]
    #[OA\Property(description: 'Code postal de l\'utilisateur')]
    #[Assert\NotBlank(message: 'Le code postal ne peut pas être vide')]
    #[Assert\Length(min: 5, max: 5, exactMessage: 'Le code postal doit contenir exactement 5 chiffres')]
    private string $postalCode = '';


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function setPostalCode(string $postalCode): User
    {

        $this->postalCode = $postalCode;
        return $this;
    }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

}
