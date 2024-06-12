<?php

namespace App\Service;

use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Form;
use Doctrine\ORM\EntityManagerInterface;

class FormService {

    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function validateFields($payload, $fields = [])
    {
        foreach($fields as $field) {
            if(!array_key_exists($field, $payload)) {
                return [
                    [
                        'field' => $field,
                    ]
                ];
            }
        }

        return true;
    }

    public function validateFormPayload($payload)
    {
        if(($errors = $this->validateFields($payload, [
            'isPublic',
            'name',
            'config',
            'recipients',
            'thankYou',
            'translations',
        ])) !== true) {
            return $errors;
        }

        return true;
    }

    public function createForm($payload)
    {
        $form = new Form();

        $form->setCreatedAt(new \DateTime());

        $form = $this->applyFormPayload($payload, $form);

        $this->em->persist($form);
        $this->em->flush();

        return $form;
    }

    public function updateForm($form, $payload)
    {
        $form->setUpdatedAt(new \DateTime());

        $form = $this->applyFormPayload($payload, $form);

        $this->em->persist($form);
        $this->em->flush();

        return $form;
    }

    public function deleteForm($form)
    {
        $this->em->remove($form);
        $this->em->flush();

        return $form;
    }

    public function applyFormPayload($payload, Form $form)
    {
        $form
            ->setIsPublic($payload['isPublic'])
            ->setName($payload['name'])
            ->setConfig($payload['config'])
            ->setThankYou($payload['thankYou'])
            ->setRecipients($payload['recipients'])
            ->setTranslations($payload['translations'] ?: [])
        ;

        return $form;
    }

}