<?php

/**
 * Pimcore
 *
 * This source file is available under two different licenses:
 * - GNU General Public License version 3 (GPLv3)
 * - Pimcore Enterprise License (PEL)
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (http://www.pimcore.org)
 * @license    http://www.pimcore.org/license     GPLv3 and PEL
 */

namespace Pimcore\Bundle\DataHubBundle\GraphQL\PropertyType;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\Definition\Type;
use Pimcore\Bundle\DataHubBundle\GraphQL\ElementDescriptor;
use Pimcore\Bundle\DataHubBundle\GraphQL\Service;
use Pimcore\Bundle\DataHubBundle\GraphQL\Traits\ServiceTrait;
use Pimcore\Bundle\DataHubBundle\PimcoreDataHubBundle;
use Pimcore\Bundle\DataHubBundle\WorkspaceHelper;
use Pimcore\Model\Document;
use Pimcore\Model\Element\Data\MarkerHotspotItem;
use Pimcore\Model\Property;

class DocumentType extends ObjectType
{
    use ServiceTrait;

    /**
     * DocumentType constructor.
     * @param Service $graphQlService
     * @throws \Exception
     */
    public function __construct(Service $graphQlService)
    {

        $this->graphQlService = $graphQlService;
        $documentUnionType = $this->getGraphQlService()->getDocumentTypeDefinition("document");


        $config = [
            'name' => "property_document",
            'fields' => [
                'name' => [
                    'type' => Type::string(),
                    'resolve' => static function ($value = null, $args = [], $context = [], ResolveInfo $resolveInfo = null) {
                        if ($value instanceof MarkerHotspotItem || $value instanceof Property) {
                            return $value->getName();
                        }
                    }
                ],
                'type' => [
                    'type' => Type::string(),
                    'resolve' => static function ($value = null, $args = [], $context = [], ResolveInfo $resolveInfo = null) {
                        if ($value instanceof MarkerHotspotItem || $value instanceof Property) {
                            return $value->getType();
                        }
                    }
                ],
                'document' => [
                    'type' => $documentUnionType,
                    'resolve' => static function ($value = null, $args = [], $context = [], ResolveInfo $resolveInfo = null) use ($graphQlService) {
                        if ($value instanceof MarkerHotspotItem) {
                            $element = \Pimcore\Model\Element\Service::getElementById($value->getType(), $value->getValue());
                        } else if ($value instanceof Property) {
                            $element = $value->getData();
                        }
                        if ($element) {
                            if (!WorkspaceHelper::isAllowed($element, $context['configuration'], 'read')) {
                                if (PimcoreDataHubBundle::getNotAllowedPolicy() == PimcoreDataHubBundle::NOT_ALLOWED_POLICY_EXCEPTION) {
                                    throw new \Exception('not allowed to view document');
                                } else {
                                    return null;
                                }
                            }
                            /** @var  $element Document */
                            $data = new ElementDescriptor($element);

                            $fieldHelper = $graphQlService->getDocumentFieldHelper();
                            $fieldHelper->extractData($data, $element, $args, $context, $resolveInfo);

                            return $data;
                        }
                        return null;
                    }

                ]]];

        parent::__construct($config);
    }
}