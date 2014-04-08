<?php
namespace EllisLab\ExpressionEngine\Core\Validation\Rule;

use EllisLab\ExpressionEngine\Core\Validation\ValidationRule as ValidationRule;

/**
 *
 */
class AlphaDash extends ValidationRule {

	public function validate($value)
	{
		return ( ! preg_match("/^([-a-z0-9_-])+$/i", $value)) ? FALSE : TRUE;
	}

}
