<?php

namespace lhs\elasticsearch\queries;

use craft\helpers\Db;
use yii\elasticsearch\ActiveQuery;

class ElasticsearchQuery extends ActiveQuery
{
    private $filterParts = [];
    private $queryParts = [];
    private $searchFields = [];
    private $siteAnalyzer = '';

    public function __construct($modelClass, $config = [])
    {
        parent::__construct($modelClass, $config);
        $this->filterParts = $this->defaultFilterParts();
    }

    public function parseQueryParameters(array $queryParameters) {
        $this->queryParts = $queryParameters['bool']['must'];
        $this->filterParts = $queryParameters['bool']['filter']['bool']['must'];
        $this->query($this->buildQueryParams());
        return $this;
    }

    public function section($sectionHandle) {
        /**
         * @todo: sectionHandle is indexed dynamically by extension module.
         * Find a way to code this dynamically by extension, too
         */
        $this->filterParts[] = [
            'term' => [
                'sectionHandle' => [
                    'value' => $sectionHandle,
                ],
            ],
        ];
        $this->query($this->buildQueryParams());
        return $this;
    }
    public function type($typeHandle) {
        $this->filterParts[] = [
            'term' => [
                'type' => [
                    'value' => $typeHandle,
                ],
            ],
        ];
        $this->query($this->buildQueryParams());
        $this->orderBy('score');
        return $this;
    }

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