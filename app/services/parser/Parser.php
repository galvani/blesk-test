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
	public function getParsedXML($sort = true)
	{
		$this->parseData($this->getDataSource()->fetchData()->getFetchedData());

		$parsedData = $sort ? $this->sortData($this->getParsedData()) : $this->getParsedData();

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
	 * @return Parser
	 */
	public function parseData($fetchedData)
	{
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

		$this->setParsedData($parsedData);

		return $this;
	}

	/**
	 * Sort data by amount of items descending, sort webs, cats
	 *
	 * @param $parsedData
	 *
	 * @return mixed
	 */
	protected function sortData($parsedData)
	{
		$items = $parsedData['items'];

		$sortedItems = [];

		array_walk($items, function ($web, $webName) use (&$sortedItems) {
			uasort($web, function ($a, $b) {
				return count($a) < count($b);
			});
			$sortedItems[$webName] = $web;
		});

		uasort($sortedItems, function ($web1, $web2) {
			$web1Count = array_sum(array_map(function ($category) use ($web1) {
				return count($category);
			}, $web1));
			$web2Count = array_sum(array_map(function ($category) use ($web2) {
				return count($category);
			}, $web2));

			return $web1Count < $web2Count;
		});

		$parsedData['items'] = $sortedItems;

		return $parsedData;
	}

	/**
	 * @return BaseDataSource|RssDataSource
	 */
	protected function getDataSource()
	{
		return $this->dataSource;
	}

	/**
	 * @return string
	 */
	public function getPublishedDate()
	{
		$this->parseData($this->getDataSource()->fetchData()->getFetchedData());

		return $this->getParsedData()['pubDate'];
	}

	/**
	 * @return mixed
	 */
	public function getParsedData()
	{
		return $this->parsedData;
	}

	/**
	 * @param $parsedData
	 *
	 * @return BaseDataSource
	 */
	protected function setParsedData($parsedData)
	{
		$this->parsedData = $parsedData;

		return $this;
	}
}