<?php

namespace lhs\elasticsearch\queries;

use craft\helpers\Db;
use lhs\elasticsearch\exceptions\InvalidFilterConfigException;
use yii\base\UnknownMethodException;
use yii\elasticsearch\ActiveQuery;

class ElasticsearchQuery extends ActiveQuery
{
    private $filterParts = [];
    private $queryParts = [];
    private $searchFields = [];
    private $registeredFilters = [];
    private $siteAnalyzer = '';

    public function __construct($modelClass, $config = [])
    {
        parent::__construct($modelClass, $config);
        $this->filterParts = $this->defaultFilterParts();
    }

    /**
     * To be able to use this class in complex twig templates that are written for a db record,
     * it is necessary to be gentler with unknown methods.
     * @param string $name
     * @param array $arguments
     * @return mixed|null
     */
    public function __call( $name,  $arguments) {
        try {
            return parent::__call($name, $arguments);
        }
        catch (UnknownMethodException $e) {
            if (isset($this->registeredFilters[$name])) {
                return $this->applyFilter($name, ...$arguments);
            }
            else {
                throw $e;
            }
        }
    }

    private function applyFilter($name, $value) {
        $filterConfig = $this->registeredFilters[$name];
        $this->filterParts[] = [
            $filterConfig['esFilterType'] => [
                $filterConfig['fieldHandle'] => [
                    'value' => $value,
                ],
            ],
        ];
        $this->query($this->buildQueryParams());
        return $this;
    }

    public function parseQueryParameters(array $queryParameters) {
        $this->queryParts = $queryParameters['bool']['must'];
        $this->filterParts = $queryParameters['bool']['filter']['bool']['must'];
        $this->query($this->buildQueryParams());
        return $this;
    }

    public function registerFilter($filterConfig) {
        $mandatoryConfigFields = ['searchHandle', 'esFilterType', 'fieldHandle'];
        foreach ($mandatoryConfigFields as $mandatoryConfigField) {
            if (!isset($filterConfig[$mandatoryConfigField])) {
                throw new InvalidFilterConfigException(sprintf('Cannot register filter. Mandatory field "%s" missing from filter config.', $mandatoryConfigField ));
            }
        }
        $this->registeredFilters[$filterConfig['searchHandle']] = $filterConfig;
    }

//    public function section($sectionHandle) {
//        /**
//         * @todo: sectionHandle is indexed dynamically by extension module.
//         * Find a way to code this dynamically by extension, too
//         */
//        $this->filterParts[] = [
//            'term' => [
//                'sectionHandle' => [
//                    'value' => $sectionHandle,
//                ],
//            ],
//        ];
//        $this->query($this->buildQueryParams());
//        return $this;
//    }
//    public function type($typeHandle) {
//        $this->filterParts[] = [
//            'term' => [
//                'type' => [
//                    'value' => $typeHandle,
//                ],
//            ],
//        ];
//        $this->query($this->buildQueryParams());
//        return $this;
//    }

    public function searchString(string $searchString) {
        $this->queryParts[] = [
            'multi_match' => [
                'fields'   => $this->getSearchFields(),
                'query'    => $searchString,
                'analyzer' => $this->getSiteAnalyzer(),
                'operator' => 'and',
            ],
        ];
        $queryParams = $this->buildQueryParams();
        $this->query($queryParams);
        return $this;
    }

    private function defaultFilterParts() {
        $currentTimeDb = Db::prepareDateForDb(new \DateTime());
        return [
            [
                'range' => [
                    'postDate' => [
                        'lte' => $currentTimeDb,
                    ],
                ],
            ],
            [
                'bool' => [
                    'should' => [
                        [
                            'range' => [
                                'expiryDate' => [
                                    'gt' => $currentTimeDb,
                                ],
                            ],
                        ],
                        [
                            'term' => [
                                'noExpiryDate' => true,
                            ],
                        ],
                    ],
                ],
            ],

        ];
    }

    private function buildQueryParams() {
        return [
            'bool' => [
                'must'   => $this->queryParts,
                'filter' => [
                    'bool' => [
                        'must' => $this->filterParts,
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getSearchFields(): array
    {
        return $this->searchFields;
    }

    /**
     * @param array $searchFields
     */
    public function setSearchFields(array $searchFields): self
    {
        $this->searchFields = $searchFields;
        return $this;
    }

    /**
     * @return string
     */
    public function getSiteAnalyzer(): string
    {
        return $this->siteAnalyzer;
    }

    /**
     * @param string $siteAnalyzer
     */
    public function setSiteAnalyzer(string $siteAnalyzer): self
    {
        $this->siteAnalyzer = $siteAnalyzer;
        return $this;
    }



}