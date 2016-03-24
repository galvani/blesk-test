<?php
/**
 * @author Jan Kozak <galvani78@gmail.com>
 * @since 2016-03-24
 */

namespace App\Parser;
use App\Exception\ApplicationLogicException;

/**
 * Class Parser
 *
 * @package App\Parser
 */
class Parser
{
    /** @var DataSource */
    protected $dataSource;

    /** @var mixed */
    private $parsedData;

    /** @var array */
    protected $inputData;

    public function __construct(BaseDataSource $dataSource)
    {
        $this->dataSource = $dataSource;
    }


    /**
     * Generates and returns XML from parsed data
     *
     * @return string
     * @throws ApplicationLogicException
     */
    public function getParsedXML()
    {
        $parsedData = $this->getParsedData();

        if (is_null($parsedData)) {
            throw new ApplicationLogicException('You need to fetch data prior to generating XML output');
        }

        $doc = new \DOMDocument('1.0', 'utf8');
        $export = $doc->createElement('export');
        $export->setAttribute('date', $parsedData['pubDate']);

        foreach ($parsedData['items'] as $web => $categories) {
            $webNode = $doc->createElement('web');
            $webNode->setAttribute('url', 'http://' . $web);

            foreach ($categories as $category => $items) {
                $categoryNode = $doc->createElement('category');
                $categoryNode->setAttribute('name', $category);

                foreach ($items as $item) {
                    $categoryNode->appendChild($doc->importNode($item, true));
                }

                $webNode->appendChild($categoryNode);
            }

            $export->appendChild($webNode);
        }

        $doc->appendChild($export);
        $doc->formatOutput = true; // Just for presentation

        return $doc->saveXML();
    }

    /**
     * Parses fetched data into a property
     *
     * @return RssDataSource
     */
    public function parseData($fetchedData)
    {
        $sortArray = array();
        try {
            $doc = new \DOMDocument();
            $doc->validateOnParse = true;
            $doc->loadXML($fetchedData);

            $parsedData['pubDate'] = $doc->getElementsByTagName('pubDate')->item(0)->nodeValue;

            $channelNode = $doc->getElementsByTagName('channel')->item(0);

            foreach ($channelNode->getElementsByTagName('item') as $item) {
                if (!preg_match('/http:\/\/([^\/]*)\/[^\/]*\/([^\/]*)/', $item->getElementsByTagName('link')->item(0)->nodeValue, $matches)) {
                    throw new \DataSourceException('Invalid input data format');
                }
                $parsedData['items'][$matches[1]][$matches[2]][] = $item;
            }
        } catch (\Exception $e) {
            throw new DataSourceException($e->getMessage());
        }

        //  Now lets sort it
        array_multisort(array_map('count', $parsedData), SORT_DESC, $parsedData);

        $this->setParsedData($parsedData);

        return $this;
    }

    /**
     * @return BaseDataSource|RssDataSource
     */
    protected function getDataSource()
    {
        return $this->dataSource;
    }

    public function getPublishedDate()
    {
        return $this->getParsedData()['pubDate'];
    }

    /**
     * @return mixed
     */
    public function getParsedData()
    {
        if (is_null($this->parsedData)) {
            $this->parseData($this->getDataSource()->fetchData()->getFetchedData());
        }
        return $this->parsedData;
    }

    /**
     * @param $parsedData
     * @return BaseDataSource
     */
    protected function setParsedData($parsedData)
    {
        $this->parsedData = $parsedData;

        return $this;
    }
}