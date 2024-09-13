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
    protected string $deeplApiKey;

    public function __construct(EntityManagerInterface $em, MailerInterface $mailer, string $mailerFrom, string $host, string $deeplApiKey)
    {
        $this->em = $em;
        $this->mailer = $mailer;
        $this->mailerFrom = $mailerFrom;
        $this->host = $host;
        $this->deeplApiKey = $deeplApiKey;
    }

    public function validateFields($payload, $form, $fields = [])
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

        foreach($form->getConfig() as $formGroup) {
            foreach($formGroup['fields'] as $field) {
                $fieldsConfig[] = $field;
            }
        }

        $errors = [];

        foreach($fieldsConfig as $fieldConfig) {

            $error = [
                'field' => $fieldConfig['identifier'],
            ];

            if(isset($fieldConfig['required']) && $fieldConfig['required'] && !array_key_exists($fieldConfig['identifier'], $payload['content'])) {
                $errors[] = $error;
                continue;
            }

            $fieldData = null;

            if(isset($payload['content'][$fieldConfig['identifier']])) {
                $fieldData = $payload['content'][$fieldConfig['identifier']];
            }

            $fieldType = $fieldConfig['type'];

            if(isset($fieldConfig['required']) && $fieldConfig['required'] && !$fieldData) {
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
                }
            }

            if($fieldType === 'email') {

                if(!filter_var($fieldData, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = $error;
                }
            }

            if($fieldType === 'image' || $fieldType === 'file' || $fieldType === 'list') {

                if($fieldConfig['required'] && !count($fieldData)) {
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
        $form = $this->em->getRepository(Form::class)->find($payload['form']['id']);

        if(($errors = $this->validateFields($payload, $form, [
                'language',
                'content',
                'form',
                'translations',
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

    public function updateFormEntry($formEntry, $payload)
    {
        $formEntry->setUpdatedAt(new \DateTime());

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

    public function translateFormEntry($formEntry, $lang)
    {
        $apiKey = $this->deeplApiKey;

        $translator = new \DeepL\Translator($apiKey);
        $fieldKeys = [];
        $fieldValues = [];
        $fieldKeysArray = [];
        $fieldValuesArray = [];

        $targetLang = $lang;

        foreach($formEntry->getContent() as $key => $value) {

            $form = $formEntry->getForm();
            $formFieldType = '';

            foreach($form->getConfig() as $fieldGroup) {
                foreach($fieldGroup['fields'] as $field) {
                    if($field['identifier'] === $key) {
                        $formFieldType = $field['type'];
                    }
                }
            }

            if($formFieldType === 'boolean') {

                if($value === true) {
                    $value = '1';
                }

                if($value === false) {
                    $value = '0';
                }
            }

            if($formFieldType === 'image' || $formFieldType === 'file' || $formFieldType === 'list_amount') {
                continue;
            }

            if($formFieldType === 'list') {
                $fieldKeysArray[] = $key;
                $fieldValuesArray[] = $value;
                continue;
            }

            $fieldKeys[] = $key;
            $fieldValues[] = $value;
        }

        $translations = [];

        if(count($fieldValues) > 0) {
            $fieldTranslations = $translator->translateText($fieldValues, null, $targetLang);
            $translations = $formEntry->getTranslations();

            foreach($fieldKeys as $index => $fieldKey) {
                $translations[$lang][$fieldKey] = $fieldTranslations[$index]->text;
            }
        }

        if(count($fieldValuesArray) > 0) {
            $fieldTranslations =  [];
            $translations = $formEntry->getTranslations();

            foreach($fieldValuesArray as $array) {
                $fieldTranslations[] = $translator->translateText($array, null, $targetLang);
            }

            foreach($fieldKeysArray as $index => $fieldKey) {
                $translationElements = [];

                foreach($fieldTranslations[$index] as $elIndex => $element) {
                    $translationElements[] = $element->text;
                }

                $translations[$lang][$fieldKey] = $translationElements;
            }
        }

        $formEntry->setTranslations($translations);

        $this->em->persist($formEntry);
        $this->em->flush();

        return $formEntry;
    }

    public function applyFormEntryPayload($payload, FormEntry $formEntry)
    {
        $formEntry
            ->setLanguage($payload['language'])
            ->setContent($payload['content'])
            ->setForm(null)
            ->setTranslations($payload['translations'] ?: [])
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