<?php

namespace App\Controller;

use App\Service\FormService;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use App\Entity\Form;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[Route(path: '/api/v1/forms', name: 'api_forms')]
class ApiFormsController extends AbstractController
{

    #[Route(path: '', name: 'index', methods: ['GET'])]
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
        schema: new OA\Schema(type: 'string', enum: ['id', 'createdAt', 'updatedAt']),
    )]
    #[OA\Parameter(
        name: 'orderDirection[]',
        description: 'Set order direction',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'string', enum: ['ASC', 'DESC']),
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns all forms',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(ref: new Model(type: Form::class, groups: ['id', 'form']))
        )
    )]
    #[OA\Tag(name: 'Forms')]
    public function index(Request $request, EntityManagerInterface $em,
                          NormalizerInterface $normalizer): JsonResponse
    {
        $qb = $em->createQueryBuilder();

        $qb
            ->select('f')
            ->from(Form::class, 'f')
        ;

        if($request->get('term')) {
            $qb
                ->andWhere('(f.name LIKE :term OR f.translations LIKE :term)')
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

                if(!in_array($orderBy, ['id', 'createdAt', 'updatedAt'])) {
                    continue;
                }

                $direction = 'ASC';

                if($request->get('orderDirection') && is_array($request->get('orderDirection')) &&
                    count($request->get('orderDirection')) && array_key_exists($key, $request->get('orderDirection')) &&
                    in_array($request->get('orderDirection')[$key], ['ASC', 'DESC'])) {
                    $direction = $request->get('orderDirection')[$key];
                }

                $qb
                    ->addOrderBy('f.'.$orderBy, $direction)
                ;

            }

        } else {
            $qb
                ->addOrderBy('f.id', 'ASC')
            ;
        }

        $forms = $qb->getQuery()->getResult();

        $result = $normalizer->normalize($forms, null, [
            'groups' => ['id', 'form'],
        ]);

        return $this->json($result);
    }
    
    #[Route(path: '/{id}', name: 'get', methods: ['GET'])]
    #[OA\Response(
        response: 200,
        description: 'Returns a single form',
        content: new OA\JsonContent(
            ref: new Model(type: Form::class, groups: ['id', 'form'])
        )
    )]
    #[OA\Tag(name: 'Forms')]
    public function find(Request $request, EntityManagerInterface $em,
                         NormalizerInterface $normalizer): JsonResponse
    {
        $form = $em->getRepository(Form::class)
            ->find($request->get('id'));

        $result = $normalizer->normalize($form, null, [
            'groups' => ['id', 'form'],
        ]);

        return $this->json($result);
    }
    
    #[Route(path: '', name: 'create', methods: ['POST'])]
    #[IsGranted('ROLE_EDITOR')]
    #[OA\Response(
        response: 200,
        description: 'Create a form',
        content: new OA\JsonContent(
            ref: new Model(type: Form::class, groups: ['id', 'form'])
        )
    )]
    #[OA\Tag(name: 'Forms')]
    #[Security(name: 'cookieAuth')]
    public function create(Request $request, EntityManagerInterface $em,
                           NormalizerInterface $normalizer, FormService $formService): JsonResponse
    {
        $payload = json_decode($request->getContent(), true);

        if(($errors = $formService->validateFormPayload($payload)) !== true) {
            return $this->json($errors, 400);
        }

        $form = $formService->createForm($payload);

        $result = $normalizer->normalize($form, null, [
            'groups' => ['id', 'form'],
        ]);

        return $this->json($result);
    }
    
    #[Route(path: '/{id}', name: 'update', methods: ['PUT'])]
    #[IsGranted('ROLE_EDITOR')]
    #[OA\Response(
        response: 200,
        description: 'Update a form',
        content: new OA\JsonContent(
            ref: new Model(type: Form::class, groups: ['id', 'form'])
        )
    )]
    #[OA\Tag(name: 'Forms')]
    #[Security(name: 'cookieAuth')]
    public function update(Request $request, EntityManagerInterface $em,
                           NormalizerInterface $normalizer, FormService $formService): JsonResponse
    {
        $form = $em->getRepository(Form::class)
            ->find($request->get('id'));

        $payload = json_decode($request->getContent(), true);

        if(($errors = $formService->validateFormPayload($payload)) !== true) {
            return $this->json($errors, 400);
        }

        $form = $formService->updateForm($form, $payload);

        $result = $normalizer->normalize($form, null, [
            'groups' => ['id', 'form'],
        ]);

        return $this->json($result);
    }
    
    #[Route(path: '/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_EDITOR')]
    #[OA\Response(
        response: 200,
        description: 'Delete a form',
        content: new OA\JsonContent(
            type: 'array',
            items: new OA\Items(type: 'string'),
            default: []
        )
    )]
    #[OA\Tag(name: 'Forms')]
    #[Security(name: 'cookieAuth')]
    public function delete(Request $request, EntityManagerInterface $em,
                           FormService $formService): JsonResponse
    {
        $form = $em->getRepository(Form::class)
            ->find($request->get('id'));

        $formService->deleteForm($form);

        return $this->json([]);
    }

}