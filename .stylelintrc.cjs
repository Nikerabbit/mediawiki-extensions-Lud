const wikimedia = require( 'stylelint-config-wikimedia' );

module.exports = {
	...wikimedia,
	rules: { ...wikimedia.rules }
};

// Stylelint 18 removed this rule; omit it until the shared config is updated.
delete module.exports.rules[ 'declaration-block-single-line-max-declarations' ];
