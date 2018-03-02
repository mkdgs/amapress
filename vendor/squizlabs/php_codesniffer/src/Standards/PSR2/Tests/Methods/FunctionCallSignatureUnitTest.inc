<?php

// ok
somefunction1( $foo, $bar, [
	// ...
], $baz );

// ok
$app->get( '/hello/{name}', function ( $name ) use ( $app ) {
	return 'Hello ' . $app->escape( $name );
}, array(
	'1',
	'2',
	'3',
) );

// error
somefunction2( $foo, $bar, [
	// ...
],
	$baz );

// ok
somefunction3(// ...
	$foo,
	$bar,
	[
		// ...
	],
	$baz
);

// ok
somefunction4( '
    this should not
    give an error
    because it\'s actually
    one line call
    with multi-line string
' );

// ok
somefunction5( "hey,
multi-line string with some
extra args", $foo, 12 );

// error
somefunction6( '
    but args in a new line
    are not ok…
    ',
	$foo
);

$this->setFoo( true
	? 1
	: 2, false, array(
	'value',
	'more'
) );

$this->setFoo( 'some'
               . 'long'
               . 'text', 'string' );

foo( bar(), $a );
foo();
bar();

foo(
	true
);

myFunction( <<<END
Foo
END
);

var_dump( array(
	<<<'EOD'
foobar!
EOD
) );

myFunction( <<<END
Foo
END
	, 'bar' );

myFunction( <<<END
Foo
END
	,
	'bar' );

if ( array_filter(
	$commands,
	function ( $cmd ) use ( $commandName ) {
		return ( $cmd['name'] == $commandName );
	}
) ) {
	// Do something
}

myFunction(
	'foo', (object) array(
	'bar' => function ( $x ) {
		return true;
	},
	'baz' => false
)
);
$qux = array_filter(
	$quux, function ( $x ) {
	return $x;
}
);

$this->listeners[] = $events->getSharedManager()->attach(
	'Zend\Mvc\Application', MvcEvent::EVENT_DISPATCH, [ $this, 'selectLayout' ], 100
);

// phpcs:set PSR2.Methods.FunctionCallSignature requiredSpacesBeforeClose 1
foo( 'Testing
    multiline text'
);

foo( 'Testing
    multiline text: ' // . $text
);

foo( 'Testing
    multiline text: ' /* . $text */
);

foo( 'Testing
    multiline text: ' /* . $text */
// . $other_text
);

foo( 'Testing
    multiline text: ' /*
 . $text
// . $text2
 */
);
// phpcs:set PSR2.Methods.FunctionCallSignature requiredSpacesBeforeClose 0

foo( 'Testing
    multiline text'
);

foo( 'Testing
    multiline text'
);

foo( 'Testing
    multiline text' // hello
);

foo( 'Testing
    multiline text' /* hello */
);

foo( 'Testing
    multiline text'
// hello
);

foo( 'Testing
    multiline text'
/* hello */
);

$var = foo( 'Testing
    multiline'
       // hi
       ) + foo( 'Testing
    multiline'
       // hi
       );

class Test {
	public function getInstance() {
		return new static(
			'arg',
			'foo'
		);
	}

	public function getSelf() {
		return new self(
			'a', 'b', 'c'
		);
	}
}

$x = $var( 'y',
	'x' );

$obj->{$x}( 1,
	2 );

( function ( $a, $b ) {
	return function ( $c, $d ) use ( $a, $b ) {
		echo $a, $b, $c, $d;
	};
} )(
	'a', 'b'
)( 'c',
	'd' );