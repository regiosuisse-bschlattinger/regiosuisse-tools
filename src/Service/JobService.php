<?php

namespace App\Service;

use App\Entity\Location;
use App\Entity\Stint;
use Doctrine\Common\Collections\ArrayCollection;
use App\Entity\Job;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Inbox;

class JobService {

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

    public function validateJobPayload($payload)
    {
        if(($errors = $this->validateFields($payload, [
            'isPublic',
            'position',
            'name',
            'description',
            'employer',
            'location',
            'contact',
            'applicationDeadline',
            'locations',
            'links',
            'files',
            'translations',
        ])) !== true) {
            return $errors;
        }

        return true;
    }

    public function createJob($payload)
    {
        $job = new Job();

        $job->setCreatedAt(new \DateTime());

        $job = $this->applyJobPayload($payload, $job);

        $this->em->persist($job);
        $this->em->flush();

        return $job;
    }

    public function updateJob($job, $payload)
    {
        $job->setUpdatedAt(new \DateTime());

        $job = $this->applyJobPayload($payload, $job);

        $this->em->persist($job);
        $this->em->flush();

        return $job;
    }

    public function deleteJob($job)
    {
        $this->em->remove($job);
        $this->em->flush();

        return $job;
    }

    public function applyJobPayload($payload, Job $job)
    {
        $job
            ->setIsPublic($payload['isPublic'])
            ->setPosition($payload['position'])
            ->setName($payload['name'])
            ->setDescription($payload['description'])
            ->setEmployer($payload['employer'])
            ->setLocation($payload['location'])
            ->setContact($payload['contact'])
            ->setApplicationDeadline($payload['applicationDeadline'] ? new \DateTime(date('Y-m-d H:i:s', strtotime($payload['applicationDeadline']))) : null)
            ->setLinks($payload['links'] ?: [])
            ->setFiles($payload['files'] ?: [])
            ->setLocations(new ArrayCollection())
            ->setStints(new ArrayCollection())
            ->setTranslations($payload['translations'] ?: [])
        ;

        foreach($payload['locations'] as $item) {
            $entity = null;
            if(array_key_exists('id', $item) && $item['id']) {
                $entity = $this->em->getRepository(Location::class)->find($item['id']);
            }
            if(!$entity && array_key_exists('name', $item)) {
                $entity = $this->em->getRepository(Location::class)
                    ->findOneBy(['name' => $item['name']]);
            }
            if($entity) {
                $job->addLocation($entity);
            }
        }

        foreach($payload['stints'] as $item) {
            $entity = null;
            if(array_key_exists('id', $item) && $item['id']) {
                $entity = $this->em->getRepository(Stint::class)->find($item['id']);
            }
            if(!$entity && array_key_exists('name', $item)) {
                $entity = $this->em->getRepository(Stint::class)
                    ->findOneBy(['name' => $item['name']]);
            }
            if($entity) {
                $job->addStint($entity);
            }
        }

        return $job;
    }

    public function createJobInboxItemFromEmbed($payload)
    {
        // Create corresponding inbox item
        $inbox = new Inbox();
        
        // Prepare normalized data structure
        $normalizedData = [
            'name' => $payload['title'],
            'description' => $payload['description'],
            'employer' => $payload['employer'],
            'locations' => $payload['locations'],
            'location' => $payload['location'],
            'contact' => $payload['contact'],
            'applicationDeadline' => $payload['applicationDeadline'],
            'stints' => $payload['stints'],
            'links' => $payload['links'] ?? [],
            'files' => $payload['files'] ?? [],
            'translations' => $payload['translations'] ?? []
        ];

        $inbox->setCreatedAt(new \DateTime())
            ->setSource('embed')
            ->setType('job')
            ->setTitle($payload['title'])
            ->setStatus('new')
            ->setIsMerged(false)
            ->setData([
                'job' => [
                    'name' => $payload['title'],
                    'description' => $payload['description'],
                    'employer' => $payload['employer'],
                    'locations' => $payload['locations'],
                    'location' => $payload['location'],
                    'contact' => $payload['contact'],
                    'applicationDeadline' => $payload['applicationDeadline'],
                    'stints' => $payload['stints'],
                    'links' => $payload['links'] ?? [],
                    'files' => $payload['files'] ?? [],
                    'translations' => $payload['translations'] ?? []
                ]
            ])
            ->setNormalizedData($normalizedData);

        $this->em->persist($inbox);
        $this->em->flush();

        return [
            'inbox' => $inbox
        ];
    }

    public function createJobFromInboxItem($payload)
    {
        $job = new Job();
        
        // Set basic job properties
        $job->setCreatedAt(new \DateTime())
            ->setIsPublic(false)
            ->setPosition(0)
            ->setName($payload['title'])
            ->setDescription($payload['description'])
            ->setEmployer($payload['employer'])
            ->setLocation($payload['location'])
            ->setContact($payload['contact'])
            ->setApplicationDeadline(
                $payload['applicationDeadline'] ? 
                new \DateTime($payload['applicationDeadline']) : 
                null
            )
            ->setLinks($payload['links'] ?? [])
            ->setFiles($payload['files'] ?? [])
            ->setTranslations($payload['translations'] ?? []);

        $this->em->persist($job);
        $this->em->flush();

        return [
            'job' => $job,
        ];
    }

}