<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\StatRepository")
 */
class Stat
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="string")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $statValue;

    /**
     * @ORM\Column(type="datetime")
     * @var \DateTimeInterface $timestamp
     */
    private $timestamp;

    public function __construct()
    {
        $this->timestamp = new \DateTime();
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getStatValue(): ?int
    {
        return $this->statValue;
    }

    public function setStatValue(int $statValue): self
    {
        $this->statValue = $statValue;

        return $this;
    }

    public function getTimestamp(): ?int
    {
        return $this->timestamp->getTimestamp();
    }

    public function setTimestamp($timestamp): self
    {
        $dt = new \DateTime('@' . $timestamp);
        $this->timestamp = $dt;

        return $this;
    }
}
