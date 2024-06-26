<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
#[HasLifecycleCallbacks()]
class Order
{
    public const DEVISE = 'eur';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $deliveryAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status_stripe = null;

    #[ORM\Column(length: 255)]
    private ?string $reference = null;

    #[ORM\Column]
    private ?float $price = null;

    #[ORM\OneToMany(mappedBy: 'ordering', targetEntity: Cart::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $carts;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Transporter $transporter = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripe_customer_id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $stripe_payment_intent = null;

    #[ORM\ManyToOne(inversedBy: 'orders')]
    private ?OrderStatus $delivery_status = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $trackerID = null;

    public function __construct()
    {
        $this->carts = new ArrayCollection();
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function prePersist()
    {
        // mise en place de la date de création de la commande
        if (empty($this->createdAt)) {
            $this->createdAt = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        }
        // mise en place d'une référence unique pour la commande
        if (empty($this->reference)) {
            $this->reference = uniqid();
        }
        // on utilise updatedAt pour avoir une référence de temps entre la création et une modification potentielle de la commande
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDeliveryAddress(): ?string
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(string $deliveryAddress): self
    {
        $this->deliveryAddress = $deliveryAddress;

        return $this;
    }

    public function getStatusStripe(): ?string
    {
        return $this->status_stripe;
    }

    public function setStatusStripe(?string $status_stripe): self
    {
        $this->status_stripe = $status_stripe;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): self
    {
        $this->reference = $reference;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, Cart>
     */
    public function getCarts(): Collection
    {
        return $this->carts;
    }

    public function addCart(Cart $cart): self
    {
        if (!$this->carts->contains($cart)) {
            $this->carts->add($cart);
            $cart->setOrdering($this);
        }

        return $this;
    }

    public function removeCart(Cart $cart): self
    {
        if ($this->carts->removeElement($cart)) {
            // set the owning side to null (unless already changed)
            if ($cart->getOrdering() === $this) {
                $cart->setOrdering(null);
            }
        }

        return $this;
    }

    public function getTransporter(): ?Transporter
    {
        return $this->transporter;
    }

    public function setTransporter(?Transporter $transporter): self
    {
        $this->transporter = $transporter;

        return $this;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripe_customer_id;
    }

    public function setStripeCustomerId(?string $stripe_customer_id): self
    {
        $this->stripe_customer_id = $stripe_customer_id;

        return $this;
    }

    public function getStripePaymentIntent(): ?string
    {
        return $this->stripe_payment_intent;
    }

    public function setStripePaymentIntent(?string $stripe_payment_intent): self
    {
        $this->stripe_payment_intent = $stripe_payment_intent;

        return $this;
    }

    public function getDeliveryStatus(): ?OrderStatus
    {
        return $this->delivery_status;
    }

    public function setDeliveryStatus(?OrderStatus $delivery_status): self
    {
        $this->delivery_status = $delivery_status;

        return $this;
    }

    public function getTrackerID(): ?string
    {
        return $this->trackerID;
    }

    public function setTrackerID(?string $trackerID): self
    {
        $this->trackerID = $trackerID;

        return $this;
    }
}
