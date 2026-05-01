<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use App\Repository\UserRepository;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'user')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id_user = null;

    #[ORM\Column(type: 'string')]
    private ?string $nom = null;

    #[ORM\Column(type: 'string')]
    private ?string $prenom = null;

    #[ORM\Column(type: 'string')]
    private ?string $username = null;

    #[ORM\Column(type: 'string')]
    private ?string $email = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: 'string')]
    private ?string $mot_de_passe = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $photo_profil = null;

    #[ORM\Column(type: 'string')]
    private ?string $role = null;

    #[ORM\Column(type: 'string')]
    private ?string $statut = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $date_inscription = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $derniere_connexion = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $email_verified = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $verification_code = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $verification_code_expires = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $reset_code = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $reset_code_expires = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $avatar_style = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $avatar_seed = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $welcome_email_sent = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $google_id = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $last_activity_date = null;

    #[ORM\Column(type: 'boolean', nullable: true)]
    private ?bool $first_login = null;

    #[ORM\Column(type: 'string', nullable: true)]
    private ?string $selected_avatar = null;

    #[ORM\Column(type: 'decimal', precision: 3, scale: 2, nullable: true)]
    private ?float $average_rating = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $total_ratings = null;

    // --- RELATIONS ---

    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'ratedUser')]
    private Collection $ratingsReceived;

    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'raterUser')]
    private Collection $ratingsGiven;

    #[ORM\OneToMany(targetEntity: AppRating::class, mappedBy: 'user')]
    private Collection $appRatings;

    #[ORM\OneToMany(targetEntity: Avi::class, mappedBy: 'user')]
    private Collection $avis;

    #[ORM\OneToMany(targetEntity: ExportLog::class, mappedBy: 'user')]
    private Collection $exportLogs;

    #[ORM\OneToMany(targetEntity: Response::class, mappedBy: 'user')]
    private Collection $responses;

    #[ORM\OneToMany(targetEntity: VerificationLog::class, mappedBy: 'user')]
    private Collection $verificationLogs;

    public function __construct()
    {
        $this->ratingsReceived = new ArrayCollection();
        $this->ratingsGiven = new ArrayCollection();
        $this->appRatings = new ArrayCollection();
        $this->avis = new ArrayCollection();
        $this->exportLogs = new ArrayCollection();
        $this->responses = new ArrayCollection();
        $this->verificationLogs = new ArrayCollection();
    }

    // --- GETTERS & SETTERS (Essentiels) ---

    public function getId_user(): ?int { return $this->id_user; }

    public function getNom(): ?string { return $this->nom; }
    public function setNom(string $nom): self { $this->nom = $nom; return $this; }

    public function getPrenom(): ?string { return $this->prenom; }
    public function setPrenom(string $prenom): self { $this->prenom = $prenom; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(string $email): self { $this->email = $email; return $this; }

    /** @return Collection<int, Rating> */
    public function getRatingsReceived(): Collection { return $this->ratingsReceived; }

    /** @return Collection<int, Rating> */
    public function getRatingsGiven(): Collection { return $this->ratingsGiven; }

    // NOTE: Vous pouvez lancer make:entity --regenerate pour recréer tous les autres getters/setters proprement ici.

    public function getIdUser(): ?int
    {
        return $this->id_user;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function getMotDePasse(): ?string
    {
        return $this->mot_de_passe;
    }

    public function setMotDePasse(string $mot_de_passe): static
    {
        $this->mot_de_passe = $mot_de_passe;

        return $this;
    }

    public function getPhotoProfil(): ?string
    {
        return $this->photo_profil;
    }

    public function setPhotoProfil(?string $photo_profil): static
    {
        $this->photo_profil = $photo_profil;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        $this->role = $role;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDateInscription(): ?\DateTime
    {
        return $this->date_inscription;
    }

    public function setDateInscription(?\DateTime $date_inscription): static
    {
        $this->date_inscription = $date_inscription;

        return $this;
    }

    public function getDerniereConnexion(): ?\DateTime
    {
        return $this->derniere_connexion;
    }

    public function setDerniereConnexion(?\DateTime $derniere_connexion): static
    {
        $this->derniere_connexion = $derniere_connexion;

        return $this;
    }

    public function isEmailVerified(): ?bool
    {
        return $this->email_verified;
    }

    public function setEmailVerified(?bool $email_verified): static
    {
        $this->email_verified = $email_verified;

        return $this;
    }

    public function getVerificationCode(): ?string
    {
        return $this->verification_code;
    }

    public function setVerificationCode(?string $verification_code): static
    {
        $this->verification_code = $verification_code;

        return $this;
    }

    public function getVerificationCodeExpires(): ?\DateTime
    {
        return $this->verification_code_expires;
    }

    public function setVerificationCodeExpires(?\DateTime $verification_code_expires): static
    {
        $this->verification_code_expires = $verification_code_expires;

        return $this;
    }

    public function getResetCode(): ?string
    {
        return $this->reset_code;
    }

    public function setResetCode(?string $reset_code): static
    {
        $this->reset_code = $reset_code;

        return $this;
    }

    public function getResetCodeExpires(): ?\DateTime
    {
        return $this->reset_code_expires;
    }

    public function setResetCodeExpires(?\DateTime $reset_code_expires): static
    {
        $this->reset_code_expires = $reset_code_expires;

        return $this;
    }

    public function getAvatarStyle(): ?string
    {
        return $this->avatar_style;
    }

    public function setAvatarStyle(?string $avatar_style): static
    {
        $this->avatar_style = $avatar_style;

        return $this;
    }

    public function getAvatarSeed(): ?string
    {
        return $this->avatar_seed;
    }

    public function setAvatarSeed(?string $avatar_seed): static
    {
        $this->avatar_seed = $avatar_seed;

        return $this;
    }

    public function isWelcomeEmailSent(): ?bool
    {
        return $this->welcome_email_sent;
    }

    public function setWelcomeEmailSent(?bool $welcome_email_sent): static
    {
        $this->welcome_email_sent = $welcome_email_sent;

        return $this;
    }

    public function getGoogleId(): ?string
    {
        return $this->google_id;
    }

    public function setGoogleId(?string $google_id): static
    {
        $this->google_id = $google_id;

        return $this;
    }

    public function getLastActivityDate(): ?\DateTime
    {
        return $this->last_activity_date;
    }

    public function setLastActivityDate(?\DateTime $last_activity_date): static
    {
        $this->last_activity_date = $last_activity_date;

        return $this;
    }

    public function isFirstLogin(): ?bool
    {
        return $this->first_login;
    }

    public function setFirstLogin(?bool $first_login): static
    {
        $this->first_login = $first_login;

        return $this;
    }

    public function getSelectedAvatar(): ?string
    {
        return $this->selected_avatar;
    }

    public function setSelectedAvatar(?string $selected_avatar): static
    {
        $this->selected_avatar = $selected_avatar;

        return $this;
    }

    public function getAverageRating(): ?string
    {
        return $this->average_rating;
    }

    public function setAverageRating(?string $average_rating): static
    {
        $this->average_rating = $average_rating;

        return $this;
    }

    public function getTotalRatings(): ?int
    {
        return $this->total_ratings;
    }

    public function setTotalRatings(?int $total_ratings): static
    {
        $this->total_ratings = $total_ratings;

        return $this;
    }

    public function addRatingsReceived(Rating $ratingsReceived): static
    {
        if (!$this->ratingsReceived->contains($ratingsReceived)) {
            $this->ratingsReceived->add($ratingsReceived);
            $ratingsReceived->setRatedUser($this);
        }

        return $this;
    }

    public function removeRatingsReceived(Rating $ratingsReceived): static
    {
        if ($this->ratingsReceived->removeElement($ratingsReceived)) {
            // set the owning side to null (unless already changed)
            if ($ratingsReceived->getRatedUser() === $this) {
                $ratingsReceived->setRatedUser(null);
            }
        }

        return $this;
    }

    public function addRatingsGiven(Rating $ratingsGiven): static
    {
        if (!$this->ratingsGiven->contains($ratingsGiven)) {
            $this->ratingsGiven->add($ratingsGiven);
            $ratingsGiven->setRaterUser($this);
        }

        return $this;
    }

    public function removeRatingsGiven(Rating $ratingsGiven): static
    {
        if ($this->ratingsGiven->removeElement($ratingsGiven)) {
            // set the owning side to null (unless already changed)
            if ($ratingsGiven->getRaterUser() === $this) {
                $ratingsGiven->setRaterUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, AppRating>
     */
    public function getAppRatings(): Collection
    {
        return $this->appRatings;
    }

    public function addAppRating(AppRating $appRating): static
    {
        if (!$this->appRatings->contains($appRating)) {
            $this->appRatings->add($appRating);
            $appRating->setUser($this);
        }

        return $this;
    }

    public function removeAppRating(AppRating $appRating): static
    {
        if ($this->appRatings->removeElement($appRating)) {
            // set the owning side to null (unless already changed)
            if ($appRating->getUser() === $this) {
                $appRating->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Avi>
     */
    public function getAvis(): Collection
    {
        return $this->avis;
    }

    public function addAvi(Avi $avi): static
    {
        if (!$this->avis->contains($avi)) {
            $this->avis->add($avi);
            $avi->setUser($this);
        }

        return $this;
    }

    public function removeAvi(Avi $avi): static
    {
        if ($this->avis->removeElement($avi)) {
            // set the owning side to null (unless already changed)
            if ($avi->getUser() === $this) {
                $avi->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, ExportLog>
     */
    public function getExportLogs(): Collection
    {
        return $this->exportLogs;
    }

    public function addExportLog(ExportLog $exportLog): static
    {
        if (!$this->exportLogs->contains($exportLog)) {
            $this->exportLogs->add($exportLog);
            $exportLog->setUser($this);
        }

        return $this;
    }

    public function removeExportLog(ExportLog $exportLog): static
    {
        if ($this->exportLogs->removeElement($exportLog)) {
            // set the owning side to null (unless already changed)
            if ($exportLog->getUser() === $this) {
                $exportLog->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Response>
     */
    public function getResponses(): Collection
    {
        return $this->responses;
    }

    public function addResponse(Response $response): static
    {
        if (!$this->responses->contains($response)) {
            $this->responses->add($response);
            $response->setUser($this);
        }

        return $this;
    }

    public function removeResponse(Response $response): static
    {
        if ($this->responses->removeElement($response)) {
            // set the owning side to null (unless already changed)
            if ($response->getUser() === $this) {
                $response->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, VerificationLog>
     */
    public function getVerificationLogs(): Collection
    {
        return $this->verificationLogs;
    }

    public function addVerificationLog(VerificationLog $verificationLog): static
    {
        if (!$this->verificationLogs->contains($verificationLog)) {
            $this->verificationLogs->add($verificationLog);
            $verificationLog->setUser($this);
        }

        return $this;
    }

    public function removeVerificationLog(VerificationLog $verificationLog): static
    {
        if ($this->verificationLogs->removeElement($verificationLog)) {
            // set the owning side to null (unless already changed)
            if ($verificationLog->getUser() === $this) {
                $verificationLog->setUser(null);
            }
        }

        return $this;
    }
}