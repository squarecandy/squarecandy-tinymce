// inspired by https://gist.github.com/jshawl/6225945
// Thanks @jshawl!

// now using grunt-sass to avoid Ruby dependency

module.exports = function( grunt ) {
	grunt.initConfig( {
		pkg: grunt.file.readJSON( 'package.json' ),
		copy: {
			init: {
				files: [
					// common
					{
						expand: true,
						cwd: 'node_modules/squarecandy-common/common',
						src: '**/*',
						dest: '',
						dot: true,
						rename( dest, matchedSrcPath ) {
							// the exact file name .gitignore is reserved by npm
							// so we track it as /common/gitignore (no dot) and rename on copy
							if ( matchedSrcPath === 'gitignore' ) {
								return dest + '.gitignore';
							}
							// default for all other files
							return dest + matchedSrcPath;
						},
					},
				],
			},
		},
		phpcs: {
			application: {
				src: [ '*.php', 'inc/*.php' ],
			},
			options: {
				bin: './vendor/squizlabs/php_codesniffer/bin/phpcs --runtime-set ignore_warnings_on_exit true',
				standard: 'phpcs.xml',
			},
		},
		stylelint: {
			src: [ 'css/*.scss', 'css/**/*.scss', 'css/*.css' ],
		},
		run: {
			stylelintfix: {
				cmd: 'npx',
				args: [ 'stylelint', 'css/*.css', '--fix' ],
			},
			eslintfix: {
				cmd: 'eslint',
				args: [ 'js/*.js', '--fix' ],
			},
			bump: {
				cmd: 'npm',
				args: [ 'run', 'release', '--', '--prerelease', 'dev', '--skip.tag', '--skip.changelog' ],
			},
			ding: {
				cmd: 'tput',
				args: [ 'bel' ],
			},
		},
		eslint: {
			gruntfile: {
				src: [ 'Gruntfile.js' ],
			},
			src: {
				src: [ 'js/*.js' ],
			},
		},
	} );

	grunt.loadNpmTasks( 'grunt-phpcs' );
	grunt.loadNpmTasks( 'grunt-stylelint' );
	grunt.loadNpmTasks( 'grunt-eslint' );
	grunt.loadNpmTasks( 'grunt-contrib-copy' );
	grunt.loadNpmTasks( 'grunt-run' );

	grunt.registerTask( 'init', [ 'copy' ] );
	grunt.registerTask( 'lint', [ 'stylelint', 'eslint', 'phpcs' ] );
	grunt.registerTask( 'bump', [ 'run:bump' ] );
	grunt.registerTask( 'preflight', [ 'lint', 'bump', 'run:ding' ] );
};
