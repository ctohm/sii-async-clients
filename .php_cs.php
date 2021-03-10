<?php

/**
 * Admin FC
 */

use Ergebnis\PhpCsFixer\Config;

\define('PROJECT_ROOT', \str_replace('/.vscode', '', __DIR__));
$json_path = PROJECT_ROOT . '/composer.json';
$composerinfo = \json_decode(\file_get_contents($json_path));

require_once PROJECT_ROOT . '/vendor/autoload.php';
$version = $composerinfo->extra->version;
$header = "Admin Financiatec";

$config = Config\Factory::fromRuleSet(new Config\RuleSet\Php74($header), [
	'declare_strict_types' => false,
	//'header_comment' => ['commentType' => 'PHPDoc', 'header' => $header],
	'escape_implicit_backslashes' => false,
	'final_class' => false,
	'final_internal_class' => false,
	'final_public_method_for_abstract_class' => false,
	'final_static_access' => false,
	'global_namespace_import' => false,
	'fully_qualified_strict_types' => false,
	'visibility_required' => [
		'elements' => [
			'method',
			'property',
		],
	],
]);
$project_path = getcwd();
$config->getFinder()
	->in([
		PROJECT_ROOT . '/app',
		PROJECT_ROOT . '/config',
		PROJECT_ROOT . '/database',
		PROJECT_ROOT . '/routes',
		PROJECT_ROOT . '/tests',
	])
	->name('*.php')
	->notName('*.blade.php')
	->exclude([
		'.build',
		'.configs',
		'__pycache__',
		'assets',
		'docs',
		'node_modules',
		'temp',
		'tests',
		'storage',
		'dist',
		'fabfile',
		'public',
		'resources',
		'sessions',
		'src', 'temp', 'tests',

		'vendor',
		'.github',
	])
	->name('.php_cs.php')
	->ignoreDotFiles(true)
	->ignoreVCS(true);

$config->setCacheFile(PROJECT_ROOT . '/.build/phpcs/csfixer.cache');

return $config;
