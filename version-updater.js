// version-updater.js
module.exports.readVersion = function( contents ) {
	// handle style.css or plugin.php
	const regex = /Version:.*/gim;
	const found = contents.match( regex );
	if ( found ) {
		return found[ 0 ].replace( 'Version: ', '' );
	}

	// handle functions.php
	const regex3 = /version-.[^']*/gim;
	const found3 = contents.match( regex3 );
	if ( found3 ) {
		return found3[ 0 ].replace( 'version-', '' );
	}

	return null;
};

module.exports.writeVersion = function( contents, version ) {
	// handle style.css or plugin.php
	const regex = /Version:.*/gim;
	const found = contents.match( regex );
	if ( found ) {
		return contents.replace( found[ 0 ], 'Version: ' + version );
	}

	// handle functions.php
	const regex3 = /version-.[^']*/gim;
	const found3 = contents.match( regex3 );
	if ( found3 ) {
		return contents.replace( regex3, 'version-' + version );
	}
	return contents;
};
