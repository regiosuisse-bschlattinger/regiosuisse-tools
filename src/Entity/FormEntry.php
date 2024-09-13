<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use OpenApi\Attributes as OA;

/**
 * Form Entry
 */
#[ORM\Table(name: 'pv_form_entry')]
#[ORM\Entity(repositoryClass: 'App\Repository\FormEntryRepository')]
class FormEntry
{

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Groups(['id', 'form_entry'])]
    private $id;

    #[ORM\Column(name: 'created_at', type: 'datetime')]
    #[Groups(['form_entry'])]
    private $createdAt;

    #[ORM\Column(name: 'language', type: 'text')]
    #[Groups(['form_entry'])]
    private $language;

    #[ORM\Column(name: 'content', type: 'json', nullable: true)]
    #[Groups(['form_entry'])]
    #[OA\Property(type: 'array', items: new OA\Items(type: 'string'))]
    private array $content = [];

    #[ORM\ManyToOne(inversedBy: 'form_entries')]
    #[Groups(['form_entry'])]
    private ?Form $form = null;

    #[ORM\Column(name: 'translations', type: 'json')]
    #[Groups(['form_entry'])]
    #[OA\Property(properties: [
        new OA\Property(property: 'fr', type: 'string'),
        new OA\Property(property: 'it', type: 'string'),
        new OA\Property(property: 'en', type: 'string'),
    ], type: 'object')]
    private $translations = [];

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
     * Set createdAt
     *
     * @param \DateTime $createdAt
     *
     * @return FormEntry
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
     * Set language
     *
     * @param string $language
     *
     * @return FormEntry
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Get language
     *
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * Set content
     *
     * @param array $content
     *
     * @return FormEntry
     */
    public function setContent(array $content): self
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return array
     */
    public function getContent()
    {
        return $this->content;
    }

    public function getForm(): ?Form
    {
        return $this->form;
    }

    public function setForm(?Form $form): self
    {
        $this->form = $form;

        return $this;
    }

    /**
     * Set translations
     *
     * @param array $translations
     *
     * @return FormEntry
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

