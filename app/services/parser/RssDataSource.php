<?php
/**
 * @author Jan Kozak <galvani78@gmail.com>
 * @since 2016-03-24
 */

namespace App\Parser;

use App\Exception\DataSourceException;
use App\Exception\InvalidConfigurationException;

/**
 * Data source for remote RSS XML feed
 *
 * Class RssDataSource
 * @package App\Parser
 */
class RssDataSource extends BaseDataSource
{

	/**
	 * @inheritdoc
	 */
	public function __construct($parameters)
	{
		if (!isset($parameters['rss']['url'])) {
			throw new InvalidConfigurationException('Missing configuration for RSS data source');
		}
		parent::__construct($parameters['rss']);
	}

	/**
	 * @param bool $forceFetch
	 *
	 * @return RssDataSource
	 * @throws \DataSourceException
	 */
	public function fetchData($forceFetch = false)
	{
		if (!is_null($this->getFetchedData()) && !$forceFetch) {
			return $this;
		}
		try {
			$data = file_get_contents($this->getParameter('url'));
		} catch (\Exception $e) {
			throw new \DataSourceException($e);
		}
		$this->setFetchedData($data);

		return $this;
	}

}