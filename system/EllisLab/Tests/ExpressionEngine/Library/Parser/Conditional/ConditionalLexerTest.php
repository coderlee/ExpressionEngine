<?php

namespace EllisLab\Tests\ExpressionEngine\Library\Parser\Conditional;

use EllisLab\ExpressionEngine\Library\Parser\Conditional\ConditionalLexer;

class ConditionalLexerTest extends \PHPUnit_Framework_TestCase {

	protected $valueTypes = array(
		'bool'				=> array('token' => array('BOOL', 'TRUE'),									'value' => 'TRUE'),
		'int'				=> array('token' => array('NUMBER', '5'),									'value' => '5'),
		'negative'			=> array('token' => array(array('OPERATOR', '-'), array('NUMBER', '5')),	'value' => '-5'),
		'bigfloat'			=> array('token' => array('NUMBER', '5.1'),									'value' => '5.1'),
		'littlefloat'		=> array('token' => array('NUMBER', '.1'),									'value' => '.1'),
		'string'			=> array('token' => array('STRING', 'string'),								'value' => '"string"'),
		'dash-string'		=> array('token' => array('STRING', 'dash-string'),							'value' => '"dash-string"'),
		'dot.string'		=> array('token' => array('STRING', 'dot.string'),							'value' => '"dot.string"'),
		'intstring'			=> array('token' => array('STRING', '5'),									'value' => '"5"'),
		'variable'			=> array('token' => array('VARIABLE', 'variable'),							'value' => 'variable'),
		'dash-variable'		=> array('token' => array('VARIABLE', 'dash-variable'),						'value' => 'dash-variable'),
		'simpletag'			=> array('token' => array('TAG', '{simpletag}'),							'value' => '{simpletag}'),
		'moduletag'			=> array('token' => array('TAG', '{exp:foo:bar}'),							'value' => '{exp:foo:bar}'),
		'tag_with_params'	=> array('token' => array('TAG', '{exp:foo:bar param="value"}'),			'value' => '{exp:foo:bar param="value"}')
	);

	protected $commonTokens = array(
		'start' => array(
			array('LD', '{'),
			array('IF', 'if'),
			array('WHITESPACE', ' ')
		),
		'end'   => array(
			array('RD',					'}'),
			array('TEMPLATE_STRING',	'out'),
			array('LD',					'{'),
			array('ENDIF',				'/if'),
			array('RD',					'}'),
			array('EOS',				TRUE)
		)
	);

	public function setUp()
	{
		$this->lexer = new ConditionalLexer();
	}

	public function tearDown()
	{
		$this->lexer = NULL;
	}

	protected function runLexer($description, $str_in, $expected)
	{
		$result = $this->lexer->tokenize($str_in);
		$this->assertSame($expected, $result, $description);
	}

	/**
	 * @dataProvider goodDataProvider
	 */
	public function testGoodDataProvider($description, $str_in, $expected)
	{
		$this->runLexer($description, $str_in, $expected);
	}

	/**
	 * @dataProvider badDataProvider
	 */
	public function testBadDataProvider($description, $str_in, $code)
	{
		$this->setExpectedException('EllisLab\ExpressionEngine\Library\Parser\Conditional\Exception\ConditionalLexerException', $code);
		$this->lexer->tokenize($str_in);
	}

	protected function assembleCommonCondition($expression)
	{
		return "{if ".$expression."}out{/if}";
	}

	protected function assembleCommonTokens($tokens_in)
	{
		$tokens = array();

		foreach ($tokens_in as $token)
		{
			if (is_array($token[0]))
			{
				$tokens = array_merge($tokens, $token);
			}
			else
			{
				$tokens[] = $token;
			}
		}

		return array_merge(
			$this->commonTokens['start'],
			$tokens,
			$this->commonTokens['end']
		);
	}

	public function goodDataProvider()
	{
		// assemble all of the tests
		return array_merge(
			array(),

			// Insure coverage for each token type
			$this->basicTokens(),

			// Individual Tokens
			$this->validNumberTokens(),
			$this->validVariableTokens(),

			// Operators
			$this->validOperatorsWithSpaces(),
			$this->validOperatorsWithoutSpaces(),
			$this->operatorCombinationsWithSpaces(),
			$this->operatorCombinationsWithoutSpaces(),
			$this->edgyOperatorCombinationsWithoutSpaces(),
			$this->edgyDoubleDashWithoutSpaces(),
			$this->edgyDotDashWithNumbersAndNoSpaces(),
			$this->edgyDoubleDotWithNumbersAndNoSpaces(),

			array() // non trailing comma thing for covienence
		);
	}

	public function badDataProvider()
	{
		return array(
			array('Unclosed String (single quotes)',	"{if string == 'ee}out{/if}", 30),
			array('Unclosed String (double quotes)',	'{if string == "ee}out{/if}', 30),
			array('If as a Prefix', 					'{if:foo}', 20),
			array('Ifelse duplicity', 					'{if 5 == 5}out{if:else:else}out{/if}', 20),
			array('Ifelse Prefixing', 					'{if 5 == 5}out{if:elsebeth}out{/if}', 20),
			array('Ifelseif Prefixing', 				'{if 5 == 5}out{if:elseiffy}out{/if}', 20),
		//	array('Unclosed Conditional', 				'{if string == "ee"}out', 21),
		//	array('Unterminated Conditional', 			'{if string == "ee"out{/if}', 30),
		//	array('NUMBER + :', 						'{if 1:2}out{/if}', 10),
		//	array('OK + :',	 							'{if :foo}out{/if}', 10),
		//	array('OK + :',	 							'{if "foo":bar}out{/if}', 10),
		//	array('OK + :',	 							"{if 'foo':bar}out{/if}", 10),
		//	array('FLOAT + :', 							'{if 1.2:3}out{/if}', 10),
		//	array('Simple Backticks',					'{if `echo hello`}out{/if}', 5),
		//	array('Splitting Backticks',				'{if string.`echo hello #}out{/if}{if `== 0}out{/if}', 5),
		//	array('Simple Comments',					'{if php/* test == 5*/info(); }out{/if}', 5),
		//	array('Splitting Comments',					'{if string /* == 5 }out{/if}{if */phpinfo(); == 5}out{/if}', 5),
		);
	}

	protected function validOperatorsWithSpaces()
	{
		$return = array();

		$operators = array(
			'||', '&&', '**',
			'==', '!=', '<=', '>=', '<>', '<', '>',
			'%', '+', '-', '*', '/',
			'.', '!', '^',
			'^=', '*=', '$=',
		);

		// Test each operator (duh)
		foreach ($operators as $operator)
		{
			// Testing our common value types for edge-cases.
			// We don't need to care about permutations here just combinations
			// because we need to ensure that these value types are found
			// on both sides of an operator.
			foreach ($this->valueTypes as $type => $value)
			{
				$expected = array(
					$value['token'],
					array('WHITESPACE',	' '),
					array('OPERATOR',	$operator),
					array('WHITESPACE',	' '),
					$value['token']
				);

				$return[] = array(
					"The \"{$operator}\" operator with {$type} values",
					$this->assembleCommonCondition($value['value']." ".$operator." ".$value['value']),
					$this->assembleCommonTokens($expected)
				);
			}
		}

		return $return;
	}

	// Things change without spaces around the operator
	protected function validOperatorsWithoutSpaces()
	{
		$return = array();

		$operators = array(
			'||', '&&', '**',
			'==', '!=', '<=', '>=', '<>', '<', '>',
			'%', '+', '-', '*', '/',
			'.', '!', '^',
			'^=', '*=', '$=',
		);

		// Test each operator (duh)
		foreach ($operators as $operator)
		{
			// Testing our common value types for edge-cases.
			// We don't need to care about permutations here just combinations
			// because we need to ensure that these value types are found
			// on both sides of an operator.
			foreach ($this->valueTypes as $type => $value)
			{
				// Some exceptions for exceptional operators
				if ($operator == '-')
				{
					if ($type == 'bool' || $type == 'variable' || $type == 'dash-variable')
					{
						continue;
					}
				}
				elseif ($operator == '.')
				{
					if ($type == 'int' || $type == 'bigfloat' || $type == 'littlefloat' || $type == 'negative')
					{
						continue;
					}
				}

				$expected = array(
					$value['token'],
					array('OPERATOR', $operator),
					$value['token']
				);

				$return[] = array(
					"The \"{$operator}\" operator with {$type} values (no spaces)",
					$this->assembleCommonCondition($value['value'].$operator.$value['value']),
					$this->assembleCommonTokens($expected)
				);
			}
		}

		// Manual tests for the '.' operator's exceptions
		$operator = '.';

		// int.int -> NUMBER
		$value = $this->valueTypes['int']['value'];
		$expected = array(
			array('NUMBER',	$value.'.'.$value)
		);
		$return[] = array(
			"The \"{$operator}\" operator with int values (no spaces)",
			$this->assembleCommonCondition($value.$operator.$value),
			$this->assembleCommonTokens($expected)
		);

		// int.int.int -> NUMBER(int.int), NUMBER(.int)
		$value = $this->valueTypes['int']['value'];
		$expected = array(
			array('NUMBER',	$value.'.'.$value),
			array('NUMBER',	'.'.$value)
		);
		$return[] = array(
			"The \"{$operator}\" operator with three int values (no spaces)",
			$this->assembleCommonCondition($value.$operator.$value.$operator.$value),
			$this->assembleCommonTokens($expected)
		);

		// int.negative -> OPERATOR
		$int = $this->valueTypes['int'];
		$negative = $this->valueTypes['negative'];
		$expected = array(
			array($int['token'][0], $int['token'][1].'.'),
			array('OPERATOR', '-'),
			$negative['token'][1]
		);
		$return[] = array(
			"The \"{$operator}\" operator with int and negative values (no spaces)",
			$this->assembleCommonCondition($int['value'].$operator.$negative['value']),
			$this->assembleCommonTokens($expected)
		);

		// negative.negative -> OPERATOR
		$negative = $this->valueTypes['negative'];
		$expected = array(
			$negative['token'][0],
			array($negative['token'][1][0], $negative['token'][1][1].'.'),
			array('OPERATOR', '-'),
			$negative['token'][1]
		);
		$return[] = array(
			"The \"{$operator}\" operator with int and negative values (no spaces)",
			$this->assembleCommonCondition($negative['value'].$operator.$negative['value']),
			$this->assembleCommonTokens($expected)
		);

		// negative.int -> NUMBER
		$int = $this->valueTypes['int'];
		$negative = $this->valueTypes['negative'];
		$expected = array(
			array('OPERATOR', '-'),
			array('NUMBER',	$negative['token'][1][1].'.'.$int['value'])
		);
		$return[] = array(
			"The \"{$operator}\" operator with int and negative values (no spaces)",
			$this->assembleCommonCondition($negative['value'].$operator.$int['value']),
			$this->assembleCommonTokens($expected)
		);

		// *float.* -> EXCEPTION (this is covered in our exceptions test)

		// Manual tests for the '-' operator's exceptions
		$operator = '-';

		// bool-bool -> variable
		// variable-variable -> variable
		// dash-variable-dash-variable -> variable
		foreach (array('bool', 'variable', 'dash-variable') as $type)
		{
			$value = $this->valueTypes[$type]['value'];
			$expected = array(
				array('VARIABLE', $value.'-'.$value)
			);
			$return[] = array(
				"The \"{$operator}\" operator with {$type} values (no spaces)",
				$this->assembleCommonCondition($value.$operator.$value),
				$this->assembleCommonTokens($expected)
			);
		}

		return $return;
	}

	protected function operatorCombinationsWithSpaces()
	{
		$return = array();

		$operator_combinations = array(
			'==='	=> array(array('OPERATOR', '=='),	array('MISC', '=')),

			/**
			 * The following array elements were generated with this code:
			 *
			 * $valid_operators = array(
			 * 	'||', '&&', '**',
			 * 	'==', '!=', '<=', '>=', '<>', '<', '>',
			 * 	'%', '+', '-', '*', '/',
			 *	'.', '!', '^',
			 *	'^=', '*=', '$=',
			 * );
             *
			 * // Build out some combinations
			 * foreach ($valid_operators as $first)
			 * {
			 * 	foreach ($valid_operators as $second)
			 * 	{
			 * 		$operator = $first.$second;
             *
			 * 		if (in_array($operator, $valid_operators) ||
			 * 			isset($invalid_operators[$operator]))
			 * 		{
			 * 			continue;
			 * 		}
             *
			 * 		// Handle the case where first.second create a valid
			 * 		// operator in the first half: ! + == => !== => OP(!=) MISC(=)
			 * 		if (in_array($first.$second[0], $valid_operators))
			 * 		{
			 * 			$first = $first.$second[0];
			 * 			$second = substr($second, 1);
			 * 		}
             *
			 * 		$token = (in_array($second, $valid_operators)) ? 'OPERATOR' : 'MISC';
             *
			 * 		printf("'%s'\t=> array(array('OPERATOR', '%s'),\tarray('%s', '%s')),\n", $operator, $first, $token, $second);
			 * 	}
			 * 	print "\n";
			 * }
			 */

			'||||'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '||')),
			'||&&'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '&&')),
			'||**'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '**')),
			'||=='	=> array(array('OPERATOR', '||'),	array('OPERATOR', '==')),
			'||!='	=> array(array('OPERATOR', '||'),	array('OPERATOR', '!=')),
			'||<='	=> array(array('OPERATOR', '||'),	array('OPERATOR', '<=')),
			'||>='	=> array(array('OPERATOR', '||'),	array('OPERATOR', '>=')),
			'||<>'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '<>')),
			'||<'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '<')),
			'||>'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '>')),
			'||%'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '%')),
			'||+'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '+')),
			'||-'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '-')),
			'||*'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '*')),
			'||/'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '/')),
			'||.'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '.')),
			'||!'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '!')),
			'||^'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '^')),
			'||^='	=> array(array('OPERATOR', '||'),	array('OPERATOR', '^=')),
			'||*='	=> array(array('OPERATOR', '||'),	array('OPERATOR', '*=')),
			'||$='	=> array(array('OPERATOR', '||'),	array('OPERATOR', '$=')),

			'&&||'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '||')),
			'&&&&'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '&&')),
			'&&**'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '**')),
			'&&=='	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '==')),
			'&&!='	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '!=')),
			'&&<='	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '<=')),
			'&&>='	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '>=')),
			'&&<>'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '<>')),
			'&&<'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '<')),
			'&&>'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '>')),
			'&&%'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '%')),
			'&&+'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '+')),
			'&&-'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '-')),
			'&&*'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '*')),
			'&&/'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '/')),
			'&&.'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '.')),
			'&&!'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '!')),
			'&&^'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '^')),
			'&&^='	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '^=')),
			'&&*='	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '*=')),
			'&&$='	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '$=')),

			'**||'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '||')),
			'**&&'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '&&')),
			'****'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '**')),
			'**=='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '==')),
			'**!='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '!=')),
			'**<='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '<=')),
			'**>='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '>=')),
			'**<>'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '<>')),
			'**<'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '<')),
			'**>'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '>')),
			'**%'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '%')),
			'**+'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '+')),
			'**-'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '-')),
			'***'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '*')),
			'**/'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '/')),
			'**.'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '.')),
			'**!'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '!')),
			'**^'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '^')),
			'**^='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '^=')),
			'***='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '*=')),
			'**$='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '$=')),

			'==||'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '||')),
			'==&&'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '&&')),
			'==**'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '**')),
			'===='	=> array(array('OPERATOR', '=='),	array('OPERATOR', '==')),
			'==!='	=> array(array('OPERATOR', '=='),	array('OPERATOR', '!=')),
			'==<='	=> array(array('OPERATOR', '=='),	array('OPERATOR', '<=')),
			'==>='	=> array(array('OPERATOR', '=='),	array('OPERATOR', '>=')),
			'==<>'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '<>')),
			'==<'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '<')),
			'==>'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '>')),
			'==%'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '%')),
			'==+'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '+')),
			'==-'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '-')),
			'==*'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '*')),
			'==/'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '/')),
			'==.'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '.')),
			'==!'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '!')),
			'==^'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '^')),
			'==^='	=> array(array('OPERATOR', '=='),	array('OPERATOR', '^=')),
			'==*='	=> array(array('OPERATOR', '=='),	array('OPERATOR', '*=')),
			'==$='	=> array(array('OPERATOR', '=='),	array('OPERATOR', '$=')),

			'!=||'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '||')),
			'!=&&'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '&&')),
			'!=**'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '**')),
			'!==='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '==')),
			'!=!='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '!=')),
			'!=<='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '<=')),
			'!=>='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '>=')),
			'!=<>'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '<>')),
			'!=<'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '<')),
			'!=>'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '>')),
			'!=%'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '%')),
			'!=+'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '+')),
			'!=-'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '-')),
			'!=*'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '*')),
			'!=/'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '/')),
			'!=.'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '.')),
			'!=!'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '!')),
			'!=^'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '^')),
			'!=^='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '^=')),
			'!=*='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '*=')),
			'!=$='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '$=')),

			'<=||'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '||')),
			'<=&&'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '&&')),
			'<=**'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '**')),
			'<==='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '==')),
			'<=!='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '!=')),
			'<=<='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '<=')),
			'<=>='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '>=')),
			'<=<>'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '<>')),
			'<=<'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '<')),
			'<=>'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '>')),
			'<=%'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '%')),
			'<=+'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '+')),
			'<=-'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '-')),
			'<=*'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '*')),
			'<=/'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '/')),
			'<=.'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '.')),
			'<=!'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '!')),
			'<=^'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '^')),
			'<=^='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '^=')),
			'<=*='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '*=')),
			'<=$='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '$=')),

			'>=||'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '||')),
			'>=&&'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '&&')),
			'>=**'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '**')),
			'>==='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '==')),
			'>=!='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '!=')),
			'>=<='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '<=')),
			'>=>='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '>=')),
			'>=<>'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '<>')),
			'>=<'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '<')),
			'>=>'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '>')),
			'>=%'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '%')),
			'>=+'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '+')),
			'>=-'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '-')),
			'>=*'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '*')),
			'>=/'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '/')),
			'>=.'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '.')),
			'>=!'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '!')),
			'>=^'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '^')),
			'>=^='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '^=')),
			'>=*='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '*=')),
			'>=$='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '$=')),

			'<>||'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '||')),
			'<>&&'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '&&')),
			'<>**'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '**')),
			'<>=='	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '==')),
			'<>!='	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '!=')),
			'<><='	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '<=')),
			'<>>='	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '>=')),
			'<><>'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '<>')),
			'<><'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '<')),
			'<>>'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '>')),
			'<>%'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '%')),
			'<>+'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '+')),
			'<>-'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '-')),
			'<>*'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '*')),
			'<>/'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '/')),
			'<>.'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '.')),
			'<>!'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '!')),
			'<>^'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '^')),
			'<>^='	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '^=')),
			'<>*='	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '*=')),
			'<>$='	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '$=')),

			'<||'	=> array(array('OPERATOR', '<'),	array('OPERATOR', '||')),
			'<&&'	=> array(array('OPERATOR', '<'),	array('OPERATOR', '&&')),
			'<**'	=> array(array('OPERATOR', '<'),	array('OPERATOR', '**')),
			'<=='	=> array(array('OPERATOR', '<='),	array('MISC', '=')),
			'<=!='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '!=')),
			'<=<='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '<=')),
			'<=>='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '>=')),
			'<=<>'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '<>')),
			'<=<'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '<')),
			'<=>'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '>')),
			'<=%'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '%')),
			'<=+'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '+')),
			'<=-'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '-')),
			'<=*'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '*')),
			'<=/'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '/')),
			'<=.'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '.')),
			'<=!'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '!')),
			'<=^'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '^')),
			'<=^='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '^=')),
			'<=*='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '*=')),
			'<=$='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '$=')),

			'>||'	=> array(array('OPERATOR', '>'),	array('OPERATOR', '||')),
			'>&&'	=> array(array('OPERATOR', '>'),	array('OPERATOR', '&&')),
			'>**'	=> array(array('OPERATOR', '>'),	array('OPERATOR', '**')),
			'>=='	=> array(array('OPERATOR', '>='),	array('MISC', '=')),
			'>=!='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '!=')),
			'>=<='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '<=')),
			'>=>='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '>=')),
			'>=<>'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '<>')),
			'>=<'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '<')),
			'>=>'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '>')),
			'>=%'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '%')),
			'>=+'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '+')),
			'>=-'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '-')),
			'>=*'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '*')),
			'>=/'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '/')),
			'>=.'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '.')),
			'>=!'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '!')),
			'>=^'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '^')),
			'>=^='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '^=')),
			'>=*='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '*=')),
			'>=$='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '$=')),

			'%||'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '||')),
			'%&&'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '&&')),
			'%**'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '**')),
			'%=='	=> array(array('OPERATOR', '%'),	array('OPERATOR', '==')),
			'%!='	=> array(array('OPERATOR', '%'),	array('OPERATOR', '!=')),
			'%<='	=> array(array('OPERATOR', '%'),	array('OPERATOR', '<=')),
			'%>='	=> array(array('OPERATOR', '%'),	array('OPERATOR', '>=')),
			'%<>'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '<>')),
			'%<'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '<')),
			'%>'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '>')),
			'%%'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '%')),
			'%+'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '+')),
			'%-'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '-')),
			'%*'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '*')),
			'%/'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '/')),
			'%.'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '.')),
			'%!'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '!')),
			'%^'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '^')),
			'%^='	=> array(array('OPERATOR', '%'),	array('OPERATOR', '^=')),
			'%*='	=> array(array('OPERATOR', '%'),	array('OPERATOR', '*=')),
			'%$='	=> array(array('OPERATOR', '%'),	array('OPERATOR', '$=')),

			'+||'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '||')),
			'+&&'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '&&')),
			'+**'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '**')),
			'+=='	=> array(array('OPERATOR', '+'),	array('OPERATOR', '==')),
			'+!='	=> array(array('OPERATOR', '+'),	array('OPERATOR', '!=')),
			'+<='	=> array(array('OPERATOR', '+'),	array('OPERATOR', '<=')),
			'+>='	=> array(array('OPERATOR', '+'),	array('OPERATOR', '>=')),
			'+<>'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '<>')),
			'+<'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '<')),
			'+>'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '>')),
			'+%'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '%')),
			'++'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '+')),
			'+-'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '-')),
			'+*'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '*')),
			'+/'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '/')),
			'+.'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '.')),
			'+!'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '!')),
			'+^'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '^')),
			'+^='	=> array(array('OPERATOR', '+'),	array('OPERATOR', '^=')),
			'+*='	=> array(array('OPERATOR', '+'),	array('OPERATOR', '*=')),
			'+$='	=> array(array('OPERATOR', '+'),	array('OPERATOR', '$=')),

			'-||'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '||')),
			'-&&'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '&&')),
			'-**'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '**')),
			'-=='	=> array(array('OPERATOR', '-'),	array('OPERATOR', '==')),
			'-!='	=> array(array('OPERATOR', '-'),	array('OPERATOR', '!=')),
			'-<='	=> array(array('OPERATOR', '-'),	array('OPERATOR', '<=')),
			'->='	=> array(array('OPERATOR', '-'),	array('OPERATOR', '>=')),
			'-<>'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '<>')),
			'-<'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '<')),
			'->'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '>')),
			'-%'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '%')),
			'-+'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '+')),
			'--'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '-')),
			'-*'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '*')),
			'-/'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '/')),
			'-.'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '.')),
			'-!'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '!')),
			'-^'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '^')),
			'-^='	=> array(array('OPERATOR', '-'),	array('OPERATOR', '^=')),
			'-*='	=> array(array('OPERATOR', '-'),	array('OPERATOR', '*=')),
			'-$='	=> array(array('OPERATOR', '-'),	array('OPERATOR', '$=')),

			'*||'	=> array(array('OPERATOR', '*'),	array('OPERATOR', '||')),
			'*&&'	=> array(array('OPERATOR', '*'),	array('OPERATOR', '&&')),
			'***'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '*')),
			'**=='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '==')),
			'**!='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '!=')),
			'**<='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '<=')),
			'**>='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '>=')),
			'**<>'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '<>')),
			'**<'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '<')),
			'**>'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '>')),
			'**%'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '%')),
			'**+'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '+')),
			'**-'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '-')),
			'***'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '*')),
			'**/'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '/')),
			'**.'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '.')),
			'**!'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '!')),
			'**^'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '^')),
			'**^='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '^=')),
			'***='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '*=')),
			'**$='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '$=')),

			'/||'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '||')),
			'/&&'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '&&')),
			'/**'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '**')),
			'/=='	=> array(array('OPERATOR', '/'),	array('OPERATOR', '==')),
			'/!='	=> array(array('OPERATOR', '/'),	array('OPERATOR', '!=')),
			'/<='	=> array(array('OPERATOR', '/'),	array('OPERATOR', '<=')),
			'/>='	=> array(array('OPERATOR', '/'),	array('OPERATOR', '>=')),
			'/<>'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '<>')),
			'/<'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '<')),
			'/>'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '>')),
			'/%'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '%')),
			'/+'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '+')),
			'/-'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '-')),
			'/*'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '*')),
			'//'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '/')),
			'/.'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '.')),
			'/!'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '!')),
			'/^'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '^')),
			'/^='	=> array(array('OPERATOR', '/'),	array('OPERATOR', '^=')),
			'/*='	=> array(array('OPERATOR', '/'),	array('OPERATOR', '*=')),
			'/$='	=> array(array('OPERATOR', '/'),	array('OPERATOR', '$=')),

			'.||'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '||')),
			'.&&'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '&&')),
			'.**'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '**')),
			'.=='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '==')),
			'.!='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '!=')),
			'.<='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '<=')),
			'.>='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '>=')),
			'.<>'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '<>')),
			'.<'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '<')),
			'.>'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '>')),
			'.%'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '%')),
			'.+'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '+')),
			'.-'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '-')),
			'.*'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '*')),
			'./'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '/')),
			'..'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '.')),
			'.!'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '!')),
			'.^'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '^')),
			'.^='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '^=')),
			'.*='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '*=')),
			'.$='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '$=')),

			'!||'	=> array(array('OPERATOR', '!'),	array('OPERATOR', '||')),
			'!&&'	=> array(array('OPERATOR', '!'),	array('OPERATOR', '&&')),
			'!**'	=> array(array('OPERATOR', '!'),	array('OPERATOR', '**')),
			'!=='	=> array(array('OPERATOR', '!='),	array('MISC', '=')),
			'!=!='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '!=')),
			'!=<='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '<=')),
			'!=>='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '>=')),
			'!=<>'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '<>')),
			'!=<'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '<')),
			'!=>'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '>')),
			'!=%'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '%')),
			'!=+'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '+')),
			'!=-'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '-')),
			'!=*'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '*')),
			'!=/'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '/')),
			'!=.'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '.')),
			'!=!'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '!')),
			'!=^'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '^')),
			'!=^='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '^=')),
			'!=*='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '*=')),
			'!=$='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '$=')),

			'^||'	=> array(array('OPERATOR', '^'),	array('OPERATOR', '||')),
			'^&&'	=> array(array('OPERATOR', '^'),	array('OPERATOR', '&&')),
			'^**'	=> array(array('OPERATOR', '^'),	array('OPERATOR', '**')),
			'^=='	=> array(array('OPERATOR', '^='),	array('MISC', '=')),
			'^=!='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '!=')),
			'^=<='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '<=')),
			'^=>='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '>=')),
			'^=<>'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '<>')),
			'^=<'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '<')),
			'^=>'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '>')),
			'^=%'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '%')),
			'^=+'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '+')),
			'^=-'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '-')),
			'^=*'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '*')),
			'^=/'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '/')),
			'^=.'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '.')),
			'^=!'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '!')),
			'^=^'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '^')),
			'^=^='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '^=')),
			'^=*='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '*=')),
			'^=$='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '$=')),

			'^=||'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '||')),
			'^=&&'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '&&')),
			'^=**'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '**')),
			'^==='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '==')),
			'^=!='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '!=')),
			'^=<='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '<=')),
			'^=>='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '>=')),
			'^=<>'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '<>')),
			'^=<'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '<')),
			'^=>'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '>')),
			'^=%'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '%')),
			'^=+'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '+')),
			'^=-'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '-')),
			'^=*'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '*')),
			'^=/'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '/')),
			'^=.'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '.')),
			'^=!'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '!')),
			'^=^'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '^')),
			'^=^='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '^=')),
			'^=*='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '*=')),
			'^=$='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '$=')),

			'*=||'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '||')),
			'*=&&'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '&&')),
			'*=**'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '**')),
			'*==='	=> array(array('OPERATOR', '*='),	array('OPERATOR', '==')),
			'*=!='	=> array(array('OPERATOR', '*='),	array('OPERATOR', '!=')),
			'*=<='	=> array(array('OPERATOR', '*='),	array('OPERATOR', '<=')),
			'*=>='	=> array(array('OPERATOR', '*='),	array('OPERATOR', '>=')),
			'*=<>'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '<>')),
			'*=<'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '<')),
			'*=>'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '>')),
			'*=%'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '%')),
			'*=+'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '+')),
			'*=-'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '-')),
			'*=*'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '*')),
			'*=/'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '/')),
			'*=.'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '.')),
			'*=!'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '!')),
			'*=^'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '^')),
			'*=^='	=> array(array('OPERATOR', '*='),	array('OPERATOR', '^=')),
			'*=*='	=> array(array('OPERATOR', '*='),	array('OPERATOR', '*=')),
			'*=$='	=> array(array('OPERATOR', '*='),	array('OPERATOR', '$=')),

			'$=||'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '||')),
			'$=&&'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '&&')),
			'$=**'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '**')),
			'$==='	=> array(array('OPERATOR', '$='),	array('OPERATOR', '==')),
			'$=!='	=> array(array('OPERATOR', '$='),	array('OPERATOR', '!=')),
			'$=<='	=> array(array('OPERATOR', '$='),	array('OPERATOR', '<=')),
			'$=>='	=> array(array('OPERATOR', '$='),	array('OPERATOR', '>=')),
			'$=<>'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '<>')),
			'$=<'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '<')),
			'$=>'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '>')),
			'$=%'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '%')),
			'$=+'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '+')),
			'$=-'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '-')),
			'$=*'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '*')),
			'$=/'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '/')),
			'$=.'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '.')),
			'$=!'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '!')),
			'$=^'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '^')),
			'$=^='	=> array(array('OPERATOR', '$='),	array('OPERATOR', '^=')),
			'$=*='	=> array(array('OPERATOR', '$='),	array('OPERATOR', '*=')),
			'$=$='	=> array(array('OPERATOR', '$='),	array('OPERATOR', '$='))
		);

		foreach ($operator_combinations as $operator => $tokens)
		{
			foreach ($this->valueTypes as $type => $value)
			{
				$expected = array(
					$value['token'],
					array('WHITESPACE',	' '),
					$tokens[0],
					$tokens[1],
					array('WHITESPACE',	' '),
					$value['token']
				);

				$return[] = array(
					"The \"{$operator}\" operator with {$type} values",
					$this->assembleCommonCondition($value['value']." ".$operator." ".$value['value']),
					$this->assembleCommonTokens($expected)
				);
			}
		}

		return $return;
	}

	// Things change without spaces around the operator
	protected function operatorCombinationsWithoutSpaces()
	{
		$return = array();

		$operator_combinations = array(
			'==='	=> array(array('OPERATOR', '=='),	array('MISC', '=')),

			/**
			 * The following array elements were generated with this code:
			 *
			 * $valid_operators = array(
			 * 	'||', '&&', '**',
			 * 	'==', '!=', '<=', '>=', '<>', '<', '>',
			 * 	'%', '+', '-', '*', '/',
			 * 	'.', '!', '^',
			 *	'^=', '*=', '$=',
			 * );
             *
			 * // Build out some combinations
			 * foreach ($valid_operators as $first)
			 * {
			 * 	foreach ($valid_operators as $second)
			 * 	{
			 * 		$operator = $first.$second;
             *
			 * 		if (in_array($operator, $valid_operators) ||
			 * 			isset($invalid_operators[$operator]))
			 * 		{
			 * 			continue;
			 * 		}
             *
			 * 		if ($operator == '--')
			 * 		{
			 * 			continue;
			 * 		}
             *
			 * 		if ($first == '.' || $second == '.')
			 * 		{
			 * 			continue;
			 * 		}
             *
			 * 		// Handle the case where first.second create a valid
			 * 		// operator in the first half: ! + == => !== => OP(!=) MISC(=)
			 * 		if (in_array($first.$second[0], $valid_operators))
			 * 		{
			 * 			$first = $first.$second[0];
			 * 			$second = substr($second, 1);
			 * 		}
             *
			 * 		$token = (in_array($second, $valid_operators)) ? 'OPERATOR' : 'MISC';
             *
			 * 		printf("'%s'\t=> array(array('OPERATOR', '%s'),\tarray('%s', '%s')),\n", $operator, $first, $token, $second);
			 * 	}
			 * 	print "\n";
			 * }
			 */

			'||||'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '||')),
			'||&&'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '&&')),
			'||**'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '**')),
			'||=='	=> array(array('OPERATOR', '||'),	array('OPERATOR', '==')),
			'||!='	=> array(array('OPERATOR', '||'),	array('OPERATOR', '!=')),
			'||<='	=> array(array('OPERATOR', '||'),	array('OPERATOR', '<=')),
			'||>='	=> array(array('OPERATOR', '||'),	array('OPERATOR', '>=')),
			'||<>'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '<>')),
			'||<'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '<')),
			'||>'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '>')),
			'||%'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '%')),
			'||+'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '+')),
			'||-'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '-')),
			'||*'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '*')),
			'||/'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '/')),
			'||!'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '!')),
			'||^'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '^')),
			'||^='	=> array(array('OPERATOR', '||'),	array('OPERATOR', '^=')),
			'||*='	=> array(array('OPERATOR', '||'),	array('OPERATOR', '*=')),
			'||$='	=> array(array('OPERATOR', '||'),	array('OPERATOR', '$=')),

			'&&||'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '||')),
			'&&&&'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '&&')),
			'&&**'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '**')),
			'&&=='	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '==')),
			'&&!='	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '!=')),
			'&&<='	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '<=')),
			'&&>='	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '>=')),
			'&&<>'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '<>')),
			'&&<'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '<')),
			'&&>'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '>')),
			'&&%'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '%')),
			'&&+'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '+')),
			'&&-'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '-')),
			'&&*'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '*')),
			'&&/'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '/')),
			'&&!'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '!')),
			'&&^'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '^')),
			'&&^='	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '^=')),
			'&&*='	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '*=')),
			'&&$='	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '$=')),

			'**||'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '||')),
			'**&&'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '&&')),
			'****'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '**')),
			'**=='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '==')),
			'**!='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '!=')),
			'**<='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '<=')),
			'**>='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '>=')),
			'**<>'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '<>')),
			'**<'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '<')),
			'**>'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '>')),
			'**%'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '%')),
			'**+'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '+')),
			'**-'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '-')),
			'***'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '*')),
			'**/'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '/')),
			'**!'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '!')),
			'**^'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '^')),
			'**^='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '^=')),
			'***='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '*=')),
			'**$='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '$=')),

			'==||'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '||')),
			'==&&'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '&&')),
			'==**'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '**')),
			'===='	=> array(array('OPERATOR', '=='),	array('OPERATOR', '==')),
			'==!='	=> array(array('OPERATOR', '=='),	array('OPERATOR', '!=')),
			'==<='	=> array(array('OPERATOR', '=='),	array('OPERATOR', '<=')),
			'==>='	=> array(array('OPERATOR', '=='),	array('OPERATOR', '>=')),
			'==<>'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '<>')),
			'==<'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '<')),
			'==>'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '>')),
			'==%'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '%')),
			'==+'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '+')),
			'==-'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '-')),
			'==*'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '*')),
			'==/'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '/')),
			'==!'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '!')),
			'==^'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '^')),
			'==^='	=> array(array('OPERATOR', '=='),	array('OPERATOR', '^=')),
			'==*='	=> array(array('OPERATOR', '=='),	array('OPERATOR', '*=')),
			'==$='	=> array(array('OPERATOR', '=='),	array('OPERATOR', '$=')),

			'!=||'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '||')),
			'!=&&'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '&&')),
			'!=**'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '**')),
			'!==='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '==')),
			'!=!='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '!=')),
			'!=<='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '<=')),
			'!=>='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '>=')),
			'!=<>'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '<>')),
			'!=<'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '<')),
			'!=>'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '>')),
			'!=%'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '%')),
			'!=+'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '+')),
			'!=-'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '-')),
			'!=*'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '*')),
			'!=/'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '/')),
			'!=!'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '!')),
			'!=^'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '^')),
			'!=^='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '^=')),
			'!=*='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '*=')),
			'!=$='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '$=')),

			'<=||'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '||')),
			'<=&&'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '&&')),
			'<=**'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '**')),
			'<==='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '==')),
			'<=!='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '!=')),
			'<=<='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '<=')),
			'<=>='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '>=')),
			'<=<>'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '<>')),
			'<=<'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '<')),
			'<=>'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '>')),
			'<=%'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '%')),
			'<=+'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '+')),
			'<=-'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '-')),
			'<=*'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '*')),
			'<=/'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '/')),
			'<=!'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '!')),
			'<=^'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '^')),
			'<=^='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '^=')),
			'<=*='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '*=')),
			'<=$='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '$=')),

			'>=||'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '||')),
			'>=&&'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '&&')),
			'>=**'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '**')),
			'>==='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '==')),
			'>=!='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '!=')),
			'>=<='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '<=')),
			'>=>='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '>=')),
			'>=<>'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '<>')),
			'>=<'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '<')),
			'>=>'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '>')),
			'>=%'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '%')),
			'>=+'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '+')),
			'>=-'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '-')),
			'>=*'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '*')),
			'>=/'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '/')),
			'>=!'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '!')),
			'>=^'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '^')),
			'>=^='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '^=')),
			'>=*='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '*=')),
			'>=$='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '$=')),

			'<>||'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '||')),
			'<>&&'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '&&')),
			'<>**'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '**')),
			'<>=='	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '==')),
			'<>!='	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '!=')),
			'<><='	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '<=')),
			'<>>='	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '>=')),
			'<><>'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '<>')),
			'<><'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '<')),
			'<>>'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '>')),
			'<>%'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '%')),
			'<>+'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '+')),
			'<>-'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '-')),
			'<>*'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '*')),
			'<>/'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '/')),
			'<>!'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '!')),
			'<>^'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '^')),
			'<>^='	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '^=')),
			'<>*='	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '*=')),
			'<>$='	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '$=')),

			'<||'	=> array(array('OPERATOR', '<'),	array('OPERATOR', '||')),
			'<&&'	=> array(array('OPERATOR', '<'),	array('OPERATOR', '&&')),
			'<**'	=> array(array('OPERATOR', '<'),	array('OPERATOR', '**')),
			'<=='	=> array(array('OPERATOR', '<='),	array('MISC', '=')),
			'<=!='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '!=')),
			'<=<='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '<=')),
			'<=>='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '>=')),
			'<=<>'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '<>')),
			'<=<'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '<')),
			'<=>'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '>')),
			'<=%'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '%')),
			'<=+'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '+')),
			'<=-'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '-')),
			'<=*'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '*')),
			'<=/'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '/')),
			'<=!'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '!')),
			'<=^'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '^')),
			'<=^='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '^=')),
			'<=*='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '*=')),
			'<=$='	=> array(array('OPERATOR', '<='),	array('OPERATOR', '$=')),

			'>||'	=> array(array('OPERATOR', '>'),	array('OPERATOR', '||')),
			'>&&'	=> array(array('OPERATOR', '>'),	array('OPERATOR', '&&')),
			'>**'	=> array(array('OPERATOR', '>'),	array('OPERATOR', '**')),
			'>=='	=> array(array('OPERATOR', '>='),	array('MISC', '=')),
			'>=!='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '!=')),
			'>=<='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '<=')),
			'>=>='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '>=')),
			'>=<>'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '<>')),
			'>=<'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '<')),
			'>=>'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '>')),
			'>=%'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '%')),
			'>=+'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '+')),
			'>=-'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '-')),
			'>=*'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '*')),
			'>=/'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '/')),
			'>=!'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '!')),
			'>=^'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '^')),
			'>=^='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '^=')),
			'>=*='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '*=')),
			'>=$='	=> array(array('OPERATOR', '>='),	array('OPERATOR', '$=')),

			'%||'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '||')),
			'%&&'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '&&')),
			'%**'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '**')),
			'%=='	=> array(array('OPERATOR', '%'),	array('OPERATOR', '==')),
			'%!='	=> array(array('OPERATOR', '%'),	array('OPERATOR', '!=')),
			'%<='	=> array(array('OPERATOR', '%'),	array('OPERATOR', '<=')),
			'%>='	=> array(array('OPERATOR', '%'),	array('OPERATOR', '>=')),
			'%<>'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '<>')),
			'%<'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '<')),
			'%>'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '>')),
			'%%'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '%')),
			'%+'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '+')),
			'%-'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '-')),
			'%*'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '*')),
			'%/'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '/')),
			'%!'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '!')),
			'%^'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '^')),
			'%^='	=> array(array('OPERATOR', '%'),	array('OPERATOR', '^=')),
			'%*='	=> array(array('OPERATOR', '%'),	array('OPERATOR', '*=')),
			'%$='	=> array(array('OPERATOR', '%'),	array('OPERATOR', '$=')),

			'+||'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '||')),
			'+&&'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '&&')),
			'+**'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '**')),
			'+=='	=> array(array('OPERATOR', '+'),	array('OPERATOR', '==')),
			'+!='	=> array(array('OPERATOR', '+'),	array('OPERATOR', '!=')),
			'+<='	=> array(array('OPERATOR', '+'),	array('OPERATOR', '<=')),
			'+>='	=> array(array('OPERATOR', '+'),	array('OPERATOR', '>=')),
			'+<>'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '<>')),
			'+<'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '<')),
			'+>'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '>')),
			'+%'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '%')),
			'++'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '+')),
			'+-'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '-')),
			'+*'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '*')),
			'+/'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '/')),
			'+!'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '!')),
			'+^'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '^')),
			'+^='	=> array(array('OPERATOR', '+'),	array('OPERATOR', '^=')),
			'+*='	=> array(array('OPERATOR', '+'),	array('OPERATOR', '*=')),
			'+$='	=> array(array('OPERATOR', '+'),	array('OPERATOR', '$=')),

			'-||'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '||')),
			'-&&'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '&&')),
			'-**'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '**')),
			'-=='	=> array(array('OPERATOR', '-'),	array('OPERATOR', '==')),
			'-!='	=> array(array('OPERATOR', '-'),	array('OPERATOR', '!=')),
			'-<='	=> array(array('OPERATOR', '-'),	array('OPERATOR', '<=')),
			'->='	=> array(array('OPERATOR', '-'),	array('OPERATOR', '>=')),
			'-<>'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '<>')),
			'-<'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '<')),
			'->'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '>')),
			'-%'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '%')),
			'-+'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '+')),
			'-*'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '*')),
			'-/'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '/')),
			'-!'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '!')),
			'-^'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '^')),
			'-^='	=> array(array('OPERATOR', '-'),	array('OPERATOR', '^=')),
			'-*='	=> array(array('OPERATOR', '-'),	array('OPERATOR', '*=')),
			'-$='	=> array(array('OPERATOR', '-'),	array('OPERATOR', '$=')),

			'*||'	=> array(array('OPERATOR', '*'),	array('OPERATOR', '||')),
			'*&&'	=> array(array('OPERATOR', '*'),	array('OPERATOR', '&&')),
			'***'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '*')),
			'**=='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '==')),
			'**!='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '!=')),
			'**<='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '<=')),
			'**>='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '>=')),
			'**<>'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '<>')),
			'**<'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '<')),
			'**>'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '>')),
			'**%'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '%')),
			'**+'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '+')),
			'**-'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '-')),
			'***'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '*')),
			'**/'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '/')),
			'**!'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '!')),
			'**^'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '^')),
			'**^='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '^=')),
			'***='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '*=')),
			'**$='	=> array(array('OPERATOR', '**'),	array('OPERATOR', '$=')),

			'/||'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '||')),
			'/&&'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '&&')),
			'/**'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '**')),
			'/=='	=> array(array('OPERATOR', '/'),	array('OPERATOR', '==')),
			'/!='	=> array(array('OPERATOR', '/'),	array('OPERATOR', '!=')),
			'/<='	=> array(array('OPERATOR', '/'),	array('OPERATOR', '<=')),
			'/>='	=> array(array('OPERATOR', '/'),	array('OPERATOR', '>=')),
			'/<>'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '<>')),
			'/<'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '<')),
			'/>'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '>')),
			'/%'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '%')),
			'/+'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '+')),
			'/-'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '-')),
			'/*'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '*')),
			'//'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '/')),
			'/!'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '!')),
			'/^'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '^')),
			'/^='	=> array(array('OPERATOR', '/'),	array('OPERATOR', '^=')),
			'/*='	=> array(array('OPERATOR', '/'),	array('OPERATOR', '*=')),
			'/$='	=> array(array('OPERATOR', '/'),	array('OPERATOR', '$=')),


			'!||'	=> array(array('OPERATOR', '!'),	array('OPERATOR', '||')),
			'!&&'	=> array(array('OPERATOR', '!'),	array('OPERATOR', '&&')),
			'!**'	=> array(array('OPERATOR', '!'),	array('OPERATOR', '**')),
			'!=='	=> array(array('OPERATOR', '!='),	array('MISC', '=')),
			'!=!='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '!=')),
			'!=<='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '<=')),
			'!=>='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '>=')),
			'!=<>'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '<>')),
			'!=<'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '<')),
			'!=>'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '>')),
			'!=%'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '%')),
			'!=+'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '+')),
			'!=-'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '-')),
			'!=*'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '*')),
			'!=/'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '/')),
			'!=!'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '!')),
			'!=^'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '^')),
			'!=^='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '^=')),
			'!=*='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '*=')),
			'!=$='	=> array(array('OPERATOR', '!='),	array('OPERATOR', '$=')),

			'^||'	=> array(array('OPERATOR', '^'),	array('OPERATOR', '||')),
			'^&&'	=> array(array('OPERATOR', '^'),	array('OPERATOR', '&&')),
			'^**'	=> array(array('OPERATOR', '^'),	array('OPERATOR', '**')),
			'^=='	=> array(array('OPERATOR', '^='),	array('MISC', '=')),
			'^=!='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '!=')),
			'^=<='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '<=')),
			'^=>='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '>=')),
			'^=<>'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '<>')),
			'^=<'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '<')),
			'^=>'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '>')),
			'^=%'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '%')),
			'^=+'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '+')),
			'^=-'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '-')),
			'^=*'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '*')),
			'^=/'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '/')),
			'^=!'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '!')),
			'^=^'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '^')),
			'^=^='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '^=')),
			'^=*='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '*=')),
			'^=$='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '$=')),

			'^=||'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '||')),
			'^=&&'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '&&')),
			'^=**'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '**')),
			'^==='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '==')),
			'^=!='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '!=')),
			'^=<='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '<=')),
			'^=>='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '>=')),
			'^=<>'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '<>')),
			'^=<'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '<')),
			'^=>'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '>')),
			'^=%'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '%')),
			'^=+'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '+')),
			'^=-'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '-')),
			'^=*'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '*')),
			'^=/'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '/')),
			'^=!'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '!')),
			'^=^'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '^')),
			'^=^='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '^=')),
			'^=*='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '*=')),
			'^=$='	=> array(array('OPERATOR', '^='),	array('OPERATOR', '$=')),

			'*=||'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '||')),
			'*=&&'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '&&')),
			'*=**'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '**')),
			'*==='	=> array(array('OPERATOR', '*='),	array('OPERATOR', '==')),
			'*=!='	=> array(array('OPERATOR', '*='),	array('OPERATOR', '!=')),
			'*=<='	=> array(array('OPERATOR', '*='),	array('OPERATOR', '<=')),
			'*=>='	=> array(array('OPERATOR', '*='),	array('OPERATOR', '>=')),
			'*=<>'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '<>')),
			'*=<'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '<')),
			'*=>'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '>')),
			'*=%'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '%')),
			'*=+'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '+')),
			'*=-'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '-')),
			'*=*'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '*')),
			'*=/'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '/')),
			'*=!'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '!')),
			'*=^'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '^')),
			'*=^='	=> array(array('OPERATOR', '*='),	array('OPERATOR', '^=')),
			'*=*='	=> array(array('OPERATOR', '*='),	array('OPERATOR', '*=')),
			'*=$='	=> array(array('OPERATOR', '*='),	array('OPERATOR', '$=')),

			'$=||'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '||')),
			'$=&&'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '&&')),
			'$=**'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '**')),
			'$==='	=> array(array('OPERATOR', '$='),	array('OPERATOR', '==')),
			'$=!='	=> array(array('OPERATOR', '$='),	array('OPERATOR', '!=')),
			'$=<='	=> array(array('OPERATOR', '$='),	array('OPERATOR', '<=')),
			'$=>='	=> array(array('OPERATOR', '$='),	array('OPERATOR', '>=')),
			'$=<>'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '<>')),
			'$=<'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '<')),
			'$=>'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '>')),
			'$=%'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '%')),
			'$=+'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '+')),
			'$=-'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '-')),
			'$=*'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '*')),
			'$=/'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '/')),
			'$=!'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '!')),
			'$=^'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '^')),
			'$=^='	=> array(array('OPERATOR', '$='),	array('OPERATOR', '^=')),
			'$=*='	=> array(array('OPERATOR', '$='),	array('OPERATOR', '*=')),
			'$=$='	=> array(array('OPERATOR', '$='),	array('OPERATOR', '$='))
		);

		foreach ($operator_combinations as $operator => $tokens)
		{
			foreach ($this->valueTypes as $type => $value)
			{
				$expected = array(
					$value['token'],
					$tokens[0],
					$tokens[1],
					$value['token']
				);

				$return[] = array(
					"The \"{$operator}\" operator with {$type} values (no spaces)",
					$this->assembleCommonCondition($value['value'].$operator.$value['value']),
					$this->assembleCommonTokens($expected)
				);
			}
		}


		$operator_combinations = array(
			/**
			 * The following array elements were generated with this code:
			 *
			 * $valid_operators = array(
			 * 	'||', '&&', '**',
			 * 	'==', '!=', '<=', '>=', '<>', '<', '>',
			 * 	'%', '+', '-', '*', '/',
			 *	'.', '!', '^',
			 *	'^=', '*=', '$=',
			 * );
             *
			 * // Build out some combinations
			 * foreach ($valid_operators as $first)
			 * {
			 * 	foreach ($valid_operators as $second)
			 * 	{
			 * 		$operator = $first.$second;
             *
			 * 		if (in_array($operator, $valid_operators) ||
			 * 			isset($invalid_operators[$operator]))
			 * 		{
			 * 			continue;
			 * 		}
             *
			 * 		if ($operator == '--')
			 * 		{
			 * 			continue;
			 * 		}
             *
			 * 		if ($first == '.' || $second == '.')
			 * 		{
			 * 			$token = (in_array($second, $valid_operators)) ? 'OPERATOR' : 'MISC';
			 *
			 * 			printf("'%s'\t=> array(array('OPERATOR', '%s'),\tarray('%s', '%s')),\n", $operator, $first, $token, $second);
			 * 		}
			 * 	}
			 * 	print "\n";
			 * }
			 */

			'||.'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '.')),

			'&&.'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '.')),

			'**.'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '.')),

			'==.'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '.')),

			'!=.'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '.')),

			'<=.'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '.')),

			'>=.'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '.')),

			'<>.'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '.')),

			'<.'	=> array(array('OPERATOR', '<'),	array('OPERATOR', '.')),

			'>.'	=> array(array('OPERATOR', '>'),	array('OPERATOR', '.')),

			'%.'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '.')),

			'+.'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '.')),

			'-.'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '.')),

			'*.'	=> array(array('OPERATOR', '*'),	array('OPERATOR', '.')),

			'/.'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '.')),

			'.||'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '||')),
			'.&&'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '&&')),
			'.**'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '**')),
			'.=='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '==')),
			'.!='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '!=')),
			'.<='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '<=')),
			'.>='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '>=')),
			'.<>'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '<>')),
			'.<'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '<')),
			'.>'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '>')),
			'.%'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '%')),
			'.+'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '+')),
			'.-'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '-')),
			'.*'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '*')),
			'./'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '/')),
			'.!'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '!')),
			'.^'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '^')),
			'.^='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '^=')),
			'.*='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '*=')),
			'.$='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '$=')),

			'!.'	=> array(array('OPERATOR', '!'),	array('OPERATOR', '.')),

			'^.'	=> array(array('OPERATOR', '^'),	array('OPERATOR', '.')),

			'^=.'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '.')),

			'*=.'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '.')),

			'$=.'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '.'))
		);

		// When a period or dash end an operator and the next character is
		// a digit then it's not an operator any longer
		foreach ($operator_combinations as $operator => $tokens)
		{
			foreach ($this->valueTypes as $type => $value)
			{
				// To avoid confusing code these will be done "by hand" see: edgyOperatorCombinationsWithoutSpaces()
				if ($type == 'negative' || $value['token'][0] == 'NUMBER')
				{
					continue;
				}

				$expected = array(
					$value['token'],
					$tokens[0],
					$tokens[1],
					$value['token']
				);

				$return[] = array(
					"The \"{$operator}\" operator with {$type} values (no spaces)",
					$this->assembleCommonCondition($value['value'].$operator.$value['value']),
					$this->assembleCommonTokens($expected)
				);
			}
		}

		return $return;
	}

	protected function edgyOperatorCombinationsWithoutSpaces()
	{
		$return = array();

		$right_hand_dot_combinations = array(
			'||.'	=> array(array('OPERATOR', '||'),	array('OPERATOR', '.')),
			'&&.'	=> array(array('OPERATOR', '&&'),	array('OPERATOR', '.')),
			'**.'	=> array(array('OPERATOR', '**'),	array('OPERATOR', '.')),
			'==.'	=> array(array('OPERATOR', '=='),	array('OPERATOR', '.')),
			'!=.'	=> array(array('OPERATOR', '!='),	array('OPERATOR', '.')),
			'<=.'	=> array(array('OPERATOR', '<='),	array('OPERATOR', '.')),
			'>=.'	=> array(array('OPERATOR', '>='),	array('OPERATOR', '.')),
			'<>.'	=> array(array('OPERATOR', '<>'),	array('OPERATOR', '.')),
			'<.'	=> array(array('OPERATOR', '<'),	array('OPERATOR', '.')),
			'>.'	=> array(array('OPERATOR', '>'),	array('OPERATOR', '.')),
			'%.'	=> array(array('OPERATOR', '%'),	array('OPERATOR', '.')),
			'+.'	=> array(array('OPERATOR', '+'),	array('OPERATOR', '.')),
			'-.'	=> array(array('OPERATOR', '-'),	array('OPERATOR', '.')),
			'*.'	=> array(array('OPERATOR', '*'),	array('OPERATOR', '.')),
			'/.'	=> array(array('OPERATOR', '/'),	array('OPERATOR', '.')),
			'!.'	=> array(array('OPERATOR', '!'),	array('OPERATOR', '.')),
			'^.'	=> array(array('OPERATOR', '^'),	array('OPERATOR', '.')),
			'^=.'	=> array(array('OPERATOR', '^='),	array('OPERATOR', '.')),
			'*=.'	=> array(array('OPERATOR', '*='),	array('OPERATOR', '.')),
			'$=.'	=> array(array('OPERATOR', '$='),	array('OPERATOR', '.'))
		);

		$left_hand_dot_combinations = array(
			'.||'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '||')),
			'.&&'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '&&')),
			'.**'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '**')),
			'.=='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '==')),
			'.!='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '!=')),
			'.<='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '<=')),
			'.>='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '>=')),
			'.<>'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '<>')),
			'.<'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '<')),
			'.>'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '>')),
			'.%'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '%')),
			'.+'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '+')),
			'.-'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '-')),
			'.*'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '*')),
			'./'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '/')),
			'.!'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '!')),
			'.^'	=> array(array('OPERATOR', '.'),	array('OPERATOR', '^')),
			'.^='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '^=')),
			'.*='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '*=')),
			'.$='	=> array(array('OPERATOR', '.'),	array('OPERATOR', '$=')),
		);

		// int: '.' in NUMBER token
		$type = 'int';
		$value = $this->valueTypes[$type];

		// The '.' is moved into the right NUMBER token
		foreach ($right_hand_dot_combinations as $operator => $tokens)
		{
			$expected = array(
				$value['token'],
				$tokens[0],
				array($value['token'][0], '.'.$value['token'][1])
			);

			$return[] = array(
				"The \"{$operator}\" operator with {$type} values (no spaces)",
				$this->assembleCommonCondition($value['value'].$operator.$value['value']),
				$this->assembleCommonTokens($expected)
			);
		}

		// The '.' is moved into the left NUMBER token
		foreach ($left_hand_dot_combinations as $operator => $tokens)
		{
			$expected = array(
				array($value['token'][0], $value['token'][1].'.'),
				$tokens[1],
				$value['token']
			);

			$return[] = array(
				"The \"{$operator}\" operator with {$type} values (no spaces)",
				$this->assembleCommonCondition($value['value'].$operator.$value['value']),
				$this->assembleCommonTokens($expected)
			);
		}

		// negative: NUMBER in right-hand, OPERATOR in left-hand
		$type = 'negative';
		$value = $this->valueTypes[$type];

		// The '.' is an OPERATOR token
		foreach ($right_hand_dot_combinations as $operator => $tokens)
		{
			$expected = array(
				$value['token'],
				$tokens[0],
				$tokens[1],
				$value['token']
			);

			$return[] = array(
				"The \"{$operator}\" operator with {$type} values (no spaces)",
				$this->assembleCommonCondition($value['value'].$operator.$value['value']),
				$this->assembleCommonTokens($expected)
			);
		}

		// The '.' is moved into the left NUMBER token
		foreach ($left_hand_dot_combinations as $operator => $tokens)
		{
			$expected = array(
				$value['token'][0],
				array($value['token'][1][0], $value['token'][1][1].'.'),
				$tokens[1],
				$value['token']
			);

			$return[] = array(
				"The \"{$operator}\" operator with {$type} values (no spaces)",
				$this->assembleCommonCondition($value['value'].$operator.$value['value']),
				$this->assembleCommonTokens($expected)
			);
		}

		// bigfloat
		$type = 'bigfloat';
		$value = $this->valueTypes[$type];

		foreach ($right_hand_dot_combinations as $operator => $tokens)
		{
			$expected = array(
				$value['token'],
				$tokens[0],
				array('NUMBER', '.5'),
				array('NUMBER', '.1')
			);

			$return[] = array(
				"The \"{$operator}\" operator with {$type} values (no spaces)",
				$this->assembleCommonCondition($value['value'].$operator.$value['value']),
				$this->assembleCommonTokens($expected)
			);
		}

		foreach ($left_hand_dot_combinations as $operator => $tokens)
		{
			$expected = array(
				$value['token'],
				$tokens[0],
				$tokens[1],
				$value['token']
			);

			$return[] = array(
				"The \"{$operator}\" operator with {$type} values (no spaces)",
				$this->assembleCommonCondition($value['value'].$operator.$value['value']),
				$this->assembleCommonTokens($expected)
			);
		}

		// littlefloat
		$type = 'littlefloat';
		$value = $this->valueTypes[$type];

		foreach ($right_hand_dot_combinations as $operator => $tokens)
		{
			$expected = array(
				$value['token'],
				$tokens[0],
				$tokens[1],
				$value['token']
			);

			$return[] = array(
				"The \"{$operator}\" operator with {$type} values (no spaces)",
				$this->assembleCommonCondition($value['value'].$operator.$value['value']),
				$this->assembleCommonTokens($expected)
			);
		}

		foreach ($left_hand_dot_combinations as $operator => $tokens)
		{
			$expected = array(
				$value['token'],
				$tokens[0],
				$tokens[1],
				$value['token']
			);

			$return[] = array(
				"The \"{$operator}\" operator with {$type} values (no spaces)",
				$this->assembleCommonCondition($value['value'].$operator.$value['value']),
				$this->assembleCommonTokens($expected)
			);
		}

		return $return;
	}

	/**
	 * Tests when encountering "--" in a conditional without whitespace
	 * surrounding it.
	 *
	 * Note: the number cases are covered in edgyOperatorCombinationsWithoutSpaces()
	 */
	protected function edgyDoubleDashWithoutSpaces()
	{
		$return = array();

		$operator = '--';

		// These become variables
		foreach(array('bool', 'variable', 'dash-variable') as $type)
		{
			$value = $this->valueTypes[$type];
			$token = $value['token'];
			$expected = array(
				array('VARIABLE', $token[1].$operator.$token[1]),
			);

			$return[] = array(
				"The \"{$operator}\" operator with {$type} values (no spaces)",
				$this->assembleCommonCondition($value['value'].$operator.$value['value']),
				$this->assembleCommonTokens($expected)
			);
		}

		// These see '--' as two operators
		foreach(array('string', 'dash-string', 'dot.string', 'intstring', 'simpletag', 'moduletag', 'tag_with_params') as $type)
		{
			$value = $this->valueTypes[$type];
			$expected = array(
				$value['token'],
				array('OPERATOR', '-'),
				array('OPERATOR', '-'),
				$value['token']
			);

			$return[] = array(
				"The \"{$operator}\" operator with {$type} values (no spaces)",
				$this->assembleCommonCondition($value['value'].$operator.$value['value']),
				$this->assembleCommonTokens($expected)
			);
		}

		return $return;
	}

	/**
	 * Tests when encountering ".-" in a conditional surrounded by numbers
	 */
	protected function edgyDotDashWithNumbersAndNoSpaces()
	{
		$return = array();

		// 5.-5
		$expected = array(
			array('NUMBER',		'5.'),
			array('OPERATOR',	'-'),
			array('NUMBER',		'5'),
		);

		$return[] = array(
			"The \".-\" operator with int values",
			$this->assembleCommonCondition("5.-5"),
			$this->assembleCommonTokens($expected)
		);

		// -5.--5
		$expected = array(
			array('OPERATOR',	'-'),
			array('NUMBER',		'5.'),
			array('OPERATOR',	'-'),
			array('OPERATOR',	'-'),
			array('NUMBER',		'5'),
		);

		$return[] = array(
			"The \".-\" operator with negative int values",
			$this->assembleCommonCondition("-5.--5"),
			$this->assembleCommonTokens($expected)
		);

		// 5.1.-5.1
		$expected = array(
			array('NUMBER',		'5.1'),
			array('OPERATOR',	'.'),
			array('OPERATOR',	'-'),
			array('NUMBER',		'5.1'),
		);

		$return[] = array(
			"The \".-\" operator with bigfloat values",
			$this->assembleCommonCondition("5.1.-5.1"),
			$this->assembleCommonTokens($expected)
		);

		// -5.1.--5.1
		$expected = array(
			array('OPERATOR',	'-'),
			array('NUMBER',		'5.1'),
			array('OPERATOR',	'.'),
			array('OPERATOR',	'-'),
			array('OPERATOR',	'-'),
			array('NUMBER',		'5.1'),
		);

		$return[] = array(
			"The \".-\" operator with negative bigfloat values",
			$this->assembleCommonCondition("-5.1.--5.1"),
			$this->assembleCommonTokens($expected)
		);

		// .1.-.1
		$expected = array(
			array('NUMBER',		'.1'),
			array('OPERATOR',	'.'),
			array('OPERATOR',	'-'),
			array('NUMBER',		'.1'),
		);

		$return[] = array(
			"The \".-\" operator with int values",
			$this->assembleCommonCondition(".1.-.1"),
			$this->assembleCommonTokens($expected)
		);

		return $return;
	}

	/**
	 * Tests when encountering ".." in a conditional surrounded by numbers
	 */
	protected function edgyDoubleDotWithNumbersAndNoSpaces()
	{
		$return = array();

		// 5..5
		$expected = array(
			array('NUMBER',		'5.'),
			array('NUMBER',		'.5'),
		);

		$return[] = array(
			"The \"..\" operator with int values",
			$this->assembleCommonCondition("5..5"),
			$this->assembleCommonTokens($expected)
		);

		// -5..-5
		$expected = array(
			array('OPERATOR',	'-'),
			array('NUMBER',		'5.'),
			array('OPERATOR',	'.'),
			array('OPERATOR',	'-'),
			array('NUMBER',		'5'),
		);

		$return[] = array(
			"The \"..\" operator with negative int values",
			$this->assembleCommonCondition("-5..-5"),
			$this->assembleCommonTokens($expected)
		);

		// 5.1..5.1
		$expected = array(
			array('NUMBER',		'5.1'),
			array('OPERATOR',	'.'),
			array('NUMBER',		'.5'),
			array('NUMBER',		'.1'),
		);

		$return[] = array(
			"The \"..\" operator with bigfloat values",
			$this->assembleCommonCondition("5.1..5.1"),
			$this->assembleCommonTokens($expected)
		);

		// -5.1..-5.1
		$expected = array(
			array('OPERATOR',	'-'),
			array('NUMBER',		'5.1'),
			array('OPERATOR',	'.'),
			array('OPERATOR',	'.'),
			array('OPERATOR',	'-'),
			array('NUMBER',		'5.1'),
		);

		$return[] = array(
			"The \"..\" operator with negative bigfloat values",
			$this->assembleCommonCondition("-5.1..-5.1"),
			$this->assembleCommonTokens($expected)
		);

		// .1...1
		$expected = array(
			array('NUMBER',		'.1'),
			array('OPERATOR',	'.'),
			array('OPERATOR',	'.'),
			array('NUMBER',		'.1'),
		);

		$return[] = array(
			"The \"..\" operator with int values",
			$this->assembleCommonCondition(".1...1"),
			$this->assembleCommonTokens($expected)
		);

		return $return;
	}

	protected function validNumberTokens()
	{
		$return = array();

		$numbers = array(
			'0', '1', '10', '100',
			'0.', '1.', '10.', '100.',
			'.0', '.1', '.01', '.001',
			'0.1', '1.1', '10.01', '100.001'
		);

		foreach ($numbers as $number)
		{
			// Positive
			$expected = array(
				array('NUMBER', $number)
			);

			$return[] = array(
				"\"{$number}\" is a NUMBER token",
				$this->assembleCommonCondition($number),
				$this->assembleCommonTokens($expected)
			);

			// Negative
			$expected = array(
				array('OPERATOR', '-'),
				array('NUMBER', $number)
			);

			$return[] = array(
				"\"{$number}\" is a NUMBER token",
				$this->assembleCommonCondition('-'.$number),
				$this->assembleCommonTokens($expected)
			);

		}

		return $return;
	}

	protected function validVariableTokens()
	{
		$return = array();

		$variables = array(
			'var', 'var-dash', 'var-two-dashes',
			'var--double', 'var---tripple',
			'var--double-plus', 'var---tripple--plus', 'var---tripple--plus-plus',
			'var_underscore', '_underscore_var', 'var_', 'var_underscore-dash',
			'var_-_rav', 's-__-s', 'TRUE-var', 'var-TRUE'
		);

		foreach ($variables as $variable)
		{
			$expected = array(
				array('VARIABLE', $variable)
			);

			$return[] = array(
				"\"{$variable}\" is a VARIABLE token",
				$this->assembleCommonCondition($variable),
				$this->assembleCommonTokens($expected)
			);
		}

		return $return;
	}

	/**
	 * The idea is to ensure that each token type is tested.
	 */
	protected function basicTokens()
	{
		$return = array();

		/**
		 * Available tokens:
		 *
		 * private $token_names = array(
		 * 	'TEMPLATE_STRING',	// generic
		 *  'LD'				// {
		 *  'RD'				// }
		 * 	'IF',				// if
		 * 	'ELSE',				// if:else
		 * 	'ELSEIF',			// if:elseif
		 * 	'ENDIF',			// /if
		 * 	'STRING',			// literal string "foo", or 'foo'. The value does not include quotes
		 * 	'NUMBER',			// literal number
		 * 	'VARIABLE',
		 * 	'OPERATOR',			// an operator from the $operators array
		 * 	'MISC',				// other stuff, usually illegal when safety on
		 * 	'LP',				// (
		 * 	'RP',				// )
		 * 	'WHITESPACE',		// \s\r\n\t
		 * 	'BOOL',				// TRUE or FALSE (case insensitive)
		 * 	'TAG',				// {exp:foo:bar}
		 * 	'EOS'				// end of string
		 * );
		 */

		// $this->commonTokens covers LD, IF, WHITESPACE, RD, TEMPLATE_STRING, LD, ENDIF, RD, and EOS.

		// ELSE
		// ELSIF
		$expected = array(
			array('LD', 				'{'),
			array('IF', 				'if'),
			array('WHITESPACE', 		' '),
			array('BOOL',				'TRUE'),
			array('RD',					'}'),
			array('TEMPLATE_STRING',	'out'),
			array('LD', 				'{'),
			array('ELSEIF', 			'if:elseif'),
			array('WHITESPACE', 		' '),
			array('BOOL',				'TRUE'),
			array('RD',					'}'),
			array('TEMPLATE_STRING',	'out'),
			array('LD', 				'{'),
			array('ELSE', 				'if:else'),
			array('RD',					'}'),
			array('TEMPLATE_STRING',	'out'),
			array('LD',					'{'),
			array('ENDIF',				'/if'),
			array('RD',					'}'),
			array('EOS',				TRUE)
		);
		$return[] = array(
			"ELSEIF & ELSE tokens",
			"{if TRUE}out{if:elseif TRUE}out{if:else}out{/if}",
			$expected
		);

		// STRING
		$return[] = array(
			"STRING tokens",
			$this->assembleCommonCondition('"foo"'),
			$this->assembleCommonTokens(array(array('STRING', 'foo')))
		);

		// NUMBER
		$return[] = array(
			"NUMBER tokens",
			$this->assembleCommonCondition('5'),
			$this->assembleCommonTokens(array(array('NUMBER', '5')))
		);

		// VARIABLE
		$return[] = array(
			"VARIABLE tokens",
			$this->assembleCommonCondition('foo'),
			$this->assembleCommonTokens(array(array('VARIABLE', 'foo')))
		);

		// OPERATOR
		$return[] = array(
			"OPERATOR tokens",
			$this->assembleCommonCondition('=='),
			$this->assembleCommonTokens(array(array('OPERATOR', '==')))
		);

		// MISC
		$return[] = array(
			"MISC tokens",
			$this->assembleCommonCondition('@'),
			$this->assembleCommonTokens(array(array('MISC', '@')))
		);

		// LP
		// RP
		$return[] = array(
			"LP & RP tokens",
			$this->assembleCommonCondition('()'),
			$this->assembleCommonTokens(array(array('LP', '('), array('RP', ')')))
		);

		// BOOL
		$return[] = array(
			"BOOL tokens",
			$this->assembleCommonCondition('TRUE'),
			$this->assembleCommonTokens(array(array('BOOL', 'TRUE')))
		);

		// TAG
		$return[] = array(
			"TAG tokens",
			$this->assembleCommonCondition('{exp:foo:bar}'),
			$this->assembleCommonTokens(array(array('TAG', '{exp:foo:bar}')))
		);

		return $return;
	}
}