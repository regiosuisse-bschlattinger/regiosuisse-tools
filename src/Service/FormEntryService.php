<?php

namespace App\Service;

use App\Entity\Form;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\FormEntry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

class FormEntryService {

    protected $em;
    protected MailerInterface $mailer;
    protected string $mailerFrom;
    protected string $host;

    public function __construct(EntityManagerInterface $em, MailerInterface $mailer, string $mailerFrom, string $host)
    {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->mailerFrom = $mailerFrom;
        $this->host = $host;
    }

    public function validateFields($payload, $fields = [])
    {
        foreach($fields as $field) {

            if (!array_key_exists($field, $payload)) {
                return [
                    [
                        'field' => $field,
                    ]
                ];
            }
        }

        $fieldsConfig = [];

        foreach($payload['form']['config'] as $formGroup) {
            foreach($formGroup['fields'] as $field) {
                $fieldsConfig[] = $field;
            }
        }

        $errors = [];

        foreach($fieldsConfig as $fieldConfig) {

            $error = [
                'field' => $fieldConfig['identifier'],
            ];

            if($fieldConfig['required'] && !array_key_exists($fieldConfig['identifier'], $payload['content'])) {
                $errors[] = $error;
                continue;
            }

            $fieldData = null;

            if(isset($payload['content'][$fieldConfig['identifier']])) {
                $fieldData = $payload['content'][$fieldConfig['identifier']];
            }

            $fieldType = $fieldConfig['type'];

            if($fieldConfig['required'] && !$fieldData) {
                $errors[] = $error;
                continue;
            }

            if($fieldType === 'text' || $fieldType === 'textarea') {

                if($fieldConfig['min'] && strlen($fieldData) < $fieldConfig['min']) {
                    $errors[] = $error;
                    continue;
                }

                if($fieldConfig['max'] && strlen($fieldData) > $fieldConfig['max']) {
                    $errors[] = $error;
                    continue;
                }
            }

            if($fieldType === 'email') {

                if(!filter_var($fieldData, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = $error;
                }
            }

        }

        if(count($errors) > 0) {
            return $errors;
        }

        return true;
    }

    public function validateFormEntryPayload($payload)
    {
        if(($errors = $this->validateFields($payload, [
            'language',
            'content',
            'form',
        ])) !== true) {
            return $errors;
        }

        return true;
    }

    public function createFormEntry($payload)
    {
        $formEntry = new FormEntry();

        $formEntry->setCreatedAt(new \DateTime());

        $formEntry = $this->applyFormEntryPayload($payload, $formEntry);

        $this->em->persist($formEntry);
        $this->em->flush();

        return $formEntry;
    }

    public function deleteFormEntry($formEntry)
    {
        $this->em->remove($formEntry);
        $this->em->flush();

        return $formEntry;
    }

    public function applyFormEntryPayload($payload, FormEntry $formEntry)
    {
        $formEntry
            ->setLanguage($payload['language'])
            ->setContent($payload['content'])
            ->setForm(null)
        ;

        if($payload['form'] && $entity = $this->em->getRepository(Form::class)->find($payload['form']['id'])) {
            $formEntry->setForm($entity);
        }

        return $formEntry;
    }

    public function sendEmailNotification($form, $formEntryId, $notificationRecipients)
    {

        $email = (new Email())
            ->from($this->mailerFrom)
            ->to(...$notificationRecipients)
            ->subject('📥 Neuer Formulareintrag ('.$form->getName().')!')
            ->text(
                trim(sprintf('
                
Hallo!
                
Du hast eine neue Formularanfrage ('.$form->getName().') erhalten.

Unter %s/#/forms/'.$form->getId().'/entries/'.$formEntryId.' kannst du die eingereichten Formulardaten einsehen.

Liebe Grüsse

                ', $this->host)),
            )
        ;

        $this->mailer->send($email);
    }

}