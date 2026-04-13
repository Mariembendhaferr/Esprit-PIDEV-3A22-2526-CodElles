<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_user', type: 'integer')]
    private ?int $idUser = null;

    #[ORM\Column(name: 'nom', type: 'string', length: 100)]
    private string $nom;

    #[ORM\Column(name: 'prenom', type: 'string', length: 100)]
    private string $prenom;

    #[ORM\Column(name: 'username', type: 'string', length: 50, unique: true)]
    private string $username;

    #[ORM\Column(name: 'email', type: 'string', length: 150, unique: true)]
    private string $email;

    #[ORM\Column(name: 'telephone', type: 'string', length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(name: 'mot_de_passe', type: 'string', length: 255)]
    private string $motDePasse;

    #[ORM\Column(name: 'photo_profil', type: 'string', length: 255, nullable: true)]
    private ?string $photoProfil = 'default.jpg';

    #[ORM\Column(name: 'role', type: 'string', columnDefinition: "ENUM('admin','voyageur')")]
    private string $role = 'voyageur';

    #[ORM\Column(name: 'statut', columnDefinition: "ENUM('actif','inactif')")]
    private string $statut = 'actif';

    #[ORM\Column(name: 'date_inscription', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateInscription = null;

    #[ORM\Column(name: 'derniere_connexion', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $derniereConnexion = null;

    #[ORM\Column(name: 'email_verified', type: 'boolean', nullable: true)]
    private ?bool $emailVerified = false;

    #[ORM\Column(name: 'verification_code', type: 'string', length: 6, nullable: true)]
    private ?string $verificationCode = null;

    #[ORM\Column(name: 'verification_code_expires', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $verificationCodeExpires = null;

    #[ORM\Column(name: 'reset_code', type: 'string', length: 6, nullable: true)]
    private ?string $resetCode = null;

    #[ORM\Column(name: 'reset_code_expires', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $resetCodeExpires = null;

    #[ORM\Column(name: 'avatar_style', type: 'string', length: 50, nullable: true)]
    private ?string $avatarStyle = 'default';

    #[ORM\Column(name: 'avatar_seed', type: 'string', length: 50, nullable: true)]
    private ?string $avatarSeed = null;

    #[ORM\Column(name: 'welcome_email_sent', type: 'boolean', nullable: true)]
    private ?bool $welcomeEmailSent = false;

    #[ORM\Column(name: 'google_id', type: 'string', length: 100, nullable: true)]
    private ?string $googleId = null;

    #[ORM\Column(name: 'last_activity_date', type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $lastActivityDate = null;

    #[ORM\Column(name: 'first_login', type: 'boolean', nullable: true)]
    private ?bool $firstLogin = true;

    #[ORM\Column(name: 'selected_avatar', type: 'string', length: 100, nullable: true)]
    private ?string $selectedAvatar = null;

    #[ORM\Column(name: 'average_rating', type: 'float', nullable: true)]
    private ?float $averageRating = 0;

    #[ORM\Column(name: 'total_ratings', type: 'integer', nullable: true)]
    private ?int $totalRatings = 0;

    // ====== BOOKING RELATIONSHIP ======
    #[ORM\ManyToMany(targetEntity: Activite::class, mappedBy: 'bookedByUsers')]
    private Collection $activitiesBooked;

    public function __construct()
    {
        $this->activitiesBooked = new ArrayCollection();
    }

    // ====== EXISTING GETTERS & SETTERS ======

    public function getIdUser(): ?int { return $this->idUser; }
    public function getNom(): string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }
    public function getPrenom(): string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $prenom; return $this; }
    public function getUsername(): string { return $this->username; }
    public function setUsername(string $username): self { $this->username = $username; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }
    public function getTelephone(): ?string { return $this->telephone; }
    public function setTelephone(?string $telephone): self { $this->telephone = $telephone; return $this; }
    public function getMotDePasse(): string { return $this->motDePasse; }
    public function setMotDePasse(string $motDePasse): self { $this->motDePasse = $motDePasse; return $this; }
    public function getPassword(): string { return $this->motDePasse; }
    public function setPassword(string $password): self { $this->motDePasse = $password; return $this; }
    public function getPhotoProfil(): ?string { return $this->photoProfil; }
    public function setPhotoProfil(?string $photoProfil): self { $this->photoProfil = $photoProfil; return $this; }
    public function getRole(): string { return $this->role; }
    public function setRole(string $role): self { $this->role = $role; return $this; }
    public function getStatut(): string { return $this->statut; }
    public function setStatut(string $statut): self { $this->statut = $statut; return $this; }
    public function getDateInscription(): ?\DateTimeInterface { return $this->dateInscription; }
    public function setDateInscription(?\DateTimeInterface $dateInscription): self { $this->dateInscription = $dateInscription; return $this; }
    public function getDerniereConnexion(): ?\DateTimeInterface { return $this->derniereConnexion; }
    public function setDerniereConnexion(?\DateTimeInterface $d): self { $this->derniereConnexion = $d; return $this; }
    public function getEmailVerified(): ?bool { return $this->emailVerified; }
    public function setEmailVerified(?bool $emailVerified): self { $this->emailVerified = $emailVerified; return $this; }
    public function getVerificationCode(): ?string { return $this->verificationCode; }
    public function setVerificationCode(?string $v): self { $this->verificationCode = $v; return $this; }
    public function getVerificationCodeExpires(): ?\DateTimeInterface { return $this->verificationCodeExpires; }
    public function setVerificationCodeExpires(?\DateTimeInterface $v): self { $this->verificationCodeExpires = $v; return $this; }
    public function getResetCode(): ?string { return $this->resetCode; }
    public function setResetCode(?string $r): self { $this->resetCode = $r; return $this; }
    public function getResetCodeExpires(): ?\DateTimeInterface { return $this->resetCodeExpires; }
    public function setResetCodeExpires(?\DateTimeInterface $r): self { $this->resetCodeExpires = $r; return $this; }
    public function getAvatarStyle(): ?string { return $this->avatarStyle; }
    public function setAvatarStyle(?string $a): self { $this->avatarStyle = $a; return $this; }
    public function getAvatarSeed(): ?string { return $this->avatarSeed; }
    public function setAvatarSeed(?string $a): self { $this->avatarSeed = $a; return $this; }
    public function getWelcomeEmailSent(): ?bool { return $this->welcomeEmailSent; }
    public function setWelcomeEmailSent(?bool $w): self { $this->welcomeEmailSent = $w; return $this; }
    public function getGoogleId(): ?string { return $this->googleId; }
    public function setGoogleId(?string $g): self { $this->googleId = $g; return $this; }
    public function getLastActivityDate(): ?\DateTimeInterface { return $this->lastActivityDate; }
    public function setLastActivityDate(?\DateTimeInterface $l): self { $this->lastActivityDate = $l; return $this; }
    public function getFirstLogin(): ?bool { return $this->firstLogin; }
    public function setFirstLogin(?bool $f): self { $this->firstLogin = $f; return $this; }
    public function getSelectedAvatar(): ?string { return $this->selectedAvatar; }
    public function setSelectedAvatar(?string $s): self { $this->selectedAvatar = $s; return $this; }
    public function getAverageRating(): ?float { return $this->averageRating; }
    public function setAverageRating(?float $a): self { $this->averageRating = $a; return $this; }
    public function getTotalRatings(): ?int { return $this->totalRatings; }
    public function setTotalRatings(?int $t): self { $this->totalRatings = $t; return $this; }

    // ====== BOOKING RELATIONSHIP METHODS ======

    /**
     * Get all activities booked by this user
     */
    public function getActivitiesBooked(): Collection
    {
        return $this->activitiesBooked;
    }

    /**
     * Add an activity to user's bookings
     */
    public function addActivityBooked(Activite $activite): static
    {
        if (!$this->activitiesBooked->contains($activite)) {
            $this->activitiesBooked->add($activite);
            $activite->addBookedByUser($this);
        }
        return $this;
    }

    /**
     * Remove an activity from user's bookings
     */
    public function removeActivityBooked(Activite $activite): static
    {
        if ($this->activitiesBooked->removeElement($activite)) {
            $activite->removeBookedByUser($this);
        }
        return $this;
    }

    /**
     * Check if user has booked a specific activity
     */
    public function hasBookedActivity(Activite $activite): bool
    {
        return $this->activitiesBooked->contains($activite);
    }

    /**
     * Count total booked activities
     */
    public function countBookedActivities(): int
    {
        return $this->activitiesBooked->count();
    }

    // ====== USERINTERFACE IMPLEMENTATION ======

    public function getRoles(): array
    {
        $roles = ['ROLE_USER'];
        if ($this->role === 'admin') {
            $roles[] = 'ROLE_ADMIN';
        }
        return array_unique($roles);
    }

    public function eraseCredentials(): void
    {
        // If you store temporary sensitive data, clear it here
    }

    public function getUserIdentifier(): string
    {
        return $this->email; // or use $this->username
    }

    // Helper to check roles
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isTraveler(): bool
    {
        return $this->role === 'voyageur';
    }
}