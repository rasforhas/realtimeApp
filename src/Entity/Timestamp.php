<?php

namespace App\Entity;

trait Timestamp
{
    /**
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    /**
     * return mixed
     */
    public function getCreateAt()
    {
        return $this->createdAt;
    }

    /**
     * @ORM\PrePersist()
     */
    public function prePersist()
    {
        $this->createdAt = new \DateTime();
    }
}