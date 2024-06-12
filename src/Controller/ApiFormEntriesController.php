<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use App\Entity\User;
use App\Entity\FormEntry;
use App\Service\FormEntryService;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[Route(path: '/api/v1/form-entries', name: 'api_form-entries')]
class ApiFormEntriesController extends AbstractController
{
    #[Route(path: '', name: 'index', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Parameter(
        name: 'ids[]',
        description: 'Set specific ids to select',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'integer')),
    )]
    #[OA\Parameter(
        name: 'term',
        description: 'Search term',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string'),
    )]
    #[OA\Parameter(
        name: 'limit',
        description: 'Limit returned items',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer'),
    )]
    #[OA\Parameter(
        name: 'offset',
        description: 'Skip returned items',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer'),
    )]
    #[OA\Parameter(
        name: 'orderBy[]',
        description: 'Order items by field',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['id', 'createdAt']),
    )]
    #[OA\Parameter(
        name: 'orderDirection[]',
        description: 'Set order direction',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['ASC', 'DESC']),
    )]
    #[OA\Parameter(
        name: 'form',
        description: 'Returns the form entries connected to a form id',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'integer'),
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns all form-entries',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: FormEntry::class, groups: ['id', 'form_entry']))
        )
    )]
    #[OA\Tag(name: 'FormEntries')]
    public function index(Request $request, EntityManagerInterface $em,
                          NormalizerInterface $normalizer): JsonResponse
    {
        $qb = $em->createQueryBuilder();

        $qb
            ->select('e')
            ->from(FormEntry::class, 'e')
        ;

        if($request->get('ids') && !is_array($request->get('ids'))) {
            $qb
                ->andWhere('e.id IN (:ids)')
                ->setParameter('ids', array_map('trim', explode(',', $request->get('ids'))))
            ;
        }

        if($request->get('ids') && is_array($request->get('ids'))) {
            $qb
                ->andWhere('e.id IN (:ids)')
                ->setParameter('ids', $request->get('ids'))
            ;
        }

        if($request->get('term')) {
            $qb
                ->andWhere('(e.name LIKE :term)')
                ->setParameter('term', '%'.$request->get('term').'%');
        }

        if($request->get('limit')) {
            $qb->setMaxResults($request->get('limit'));
        }

        if($request->get('offset')) {
            $qb->setFirstResult($request->get('offset'));
        }

        if($request->get('orderBy') && is_array($request->get('orderBy')) && count($request->get('orderBy'))) {

            foreach($request->get('orderBy') as $key => $orderBy) {

                if(!in_array($orderBy, ['id', 'createdAt'])) {
                    continue;
                }

                $direction = 'ASC';

                if($request->get('orderDirection') && is_array($request->get('orderDirection')) &&
                    count($request->get('orderDirection')) && array_key_exists($key, $request->get('orderDirection')) &&
                    in_array($request->get('orderDirection')[$key], ['ASC', 'DESC'])) {
                    $direction = $request->get('orderDirection')[$key];
                }

                $qb
                    ->addOrderBy('e.'.$orderBy, $direction)
                ;

            }

        } else {
            $qb
                ->addOrderBy('e.id', 'ASC')
            ;
        }

        if($request->get('form')) {
            $qb
                ->leftJoin('e.form', 'form')
                ->andWhere('form.id LIKE :formId')
                ->setParameter('formId', $request->get('form'))
            ;
        }

        $formEntryEntries = $qb->getQuery()->getResult();

        $result = $normalizer->normalize($formEntryEntries, null, [
            'groups' => ['id', 'form_entry'],
        ]);

        return $this->json($result);
    }
    
    #[Route(path: '/{id}', name: 'get', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Response(
        response: 200,
        description: 'Returns a single form entry',
        content: new OA\JsonContent(
            ref: new Model(type: FormEntry::class, groups: ['id', 'form_entry'])
        )
    )]
    #[OA\Tag(name: 'FormEntries')]
    public function find(Request $request, EntityManagerInterface $em,
                         NormalizerInterface $normalizer): JsonResponse
    {
        $formEntryEntry = $em->getRepository(FormEntry::class)
            ->find($request->get('id'));

        $result = $normalizer->normalize($formEntryEntry, null, [
            'groups' => ['id', 'form_entry'],
        ]);

        return $this->json($result);
    }
    
    #[Route(path: '', name: 'create', methods: ['POST'])]
    #[OA\Response(
        response: 200,
        description: 'Create a form entry',
        content: new OA\JsonContent(
            ref: new Model(type: FormEntry::class, groups: ['id', 'form_entry'])
        )
    )]
    #[OA\Tag(name: 'FormEntries')]
    #[Security(name: 'cookieAuth')]
    public function create(Request $request, EntityManagerInterface $em,
                           NormalizerInterface $normalizer, FormEntryService $formEntryService): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if(($errors = $formEntryService->validateFormEntryPayload($payload)) !== true) {
            return $this->json($errors, 404);
        }

        $formEntry = $formEntryService->createFormEntry($payload);

        $result = $normalizer->normalize($formEntry, null, [
            'groups' => ['id', 'form_entry'],
        ]);

        if($formEntry->getForm()->getRecipients()) {

            $notificationRecipients = explode("\n", $formEntry->getForm()->getRecipients());

            if(count($notificationRecipients) > 0) {
                $formEntryService->sendEmailNotification($formEntry->getForm(), $formEntry->getId(), $notificationRecipients);
            }
        }

        return $this->json($result);
    }
    
    /*#[Route(path: '/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_EDITOR')]
    #[OA\Response(
        response: 200,
        description: 'Update a form entry',
        content: new OA\JsonContent(
            ref: new Model(type: FormEntry::class, groups: ['id', 'form_entry'])
        )
    )]
    #[OA\Tag(name: 'FormEntries')]
    #[Security(name: 'cookieAuth')]
    public function update(Request $request, EntityManagerInterface $em,
                           NormalizerInterface $normalizer, FormEntryService $formEntryEntryService): JsonResponse
    {
        $formEntry = $em->getRepository(FormEntry::class)
            ->find($request->get('id'));

        $payload = json_decode($request->getContent(), true);

        if(($errors = $formEntryEntryService->validateFormPayload($payload)) !== true) {
            return $this->json($errors, 400);
        }

        $formEntry = $formEntryService->updateForm($formEntry, $payload);

        $result = $normalizer->normalize($formEntry, null, [
            'groups' => ['id', 'form_entry'],
        ]);

        return $this->json($result);
    }*/
    
    #[Route(path: '/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_EDITOR')]
    #[OA\Response(
        response: 200,
        description: 'Delete a form entry',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(type: 'string'),
            default: []
        )
    )]
    #[OA\Tag(name: 'FormEntries')]
    #[Security(name: 'cookieAuth')]
    public function delete(Request $request, EntityManagerInterface $em,
                           FormEntryService $formEntryService): JsonResponse
    {
        $formEntry = $em->getRepository(FormEntry::class)
            ->find($request->get('id'));

        $formEntryService->deleteFormEntry($formEntry);

        return $this->json([]);
    }

}