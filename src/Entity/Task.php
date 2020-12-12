<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\ORM\Mapping as ORM;
/**
 * @ORM\Entity(repositoryClass=TaskRepository::class)
 */
class Task implements \JsonSerializable
{

    public function jsonSerialize()
    {
        $array = [
            'id' => $this->getId(),
            'name' => $this->getName(),
            'description' => $this->getDescription(),
            'duedate' => $this->getDate(),
            'readableDuedate' => $this->getReadableDate(),
            'completed' => $this->getCompleted(),
            'user' => $this->getUser(),
            'situation' => $this->getSituation(),
            'createdat' => $this->getCreatedAt(),
            'updatedat' => $this->getUpdatedAt(),
            'project' => $this->getProject(),
        ];
        return $array;
    }

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $duedate;

    /**
     * @ORM\Column(type="boolean",options={"default":false})
     */
    private $completed = false;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="tasks")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\ManyToOne(targetEntity=Situation::class, inversedBy="tasks")
     */
    private $situation;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_at;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updated_at;

    /**
     * @ORM\ManyToOne(targetEntity=Project::class, inversedBy="tasks")
     */
    private $project;

     /* CUSTOM METHODS */

    public function __construct()
    {
        $this->created_at = new \DateTime('now');
        $this->updated_at = new \DateTime('now');
    }

    public function getReadableDate(){
        if(is_null($this->duedate)) return null;
        $date = $this->duedate;
        // $dateObject = \DateTime::createFromFormat('Y-m-d H:i:s',$date);
        $duedateReadable = $date->format('l, m/d/Y');// H:i:s
        return $duedateReadable;
    }

    public function getDate(){
        if(is_null($this->duedate)) return null;
        $date = $this->duedate;
        // $dateObject = \DateTime::createFromFormat('Y-m-d H:i:s',$date);
        $date = $date->format('Y-m-d');
        return $date;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getDuedate(): ?\DateTimeInterface
    {
        return $this->duedate;
    }

    public function setDuedate(?\DateTimeInterface $duedate): self
    {
        $this->duedate = $duedate;

        return $this;
    }

    public function getCompleted(): ?bool
    {
        return $this->completed;
    }

    public function setCompleted(bool $completed): self
    {
        $this->completed = $completed;

        return $this;
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

    public function getSituation(): ?Situation
    {
        return $this->situation;
    }

    public function setSituation(?Situation $situation): self
    {
        $this->situation = $situation;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    // public function setCreatedAt(\DateTimeInterface $created_at = null): self
    // {
    //     $this->created_at = $created_at !== null ? $created_at : new \DateTime('now');

    //     return $this;
    // }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(): self
    {
        $this->updated_at = new \DateTime('now');

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }
}
