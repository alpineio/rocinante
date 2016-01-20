<?php


namespace AlpineIO\Rocinante\Console\Commands;


use \RecursiveDirectoryIterator;
use \RecursiveIteratorIterator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class RenameCommand extends Command
{
	protected $text_domain;
	protected $prefixed_handles;
	protected $theme_name;
	protected $function_name;
	private $hashes = [];
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
			->addArgument(
				'destination',
				InputArgument::REQUIRED,
				'Destination folder'
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
		$this->theme_name = $input->getArgument('name');
		$destination = $input->getArgument('destination');
		$this->text_domain = strtolower(str_replace(' ', '_', $this->theme_name ));
		$this->function_name = $this->text_domain . '_';
		$this->prefixed_handles = strtolower(str_replace(' ', '-', $this->theme_name)) . '-';
		$output->writeln(sprintf('Theme Name: %s', $this->theme_name));
		$output->writeln(sprintf('Function Name: %s', $this->function_name));
		$output->writeln(sprintf('Prefixed Handles: %s', $this->prefixed_handles));

		$source_dir = dirname(ROCINANTE_COMPOSER_INSTALL) . '/alpineio/underscores';
		//$target_dir = getcwd() . '/build';
		//$target_dir = realpath($destination);
		$target_dir = $destination;
		$this->hashes = $this->get_saved_hashes($target_dir);

		//$this->move_files($source_dir, $target_dir, $input, $output);
		$directory = new RecursiveDirectoryIterator($source_dir);
		//var_dump($directory);
		foreach (new RecursiveIteratorIterator($directory) as $filename=>$current) {
			$current_file_name = $current->getFilename();

			$hash_path = $this->get_hash_path($source_dir, $current->getPathName());

			if ($current_file_name[0] === '.') {
				continue;
			}
			if (false !== strpos($hash_path, '.git')) {
				continue;
			}

			$src = $current->getPathName();
			$dest = $target_dir . $hash_path;

			if ( isset($this->hashes[$hash_path]) && $this->has_file_been_modified($hash_path, $dest) ) {
				$helper = $this->getHelper('question');
				$question = new ConfirmationQuestion(sprintf('File "%s" has been modified. Remove changes?', $hash_path) , false);
				if (!$helper->ask($input, $output, $question)) {
					continue;
				}
			}

//			echo "copy " .  $src . " => " . $dest  . "\n";
			if( ! is_dir( dirname( $dest ) ) ) {
				mkdir( dirname( $dest ) . '/' );
			}

			$file_contents = file_get_contents($src);
			$file_contents = $this->update_strings($file_contents);
			file_put_contents($dest, $file_contents);
			$this->hashes[$hash_path] = md5($file_contents);
		}

		$this->save_hash($target_dir);
	}

	protected function move_files($source_dir, $target_dir, $input, $output) {
		$files = scandir($source_dir);

		foreach ($files as $file ) {
			if ( '.' == $file || '..' == $file) {
				continue;
			}
			$pathname = $source_dir . DIRECTORY_SEPARATOR . $file;
			if ( ! is_dir( $pathname ) ) {
				//var_dump($pathname);
				$target_path = str_replace($source_dir, $target_dir, $pathname);
				$hash_path = $this->get_hash_path($target_dir, $target_path);
				var_dump($target_path);
				if ( isset($this->hashes[$hash_path]) && $this->has_file_been_modified($target_dir, $target_path) ) {
					$helper = $this->getHelper('question');
					$question = new ConfirmationQuestion(sprintf('File "%s" has been modified. Remove changes?', $hash_path) , false);
					if (!$helper->ask($input, $output, $question)) {
						continue;
					}
				}
				if( ! is_dir( dirname( $target_path ) ) ) {
					//mkdir( dirname( $target_path ) . '/' );
				}

				$file_contents = file_get_contents($pathname);
				$file_contents = $this->update_strings($file_contents);
				//file_put_contents($target_path, $file_contents);
				$this->hashes[$hash_path] = md5($file_contents);
			} else {
				$this->move_files($pathname, $target_dir,$input,$output);
			}
		}
	}

	protected function update_strings($file_contents) {
		$file_contents = str_replace( "'_s'", "'" . $this->text_domain . "'", $file_contents);
		$file_contents = str_replace( '_s_', $this->function_name, $file_contents);
		$file_contents = str_replace( 'Text Domain: _s', 'Text Domain: ' . $this->text_domain, $file_contents);
		$file_contents = str_replace( ' _s', ' ' .  $this->theme_name, $file_contents);
		$file_contents = str_replace( '_s-', $this->prefixed_handles, $file_contents);
		return $file_contents;
	}

	protected function has_file_been_modified($hash_path, $existing_file_path  ) {
		$existing_file_hash = md5_file($existing_file_path);
		if ( $existing_file_hash === $this->hashes[$hash_path] ) {
			return false;
		}
		return true;
	}

	protected function get_hash_path($target_dir, $target_path) {
		return str_replace($target_dir, '', $target_path);
	}


	protected function hash_match( $hash_path, $md5 ) {
		if ( $md5 === $this->hashes[$hash_path] ) {
			return true;
		}
		return false;
	}

	protected function save_hash($target_dir) {
		$json = json_encode($this->hashes,JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
		file_put_contents($target_dir . DIRECTORY_SEPARATOR . 'hashes.json', $json);
	}

	protected function get_saved_hashes($target_dir) {
		$hash_file = $target_dir . DIRECTORY_SEPARATOR . 'hashes.json';
		if ( file_exists($hash_file)) {
			return json_decode(file_get_contents($hash_file), true);
		}
		return [];
	}
}