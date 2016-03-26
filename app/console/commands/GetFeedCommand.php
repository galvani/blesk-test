<?php
/**
 * @author Jan Kozak <galvani78@gmail.com>
 * @since 2016-03-24
 */

namespace App\Console\Command;

use App\Parser\Parser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetFeedCommand extends Command
{

	protected function configure()
	{
		$this->setName('app:feed')
			->setDescription('Fetches RSS feed');
		$this->addOption('published-date-only', 'p', null, 'Returns only the published date');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		/** @var Parser $parser */
		$parser = $this->getHelper('container')->getContainer()->getService('Parser');

		try {
			$outputString = ($input->getOption('published-date-only')) ? $parser->getPublishedDate() : $parser->getParsedXML(true);
		} catch (\ApplicationException $e) {
			$output->writeLn('<error>' . $e->getMessage() . '</error>');

			return 1; // non-zero return code means error
		}

		$output->write($outputString);

		return 0;
	}
}