<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use Nelmio\ApiDocBundle\Annotation\Security;
use OpenApi\Attributes as OA;
use App\Entity\Form;
use App\Entity\FormEntry;
use App\Entity\File;
use App\Service\FormEntryService;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

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

    #[Route(path: '/translate/{id}', name: 'translate', methods: ['PUT'])]
    #[IsGranted('ROLE_EDITOR')]
    #[OA\Response(
        response: 200,
        description: 'Translate a form entry',
        content: new OA\JsonContent(
            ref: new Model(type: FormEntry::class, groups: ['id', 'form_entry'])
        )
    )]
    #[OA\Tag(name: 'FormEntries')]
    #[Security(name: 'cookieAuth')]
    public function translate(Request $request, EntityManagerInterface $em,
                              NormalizerInterface $normalizer, FormEntryService $formEntryService): JsonResponse
    {
        $formEntry = $em->getRepository(FormEntry::class)
            ->find($request->get('id'));

        $payload = json_decode($request->getContent(), true);

        if(($errors = $formEntryService->validateFormEntryPayload($payload)) !== true) {
            return $this->json($errors, 400);
        }

        $formEntry = $formEntryService->translateFormEntry($formEntry, $payload['selectedTranslation']);

        $result = $normalizer->normalize($formEntry, null, [
            'groups' => ['id', 'form_entry'],
        ]);

        return $this->json($result);
    }

    #[Route(path: '.xlsx', name: 'xlsx', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Parameter(
        name: 'ids[]',
        description: 'Set specific form entry ids to select',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'integer')),
    )]
    #[OA\Parameter(
        name: 'form',
        description: 'Set specific form id to select',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'integer'),
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns an excel file',
        content: new OA\MediaType(mediaType: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', schema: new OA\Schema(type: 'string', format: 'binary'))
    )]
    #[OA\Tag(name: 'FormEntries')]
    #[Security(name: 'cookieAuth')]
    public function xlsx(Request $request, EntityManagerInterface $em, NormalizerInterface $normalizer, FormEntryService $formEntryService): Response
    {

        $form = $em->getRepository(Form::class)
            ->find($request->get('form'));

        $qb = $em->createQueryBuilder();

        $qb
            ->select('f')
            ->from(FormEntry::class, 'f')
        ;

        if($request->get('ids') && !is_array($request->get('ids'))) {
            $qb
                ->andWhere('f.id IN (:ids)')
                ->setParameter('ids', array_map('trim', explode(',', $request->get('ids'))))
            ;
        }

        if($request->get('ids') && is_array($request->get('ids'))) {
            $qb
                ->andWhere('f.id IN (:ids)')
                ->setParameter('ids', $request->get('ids'))
            ;
        }

        $formEntries = $qb->getQuery()->getResult();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        foreach(['', 'de', 'fr', 'it'] as $localeIndex => $locale) {

            $sheet = new Worksheet(null, $locale ? strtoupper($locale) : 'Original');
            $spreadsheet->addSheet($sheet, $localeIndex);

            $sheet = $spreadsheet->getSheet($localeIndex);

            $columns = [];
            $columnCells = range('C', 'Z');
            $columnCellsAddition = range('A', 'Z');

            foreach($columnCellsAddition as $cell) {
                $columnCells[] = 'A'.$cell;
            }

            $columns['ID'] = 'A';

            switch ($locale) {
                case 'fr':
                    $langLabel = 'Langue';
                    break;
                case 'it':
                    $langLabel = 'Lingua';
                    break;
                default:
                    $langLabel = 'Sprache';
                    break;
            }

            $columns[$langLabel] = 'B';

            $count = 0;

            foreach($form->getConfig() as $fieldGroup) {

                foreach($fieldGroup['fields'] as $field) {

                    $label = $field['name'];

                    if($locale && $locale !== 'de' && count($field['translations']) && isset($field['translations'][$locale])) {
                        $label = $field['translations'][$locale]['name'] ?? $field['name'];
                    }

                    $columns[$label] = $columnCells[$count];
                    $count++;
                }
            }

            $row = 1;

            foreach($columns as $columnLabel => $column) {
                $sheet->getColumnDimension($column)->setAutoSize(true);
                $sheet->setCellValue($column.$row, $columnLabel);
            }

            $sheet->getColumnDimension($columns['ID'])->setWidth(10);
            $sheet->getColumnDimension($columns[$langLabel])->setWidth(10);

            $row++;

            /** @var FormEntry $formEntry */
            foreach($formEntries as $formEntry) {

                if($locale && $locale !== $formEntry->getLanguage() && !isset($formEntry->getTranslations()[$locale])) {
                    $formEntry = $formEntryService->translateFormEntry($formEntry, $locale);
                }

                $sheet->setCellValue($columns['ID'].$row, $formEntry->getId());
                $sheet->setCellValue($columns[$langLabel].$row, strtoupper($formEntry->getLanguage()));

                foreach($form->getConfig() as $fieldGroup) {
                    foreach($fieldGroup['fields'] as $field) {

                        $label = $field['name'];

                        if($locale && $locale !== 'de' && count($field['translations']) && isset($field['translations'][$locale])) {
                            $label = $field['translations'][$locale]['name'] ?? $field['name'];
                        }

                        if(!$locale || !$formEntry->getTranslations() || count($formEntry->getTranslations()) <= 0
                            || !isset($formEntry->getTranslations()[$locale]) || $formEntry->getLanguage() === $locale
                            || $field['type'] === 'image' || $field['type'] === 'file' || $field['type'] === 'list_amount') {
                            $value = $formEntry->getContent()[$field['identifier']] ?? '';
                        } else {
                            $value = $formEntry->getTranslations()[$locale][$field['identifier']] ?? '';
                        }

                        if($field['type'] === 'boolean') {
                            $lang = $locale ?: $formEntry->getLanguage();
                            $boolean = (bool)$value;
                            $value = $this->fieldBooleanMapping()[$lang][$boolean];
                        }

                        $host = $request->getScheme() . '://'.$request->getHttpHost();

                        if($field['type'] === 'image') {
                            $images = [];

                            if(!is_array($value) || !count($value)) {
                                $value = '';

                            } else {

                                foreach ($value as $image) {
                                    if(isset($image['id'])) {
                                        $images[] = $host . '/api/v1/files/view/' . $image['id'] . '.' . $image['extension'];
                                        }
                                }

                                $value = implode(chr(10), $images);
                            }
                        }

                        if($field['type'] === 'file') {
                            $files = [];

                            if(!is_array($value) || !count($value)) {
                                $value = '';

                            } else {

                                foreach ($value as $file) {
                                    if(isset($file['id'])) {
                                        $files[] = $host . '/api/v1/files/view/' . $file['id'] . '.' . $file['extension'];
                                    }
                                }

                                $value = implode(chr(10), $files);
                            }
                        }

                        if($field['type'] === 'list' && is_array($value)) {
                            $value = array_filter($value, function($listElement) {
                                return $listElement !== '';
                            });

                            $value = implode(chr(10), $value);
                        }

                        if($field['type'] === 'list_amount' && is_array($value)) {

                            $elements = [];

                            foreach($value as $listElement) {

                                if(isset($listElement['value']) && $listElement['value']) {
                                    $elLabel = $listElement['label'];

                                    if($locale !== '' && $locale !== 'de') {
                                        $elTranslation = $field['elements'][$listElement['index']]['translations'][$locale];

                                        if(isset($elTranslation['name']) && $elTranslation['name']) {
                                            $elLabel = $elTranslation['name'];
                                        }
                                    }

                                    $elements[] = $elLabel.': '.$listElement['value'];
                                }
                            }

                            $value = implode(chr(10), $elements);
                        }

                        $sheet->setCellValue($columns[$label].$row, $value);
                        $sheet->getStyle($columns[$label].$row)->getAlignment()->setWrapText(true);

                        if(str_starts_with($value, 'http')) {
                            $sheet->getCell($columns[$label].$row)->getHyperlink()->setUrl($value);
                        }
                    }
                }

                $row++;
            }

            $sheet
                ->getStyle($sheet->calculateWorksheetDimension())
                ->getAlignment()
                ->setWrapText(true)
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_TOP);
            ;

        }

        $maxWidth = 100;
        foreach ($spreadsheet->getAllSheets() as $sheet) {
            $sheet->calculateColumnWidths();
            foreach ($sheet->getColumnDimensions() as $colDim) {
                if (!$colDim->getAutoSize()) {
                    continue;
                }
                $colWidth = $colDim->getWidth();
                if ($colWidth > $maxWidth) {
                    $colDim->setAutoSize(false);
                    $colDim->setWidth($maxWidth);
                }
            }
        }

        $writer = new Xlsx($spreadsheet);

        $extension = 'xlsx';
        $fileName = $form->getName().'-'.date('Y-m-d_H-i-s').'.'.$extension;

        $tmpFile = tempnam(sys_get_temp_dir(), $fileName);

        $writer->save($tmpFile);

        $response = $this->file($tmpFile, $fileName, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
        $response->deleteFileAfterSend(true);

        return $response;
    }

    #[Route(path: '.docx', name: 'docx', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Parameter(
        name: 'ids[]',
        description: 'Set specific form entry ids to select',
        in: 'query',
        required: false,
        schema: new OA\Schema(type: 'array', items: new OA\Items(type: 'integer')),
    )]
    #[OA\Parameter(
        name: 'form',
        description: 'Set specific form id to select',
        in: 'query',
        required: true,
        schema: new OA\Schema(type: 'integer'),
    )]
    #[OA\Response(
        response: 200,
        description: 'Returns a word file',
        content: new OA\MediaType(mediaType: 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', schema: new OA\Schema(type: 'string', format: 'binary'))
    )]
    #[OA\Tag(name: 'FormEntries')]
    #[Security(name: 'cookieAuth')]
    public function docx(Request $request, EntityManagerInterface $em, FormEntryService $formEntryService): Response
    {

        $host = $request->getScheme() . '://'.$request->getHttpHost();

        $form = $em->getRepository(Form::class)
            ->find($request->get('form'));

        $qb = $em->createQueryBuilder();

        $qb
            ->select('f')
            ->from(FormEntry::class, 'f')
        ;

        if($request->get('ids') && !is_array($request->get('ids'))) {
            $qb
                ->andWhere('f.id IN (:ids)')
                ->setParameter('ids', array_map('trim', explode(',', $request->get('ids'))))
            ;
        }

        if($request->get('ids') && is_array($request->get('ids'))) {
            $qb
                ->andWhere('f.id IN (:ids)')
                ->setParameter('ids', $request->get('ids'))
            ;
        }

        $formEntries = $qb->getQuery()->getResult();

        $tmpDirectoryTitle = $this->sanitizeString($form->getName() . '-' . date('Y') . '-' . uniqid());
        $tmpDirectory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $tmpDirectoryTitle;

        if (!is_dir($tmpDirectory)) {
            mkdir($tmpDirectory, 0777, true);
        }

        /** @var FormEntry $formEntry */
        foreach($formEntries as $formEntry) {

            $entryDirectoryTitle = $formEntry->getContent()['title'] ?? $formEntry->getContent()['name'] ?? $formEntry->getId();
            $entryDirectory = $tmpDirectory.DIRECTORY_SEPARATOR.($this->sanitizeString($entryDirectoryTitle.' ('.$formEntry->getId().')'));
            mkdir($entryDirectory, 0777, true);

            $attachmentsDirectory = $entryDirectory.DIRECTORY_SEPARATOR.'Attachments';
            mkdir($attachmentsDirectory, 0777, true);

            foreach(['', 'de', 'fr', 'it'] as $locale) {
                $images = [];
                $files = [];

                if ($locale && $locale !== $formEntry->getLanguage() && !isset($formEntry->getTranslations()[$locale])) {
                    $formEntry = $formEntryService->translateFormEntry($formEntry, $locale);
                }

                $entryFile = $entryDirectory . DIRECTORY_SEPARATOR . $this->sanitizeString($entryDirectoryTitle).' ('.($locale ? strtoupper($locale) : 'Original').').docx';

                $phpWord = new PhpWord();

                $section = $phpWord->addSection();

                $text = $form->getName();

                if($locale && isset($form->getTranslations()[$locale]['name'])) {
                    $text = $form->getTranslations()[$locale]['name'];
                }

                $headline = $section->addText(
                    htmlspecialchars($text),
                    [
                        'name' => 'Verdana',
                        'size' => 21,
                        'bold' => true,
                    ],
                    [
                        'lineHeight' => 1.2,
                        'alignment' => Alignment::HORIZONTAL_CENTER,
                        'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(18),
                    ],
                );

                foreach($form->getConfig() as $fieldGroup) {

                    $text = $fieldGroup['name'];

                    if($locale && isset($fieldGroup['translations'][$locale])) {
                        $text = $fieldGroup['translations'][$locale];
                    }

                    $subHeadline = $section->addText(
                        htmlspecialchars($text),
                        [
                            'name' => 'Verdana',
                            'size' => 14,
                            'underline' => 'single',
                        ],
                        [
                            'lineHeight' => 1.2,
                            'spaceBefore' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(40),
                            'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(27),
                        ],
                    );

                    foreach($fieldGroup['fields'] as $field) {

                        $label = $field['name'];

                        if($locale && $locale !== 'de' && count($field['translations']) && isset($field['translations'][$locale])) {
                            $label = $field['translations'][$locale]['name'] ?? $field['name'];
                        }

                        $fieldLabel = $section->addText(
                            htmlspecialchars($label),
                            [
                                'name' => 'Verdana',
                                'size' => 12,
                                'bold' => true,
                            ],
                            [
                                'lineHeight' => 1.2,
                                'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(6),
                            ],
                        );

                        if(!$locale || !$formEntry->getTranslations() || count($formEntry->getTranslations()) <= 0
                            || !isset($formEntry->getTranslations()[$locale]) || $formEntry->getLanguage() === $locale
                            || $field['type'] === 'image' || $field['type'] === 'file' || $field['type'] === 'list_amount') {
                            $value = $formEntry->getContent()[$field['identifier']] ?? '';
                        } else {
                            $value = $formEntry->getTranslations()[$locale][$field['identifier']] ?? '';
                        }

                        if($field['type'] === 'boolean') {
                            $lang = $locale ?: $formEntry->getLanguage();
                            $boolean = (bool)$value;
                            $value = $this->fieldBooleanMapping()[$lang][$boolean];
                        }

                        if($field['type'] === 'image' && isset($formEntry->getContent()[$field['identifier']])) {

                            $imgArray = $formEntry->getContent()[$field['identifier']];
                            $links = [];
                            $labels = [];

                            if(!$locale) {
                                foreach($imgArray as $img) {
                                    if(isset($img['id'])) {
                                        $images[] = $img;
                                    }
                                }
                            }
                        }

                        if($field['type'] === 'file' && isset($formEntry->getContent()[$field['identifier']])) {

                            $fileArray = $formEntry->getContent()[$field['identifier']];
                            $links = [];
                            $labels = [];

                            if(!$locale) {
                                foreach($fileArray as $file) {
                                    if(isset($file['id'])) {
                                        $files[] = $file;
                                    }
                                }
                            }
                        }

                        if($field['type'] === 'list' && is_array($value)) {
                            $value = array_filter($value, function($listElement) {
                                return $listElement !== '';
                            });

                            $value = implode(chr(10), $value);
                        }

                        if($field['type'] === 'list_amount' && is_array($value)) {

                            $elements = [];

                            foreach($value as $listElement) {

                                if(isset($listElement['value']) && $listElement['value']) {
                                    $elLabel = $listElement['label'];

                                    if($locale !== '' && $locale !== 'de') {
                                        $elTranslation = $field['elements'][$listElement['index']]['translations'][$locale];

                                        if(isset($elTranslation['name']) && $elTranslation['name']) {
                                            $elLabel = $elTranslation['name'];
                                        }
                                    }

                                    $elements[] = $elLabel.': '.$listElement['value'];
                                }
                            }

                            $value = implode(chr(10), $elements);
                        }

                        if(!$value) {
                            $value = '-';
                        }

                        if(($field['type'] !== 'image' && $field['type'] !== 'file')) {
                            $section->addText(
                                htmlspecialchars($value),
                                [
                                    'name' => 'Verdana',
                                    'size' => 11,
                                ],
                                [
                                    'lineHeight' => 1.2,
                                    'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(22),
                                ]
                            );
                        } else {

                            if($value === '-') {
                                $section->addText(
                                    '-',
                                    [
                                        'name' => 'Verdana',
                                        'size' => 11,
                                    ],
                                    [
                                        'lineHeight' => 1.2,
                                        'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(22),
                                    ]
                                );

                                continue;
                            }

                            foreach($value as $file) {

                                if(isset($file['id'])) {

                                    $link = $host . '/api/v1/files/view/' . $file['id'] . '.' . $file['extension'];
                                    $label = 'Attachments/' . $file['id'] . '.' . $file['extension'];

                                    $section->addLink(
                                        $link,
                                        htmlspecialchars($label),
                                        [
                                            'name' => 'Verdana',
                                            'color' => '003f4b',
                                            'size' => 11,
                                            'underline' => 'single',
                                        ],
                                        [
                                            'lineHeight' => 1.2,
                                            'spaceAfter' => \PhpOffice\PhpWord\Shared\Converter::pointToTwip(22),
                                        ]
                                    );
                                }
                            }
                        }
                    }
                }

                $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
                $objWriter->save($entryFile);

                foreach($images as $img) {
                    $image = $em->getRepository(File::class)
                        ->find($img['id']);

                    $fileName = $image->getId().'.'.$image->getExtension();
                    $filePath = $attachmentsDirectory . DIRECTORY_SEPARATOR . $fileName;

                    $fileStreamData = stream_get_contents($image->getData());

                    if (preg_match('/^data:(image\/[a-zA-Z0-9]+);base64,/',$fileStreamData, $matches)) {
                        $mimeType = $matches[1];
                        $base64Data = substr($fileStreamData , strpos($fileStreamData , ',') + 1);
                    } else {
                        throw new \Exception('Invalid base64 image data.');
                    }

                    $fileData = base64_decode($base64Data);
                    if ($fileData === false) {
                        throw new \Exception('Base64 decoding failed.');
                    }

                    file_put_contents($filePath,  $fileData);
                }

                foreach($files as $fl) {
                    $file = $em->getRepository(File::class)
                        ->find($fl['id']);

                    $fileName = $file->getId().'.'.$file->getExtension();

                    $filePath = $attachmentsDirectory . DIRECTORY_SEPARATOR . $fileName;

                    rewind($file->getData());

                    $fileStreamData = stream_get_contents($file->getData());

                    if (preg_match('/^data:([a-zA-Z0-9\/\.-]+);base64,/', $fileStreamData, $matches)) {
                        $mimeType = $matches[1];
                        $base64Data = substr($fileStreamData, strpos($fileStreamData, ',') + 1);
                    } else {
                        continue;
                    }

                    $fileData = base64_decode($base64Data);
                    if ($fileData === false) {
                        throw new \Exception('Base64 decoding failed.');
                    }

                    file_put_contents($filePath,  $fileData);
                }
            }
        }

        $zipFilePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . $form->getName() . '-' . date('Y') . '-' . uniqid() . '.zip';

        $command = "cd " . escapeshellarg($tmpDirectory) . " && zip -r " . escapeshellarg($zipFilePath) . " ./*";
        $output = shell_exec($command . ' 2>&1');

        if (!file_exists($zipFilePath) || filesize($zipFilePath) === 0) {
            $errorMessage = "Failed to create ZIP file. Command output: " . $output;
            return new Response($errorMessage, Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $response = new BinaryFileResponse($zipFilePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            basename($zipFilePath)
        );
        $response->headers->set('Content-Type', 'application/zip');
        $response->deleteFileAfterSend(true);

        return $response;

    }

    private function fieldBooleanMapping(): array
    {
        return [
            'de' => [
                true => 'Ja',
                false => 'Nein',
            ],
            'fr' => [
                true => 'Oui',
                false => 'Non',
            ],
            'it' => [
                true => 'Sì',
                false => 'No',
            ],
        ];

    }

    private function sanitizeString($string): string
    {
        $whitelistArray = ['a-zA-Z0-9', '_', '-', '[', ']', '|', '{', '}', '(', ')', '.'];
        $whitelist = implode('\\', $whitelistArray).' äöü';

        return preg_replace("/[^$whitelist]/u", '', $string);
    }

}