<?php

namespace App\Entity;

use App\Repository\TaskRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=TaskRepository::class)
 */
class Task
{
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
    private $user_id;

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

    public function getUserId(): ?User
    {
        return $this->user_id;
    }

    public function setUserId(?User $user_id): self
    {
        $this->user_id = $user_id;

        return $this;
    }


    /* CUSTOM METHODS */

    public function getReadableDate(){
        if(is_null($this->duedate)) return null;
        $date = $this->duedate;
        // $dateObject = \DateTime::createFromFormat('Y-m-d H:i:s',$date);
        $duedateReadable = $date->format('D, d M Y');// H:i:s
        return $duedateReadable;
    }

    public function getDate(){
        if(is_null($this->duedate)) return null;
        $date = $this->duedate;
        // $dateObject = \DateTime::createFromFormat('Y-m-d H:i:s',$date);
        $date = $date->format('Y-m-d');
        return $date;
    }
}
