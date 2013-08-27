<?php

class ThisOr {

	const BLANK = '';
	const FALSE = false;
	const NULL  = null;
	const ZERO  = 0;

	public static function __callStatic($return, $args) {
		if ($return == 'that')
			$return = array_pop($args);
		else
			$return = constant('ThisOr::'.strtoupper($return));

		if (count($args) == 1) {
			return isset($args[0]) ? $args[0] : $return;
		} elseif (count($args) == 2) {
			if (is_scalar($args[0])) {
				if (is_array($args[1]))
					return array_key_exists($args[0], $args[1]) ? $args[1][$args[0]] : $return;
				elseif(is_object($args[1]))
					return property_exists($args[1], $args[0]) ? $args[1]->$args[0] : $return;
			} elseif (is_scalar($args[1])) {
				if (is_array($args[0]))
					return array_key_exists($args[1], $args[0]) ? $args[0][$args[1]] : $return;
				elseif(is_object($args[0]))
					return property_exists($args[0], $args[1]) ? $args[0]->$args[1] : $return;
			}
		}
	}

}