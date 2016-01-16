<?php


namespace AlpineIO\Rocinante\Console\Commands;


use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RenameCommand extends Command
{
	protected function configure()
	{
		$this
			->setName('theme:regen')
			->setDescription('Regenerate theme from _s\'s')
			->addArgument(
				'name',
				InputArgument::REQUIRED,
				'Name of new theme?'
			)
			/*
			->addOption(
				'yell',
				null,
				InputOption::VALUE_NONE,
				'If set, the task will yell in uppercase letters'
			)
			*/
		;
	}
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$name = $input->getArgument('name');
		$text_domain = strtolower(str_replace(' ', '_', $name ));
		$function_name = $text_domain . '_';
		$prefixed_handles = strtolower(str_replace(' ', '-', $name)) . '-';
		$output->writeln(sprintf('Theme Name: %s', $name));
		$output->writeln(sprintf('Function Name: %s', $function_name));
		$output->writeln(sprintf('Prefixed Handles: %s', $prefixed_handles));

		$source_dir = dirname(ROCINANTE_COMPOSER_INSTALL) . '/automattic/_s';
		$target_dir = './build';

		$files = scandir($source_dir);
		var_dump($files);

		foreach ($files as $file ) {
			$pathname = $source_dir . DIRECTORY_SEPARATOR . $file;
			if ( ! is_dir( $pathname ) ) {
				var_dump($pathname);
				$file_contents = file_get_contents($pathname);

			}
		}
	}
}