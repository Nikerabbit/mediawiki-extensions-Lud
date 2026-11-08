<?php

declare( strict_types = 1 );

use Rector\CodingStyle\Rector\ClassLike\NewlineBetweenClassLikeStmtsRector;
use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths( [
		__DIR__ . '/src',
		__DIR__ . '/maintenance',
		__DIR__ . '/phpunit',
		__DIR__ . '/MagicWords.i18n.php',
	] )
	->withPhpSets()
	->withComposerBased()
	->withSkip( [
		// Keep consecutive class member declarations together.
		NewlineBetweenClassLikeStmtsRector::class,
		// MediaWiki codesniffer 52 requires @covers annotations.
		Rector\PHPUnit\AnnotationsToAttributes\Rector\Class_\CoversAnnotationWithValueToAttributeRector::class,
	] )
	->withPreparedSets(
		deadCode: true,
		codeQuality: true,
		codingStyle: true,
		typeDeclarations: true,
		privatization: true,
		phpunitCodeQuality: true,
	);
