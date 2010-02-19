<?php
/**
 * Lithium: the most rad php framework
 *
 * @copyright     Copyright 2010, Union of RAD (http://union-of-rad.org)
 * @license       http://opensource.org/licenses/bsd-license.php The BSD License
 */

namespace lithium\test\filter;

use \lithium\analysis\Parser;
use \lithium\analysis\Inspector;

/**
 * Calculates the cyclomatic complexity of class methods, and shows worst-offenders and statistics.
 */
class Complexity extends \lithium\test\Filter {

	/**
	 * The list of tokens which represent the starting point of a code branch.
	 */
	protected static $_include = array(
		'T_CASE', 'T_DEFAULT', 'T_CATCH', 'T_IF', 'T_FOR',
		'T_FOREACH', 'T_WHILE', 'T_DO', 'T_ELSEIF'
	);

	/**
	 * Takes an instance of an object (usually a Collection object) containing test
	 * instances. Introspects the test subject classes to extract cyclomatic complexity data.
	 *
	 * @param object $report Instance of Report which is calling apply.
	 * @param array $options Not used.
	 * @return object|void Returns the instance of `$tests`.
	 */
	public static function apply($report, array $options = array()) {
		$tests = $report->group->tests();
		$results = array();
		foreach ($tests->invoke('subject') as $class) {
			$results[$class] = array();

			if (!$methods = Inspector::methods($class, 'ranges')) {
				continue;
			}

			foreach ($methods as $method => $lines) {
				$lines = Inspector::lines($class, $lines);
				$branches = Parser::tokenize(join("\n", (array) $lines), array(
					'include' => static::$_include
				));
				$results[$class][$method] = count($branches) + 1;
				$report->collect(__CLASS__, $results);
			}
		}
		return $tests;
	}

	/**
	 * Analyzes the results of a test run and returns the result of the analysis.
	 *
	 * @param object $report The report instance running this filter and aggregating results
	 * @param array $options Not used.
	 * @return array|void The results of the analysis.
	 */
	public static function analyze($report, array $options = array()) {
		$filterResults = static::collect($report->results['filters'][__CLASS__]);
		$metrics = array('max' => array(), 'class' => array());

		foreach ($filterResults as $class => $methods) {
			if (!$methods) {
				continue;
			}
			$metrics['class'][$class] = array_sum($methods) / count($methods);

			foreach ($methods as $method => $count) {
				$metrics['max']["{$class}::{$method}()"] = $count;
			}
		}

		arsort($metrics['max']);
		arsort($metrics['class']);
		return $metrics;
	}

	/**
	 * Collects raw data aggregated in Report and prepares it for analysis
	 *
	 * @param array $filterResults The results of the filter on the test run.
	 * @return array The packaged results.
	 */
	public static function collect($filterResults) {
		$packagedResults = array();

		foreach ($filterResults as $result) {
			$class = key($result);
			if (!isset($packagedResults[$class])) {
				$packagedResults[$class] = array();
			}
			$packagedResults[$class] = array_merge($result[$class], $packagedResults[$class]);
		}

		return $packagedResults;
	}
}

?>