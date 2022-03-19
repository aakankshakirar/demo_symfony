<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\HttpFoundation\File\File;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]

/**
 * Class User
 * @package App\Entity
 * @Vich\Uploadable
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    /**
     * @Assert\NotBlank(message="First name can not be blank")
     * @Assert\Length(
     *      min = 3,
     *      max = 50,
     *      minMessage = "First name must be at least {{ limit }} characters long",
     *      maxMessage = "First name cannot be longer than {{ limit }} characters"
     * )
     */
    private $firstName;

    #[ORM\Column(type: 'string', length: 255)]
    /**
     * @Assert\NotBlank(message="Last name can not be blank")
     * @Assert\Length(
     *      min = 3,
     *      max = 50,
     *      minMessage = "Last name must be at least {{ limit }} characters long",
     *      maxMessage = "Last name cannot be longer than {{ limit }} characters"
     * )
     */
    private $lastName;

    #[ORM\Column(type: 'string', length: 255)]
    private $email;

    #[ORM\Column(type: 'string', length: 255)]
    /**
     * @var string The hashed password
     * @ORM\Column(type="string", name="password")
     * @Assert\NotBlank(message="Password can not be blank")
     * @Assert\Length(
     *      min = 4,
     *      max = 50,
     *      minMessage = "Password must be at least {{ limit }} characters long",
     *      maxMessage = "Password cannot be longer than {{ limit }} characters"
     * )
     */
    private $password;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $dateCreated;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private $dateUpdated;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private $avatar;

    /**
     * @Assert\File(maxSize="2M")
     * @Vich\UploadableField(mapping="user_image", fileNameProperty="avatar")
     * @var File
     */
    private $imageFile;

    public function __construct()
    {
        $this->dateCreated = new \DateTime();
        $this->dateUpdated = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return (string)$this->password;
    }

    /**
     * @param string $password
     *
     * @return User
     */
    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    public function getDateCreated(): ?\DateTimeInterface
    {
        return $this->dateCreated;
    }

    public function setDateCreated(?\DateTimeInterface $dateCreated): self
    {
        $this->dateCreated = $dateCreated;

        return $this;
    }

    public function getDateUpdated(): ?\DateTimeInterface
    {
        return $this->dateUpdated;
    }

    public function setDateUpdated(?\DateTimeInterface $dateUpdated): self
    {
        $this->dateUpdated = $dateUpdated;

        return $this;
    }

    public function getSalt()
    {
        // you *may* need a real salt depending on your encoder
        // see section on salt below
        return null;
    }

    public function getRoles()
    {
        return array('ROLE_USER');
    }

    public function eraseCredentials()
    {
    }

    public function getUsername()
    {
    }

    public function getUserIdentifier()
    {
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function setImageFile(File $image = null)
    {
        $this->imageFile = $image;

        // VERY IMPORTANT:
        // It is required that at least one field changes if you are using Doctrine,
        // otherwise the event listeners won't be called and the file is lost
        if ($image) {
            $this->dateUpdated = new \DateTime('now');
        }
    }

    public function getImageFile()
    {
        return $this->imageFile;
    }
}
