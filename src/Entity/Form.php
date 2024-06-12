<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

/**
 * Form
 */
#[ORM\Table(name: 'pv_form')]
#[ORM\Entity(repositoryClass: 'App\Repository\FormRepository')]
class Form
{

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['id', 'form'])]
    private $id;

    #[ORM\Column(name: 'is_public', type: 'boolean')]
    #[Groups(['form'])]
    private $isPublic;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    #[Groups(['form'])]
    private $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime', nullable: true)]
    #[Groups(['form'])]
    private $updatedAt;

    #[ORM\Column(name: 'name', type: 'text', nullable: true)]
    #[Groups(['form'])]
    private $name;

    #[ORM\Column(name: 'config', type: 'json', nullable: true)]
    #[Groups(['form'])]
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
    private array $config = [];

    #[ORM\OneToMany(mappedBy: 'form', targetEntity: FormEntry::class)]
    #[Groups(['form'])]
    private Collection $formEntries;

    #[ORM\Column(name: 'recipients', type: 'text', nullable: true)]
    #[Groups(['form'])]
    private $recipients;

    #[ORM\Column(name: 'thank_you', type: 'string', length: 255, nullable: true)]
    #[Groups(['form'])]
    private $thankYou;

    #[ORM\Column(name: 'translations', type: 'json')]
    #[Groups(['form'])]
    #[OA\Property(properties: [
        new OA\Property(property: 'fr', type: 'string'),
        new OA\Property(property: 'it', type: 'string'),
    ], type: 'object')]
    private $translations = [];

    public function __construct()
    {
        $this->formEntries = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set isPublic
     *
     * @param boolean $isPublic
     *
     * @return Form
     */
    public function setIsPublic($isPublic)
    {
        $this->isPublic = $isPublic;

        return $this;
    }

    /**
     * Get isPublic
     *
     * @return bool
     */
    public function getIsPublic()
    {
        return $this->isPublic;
    }

    /**
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return Form
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Get createdAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * Set updatedAt
     *
     * @param \DateTime $updatedAt
     *
     * @return Form
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return Form
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set config
     *
     * @param array $config
     *
     * @return Form
     */
    public function setConfig(array $config): self
    {
        $this->config = $config;

        return $this;
    }

    /**
     * Get config
     *
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Get form entries
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getFormEntries(): Collection
    {
        return $this->formEntries;
    }

    /**
     * Add to form entries
     *
     * @param $formEntry
     * @return Form
     */
    public function addFormEntry(FormEntry $formEntry): self
    {
        if (!$this->formEntries->contains($formEntry)) {
            $this->formEntries->add($formEntry);
            $formEntry->setForm($this);
        }

        return $this;
    }

    /**
     * Remove form entry
     *
     * @param $formEntry
     * @return Form
     */
    public function removeFormEntry(FormEntry $formEntry): self
    {
        if ($this->formEntries->removeElement($formEntry)) {
            // set the owning side to null (unless already changed)
            if ($formEntry->getForm() === $this) {
                $formEntry->setForm(null);
            }
        }

        return $this;
    }

    /**
     * Set recipients
     *
     * @param string $recipients
     *
     * @return Form
     */
    public function setRecipients($recipients)
    {
        $this->recipients = $recipients;

        return $this;
    }

    /**
     * Get recipients
     *
     * @return string
     */
    public function getRecipients()
    {
        return $this->recipients;
    }

    /**
     * Set thankYou
     *
     * @param string $thankYou
     *
     * @return Form
     */
    public function setThankYou($thankYou)
    {
        $this->thankYou = $thankYou;

        return $this;
    }

    /**
     * Get thankYou
     *
     * @return string
     */
    public function getThankYou()
    {
        return $this->thankYou;
    }

    /**
     * Set translations
     *
     * @param array $translations
     *
     * @return Form
     */
    public function setTranslations($translations)
    {
        $this->translations = $translations;

        return $this;
    }

    /**
     * Get translations
     *
     * @return array
     */
    public function getTranslations()
    {
        return $this->translations;
    }

}

